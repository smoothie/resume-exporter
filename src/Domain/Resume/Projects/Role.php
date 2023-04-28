<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Projects;

class Role
{
    public function __construct(
        private readonly string $role,
    ) {
    }

    public function role(): string
    {
        return $this->role;
    }
}
