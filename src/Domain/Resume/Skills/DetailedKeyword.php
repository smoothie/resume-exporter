<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Skills;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class DetailedKeyword
{
    public function __construct(
        private readonly string $keyword,
        private readonly string $level,
        private readonly string $experienceInYears,
    ) {
    }

    public function keyword(): string
    {
        return $this->keyword;
    }

    public function level(): string
    {
        return $this->level;
    }

    public function experienceInYears(): string
    {
        return $this->experienceInYears;
    }
}
