<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Projects;

use Webmozart\Assert\Assert;

class Projects
{
    /**
     * @param Project[] $projects
     */
    public function __construct(
        private readonly array $projects,
    ) {
        Assert::allIsInstanceOf(value: $projects, class: Project::class);
    }

    public function projects(): array
    {
        return $this->projects;
    }
}
