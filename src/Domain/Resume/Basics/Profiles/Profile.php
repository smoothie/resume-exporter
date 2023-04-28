<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles;

class Profile
{
    public function __construct(
        private readonly string $network = '',
        private readonly string $url = '',
        private readonly string $username = '',
    ) {
    }

    public function network(): string
    {
        return $this->network;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function username(): string
    {
        return $this->username;
    }
}
