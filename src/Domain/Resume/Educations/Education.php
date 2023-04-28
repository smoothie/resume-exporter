<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Educations;

class Education
{
    public function __construct(
        private readonly string $area,
        private readonly string $endDate,
        private readonly string $startDate,
        private readonly string $studyType,
    ) {
    }

    public function area(): string
    {
        return $this->area;
    }

    public function endDate(): string
    {
        return $this->endDate;
    }

    public function startDate(): string
    {
        return $this->startDate;
    }

    public function studyType(): string
    {
        return $this->studyType;
    }
}
