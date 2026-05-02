<?php

use Vima\Core\Config\Setup;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PolicyInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\DTOs\AccessContext;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Tests\Fixtures\User;
use function Vima\Core\resolve;


class Student
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $name,
        public bool $isLeader = false,
        public bool $isSuspended = false,
        public string $reasonForSuspension = '',
    ) {
    }
}

class StudentPolicy implements PolicyInterface
{
    public static function getResource(): string
    {
        return Student::class;
    }

    public function canView(AccessContext $ctx, Student $student): bool
    {
        if ($ctx->isAny(['school:principal', 'school:teacher'])) {
            return true;
        }

        if ($ctx->is("school:student")) {
            if ($student->id === $ctx->resolveId() && !$student->isSuspended) {
                return true;
            }
        }

        return false;
    }

    public function canUpdate(AccessContext $ctx, Student $student): bool
    {
        if ($ctx->is("school:student")) {
            if ($student->id === $ctx->resolveId() && !$student->isSuspended) {
                return true;
            }
        }

        if ($ctx->isAny(['school:principal'])) {
            return true;
        }

        return false;
    }

    public function canUpdateProfile(AccessContext $ctx, Student $student): bool
    {
        if ($ctx->is("school:student")) {
            if ($student->id === $ctx->resolveId() && !$student->isSuspended) {
                return true;
            }
        }

        if ($ctx->isAny(['school:principal'])) {
            return true;
        }

        return false;
    }
}


beforeEach(function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */
    initDependencies();

    /** @var VimaConfig $config */
    $config = resolve(VimaConfig::class);

    $config->superAdminRole = 'admin';
    $config->superAdminBypass = true; // use this to automatically bypass access checks for super admins, or set to false to have them go through the normal flow (but they will still have all permissions by default)

    //dd(resolve(VimaConfig::class));

    // Setup roles and permissions
    $adminRole = Role::define(name: 'admin', permissions: [
        Permission::define(name: 'posts.create'),
        Permission::define(name: 'posts.update'),
        Permission::define(name: 'posts.delete'),
        Permission::define(name: 'posts.view'),
        Permission::define(name: 'plans.view', namespace: 'school'),
        Permission::define(name: 'plans.create', namespace: 'school'),
        Permission::define(name: 'plans.update', namespace: 'school'),
        Permission::define(name: 'plans.delete', namespace: 'school'),
        Permission::define(
            name: 'students.view',
            namespace: 'school',
            description: 'Allows viewing student records in the system.'
        ),
        Permission::define(
            name: 'students.create',
            namespace: 'school',
            description: 'Allows enrolling new students into the system.'
        ),
        Permission::define(
            name: 'students.update',
            namespace: 'school',
            description: 'Allows editing existing student information.'
        ),

        Permission::define(
            name: 'students.update.profile',
            namespace: 'school',
            description: 'Allows editing specifically profile information.'
        ),
        Permission::define(
            name: 'students.delete',
            namespace: 'school',
            description: 'Allows removing a student record from the system (Hard or Soft delete).'
        ),
        Permission::define(
            name: 'students.suspend',
            namespace: 'school',
            description: 'Allows toggling the suspension status of a student for disciplinary reasons.'
        ),
        Permission::define(
            name: 'students.promote',
            namespace: 'school',
            description: 'Allows promoting a student to a leadership role, granting them additional permissions or responsibilities within the system.'
        )
    ]);

    $editorRole = Role::define(name: 'editor', permissions: [
        'posts.create',
        'posts.update',
        'posts.view',
    ]);

    $teacherRole = Role::define(name: 'teacher', permissions: [
        'school:students.view',
    ], namespace: 'school');

    $principalRole = Role::define(name: 'principal', permissions: [
        'school:students.create',
        'school:students.update',
        'school:students.delete',
    ], parents: ['school:teacher'], namespace: 'school');

    $schoolStaff = Role::define(
        name: 'staff',
        permissions: [
            'school:plans.view',
        ],
        namespace: 'school',
        children: [
            Role::define(name: 'registrar', namespace: 'school')
        ]
    );

    $schoolRegistrar = Role::define(
        name: 'registrar',
        namespace: 'school',
        permissions: [
            'school:plans.create',
            'school:plans.update',
            'school:plans.delete',
        ],
        parents: [
            // Role::define('staff', namespace: 'school')
        ]
    );

    $studentRole = Role::define(name: 'student', permissions: [], namespace: 'school');

    $viewerRole = Role::define(name: 'viewer', permissions: [
        'posts.view',
    ]);

    $this->roles = [
        $adminRole,
        $editorRole,
        $viewerRole,
        $principalRole,
        $teacherRole,
        $studentRole,
        $schoolRegistrar,
        $schoolStaff,
    ];

    $this->permissions = array_merge(
        $adminRole->permissions,
    );

    $setup = resolve(Setup::class);
    $setup->roles = $this->roles;
    $setup->permissions = $this->permissions;

    // Setup Policy Registry
    $this->policyRegistry = resolve(PolicyRegistryInterface::class);
    $this->policyRegistry->register('posts.update', function (AccessContext $ctx, $post) {
        // Editors and Admins can update any post
        foreach (resolve(AccessManager::class)->getUserRoles($ctx->user) as $role) {
            if (in_array($role->name, ['editor', 'admin'])) {
                return true;
            }
        }
        return false;
    });

    $this->policyRegistry->registerClass(Student::class, StudentPolicy::class);

    $this->manager = resolve(AccessManager::class);

    // two ways to start off here
    // Both should work well

    // 1. Use the SyncSevice
    resolve(SyncService::class)->sync($config);

    // 2. Add the roles using the manager->addRole
    // foreach ($this->roles as $role) {
    //     $this->manager->ensureRole($role);
    // }

    // Fake users
    $this->alice = new User(1);
    $this->manager->assignRole($this->alice, $adminRole);

    $this->bob = new User(2);
    $this->manager->assignRole($this->bob, $editorRole);

    $this->carol = new User(3);
    $this->manager->assignRole($this->carol, $viewerRole);

    $this->david = new User(4);
    $this->manager->assignRole($this->david, $principalRole);

    $this->eve = new User(5);
    $this->manager->assignRole($this->eve, $teacherRole);

    $this->felicia = new User(6);
    $this->manager->assignRole($this->felicia, $studentRole);

    $this->john = new User(7);
    $this->manager->assignRole($this->john, $studentRole);

    $this->jane = new User(8);
    $this->manager->assignRole($this->jane, $studentRole);


    // Fake post resource
    $this->post = ['id' => 1, 'owner' => 3];
    $this->normalStudent = new Student(1, 6, 'John Doe');
    $this->suspendedStudent = new Student(2, 7, 'Jane Doe', isSuspended: true);
    $this->studentLeader = new Student(3, 8, 'Jim Doe', isLeader: true);
});

