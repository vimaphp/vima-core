<?php

use Vima\Core\Config\VimaConfig;

it('calls registerPolicies closure in constructor', function () {
    $called = false;
    new VimaConfig(registerPolicies: function() use (&$called) {
        $called = true;
    });
    
    expect($called)->toBeTrue();
});

it('instantiates with default values', function () {
    $config = new VimaConfig();
    expect($config->cacheEnabled)->toBeFalse();
    expect($config->cacheTTL)->toBe(3600);
});
