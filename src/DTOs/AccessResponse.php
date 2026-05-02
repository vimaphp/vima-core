<?php

namespace Vima\Core\DTOs;

/**
 * Class AccessResponse
 * 
 * Represents the detailed result of an authorization check.
 */
class AccessResponse
{
    private function __construct(
        private bool $allowed,
        private ?string $reason = null,
        private bool $abstain = false
    ) {}

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(?string $reason = null): self
    {
        return new self(false, $reason);
    }

    public static function abstain(): self
    {
        return new self(false, null, true);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function shouldAbstain(): bool
    {
        return $this->abstain;
    }
}
