<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Projects;

class Highlight
{
    public function __construct(
        private readonly string $highlight,
    ) {
    }

    public function highlight(): string
    {
        return $this->highlight;
    }
}
