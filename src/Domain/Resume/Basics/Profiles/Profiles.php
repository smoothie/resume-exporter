<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics\Profiles;

use Webmozart\Assert\Assert;

class Profiles
{
    /**
     * @param Profile[] $profiles
     */
    public function __construct(
        private readonly array $profiles = [],
    ) {
        Assert::allIsInstanceOf(value: $profiles, class: Profile::class);
    }

    /**
     * @return Profile[]
     *
     * @psalm-return array<Profile>
     */
    public function profiles(): array
    {
        return $this->profiles;
    }
}
