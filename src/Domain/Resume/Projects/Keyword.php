<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Projects;

class Keyword
{
    public function __construct(
        private readonly string $keyword,
    ) {
    }

    public function keyword(): string
    {
        return $this->keyword;
    }
}