test('admins can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->alice, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->alice, 'posts.update', null, $this->post))->toBeTrue();
});

test('editors can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->bob, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->bob, 'posts.update', null, $this->post))->toBeTrue();
});

test('viewers cannot update posts, even if owner', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->carol, 'posts.update'))->toBeFalse();
    expect($this->manager->evaluatePolicy($this->carol, 'posts.update', null, $this->post))->toBeFalse();
    $this->manager->enforce($this->carol, 'posts.update'); // should throw
})->throws(AccessDeniedException::class);

test('admins can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->alice, 'posts.update', null, $this->post))->toBeTrue();
});

test('editors can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->bob, 'posts.update', null, $this->post))->toBeTrue();
});

test('viewers cannot update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', null, $this->post))->toBeFalse();
});

test('viewers can view posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->bob, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->alice, 'posts.view'))->toBeTrue();
});

test('returns false when user lacks permission even if policy exists', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', null, $this->post))->toBeFalse();
});

test('policies are evaluated for permissions with namespaces', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->david, 'school:students.view', null, $this->normalStudent))->toBeTrue();
    expect($this->manager->can($this->eve, 'students.view', 'school', $this->normalStudent))->toBeTrue();
    expect($this->manager->can($this->alice, 'students.view', 'school', $this->normalStudent))->toBeTrue();
    expect($this->manager->can($this->alice, 'students.update.profile', 'school', $this->normalStudent))->toBeTrue();

    expect($this->manager->can($this->david, 'school:students.update', null, $this->normalStudent))->toBeTrue();
    expect($this->manager->can($this->eve, 'school:students.update', null, $this->normalStudent))->toBeFalse();
    expect($this->manager->can($this->alice, 'school:students.update', null, $this->normalStudent))->toBeTrue();
});

test('principals inherit permissions from teachers', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    // Principal doesn't explicitly have view, but teacher does
    expect($this->manager->can($this->david, 'school:students.view'))->toBeTrue();
});

test('suspended students cannot update their own profile', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->john, 'school:students.update.profile', null, $this->suspendedStudent))->toBeFalse();
});

test('students cannot update other students', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->felicia, 'school:students.update.profile', null, $this->studentLeader))->toBeFalse();
});
