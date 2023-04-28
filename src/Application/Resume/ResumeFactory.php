<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Application\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Resume;

interface ResumeFactory
{
    public function toArray(Resume $resume): array;
}
