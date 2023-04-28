<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Skills;

use Webmozart\Assert\Assert;

class Skills
{
    /**
     * @param Skill[] $skills
     */
    public function __construct(
        private readonly array $skills,
    ) {
        Assert::allIsInstanceOf(value: $skills, class: Skill::class);
    }

    public function skills(): array
    {
        return $this->skills;
    }
}
