<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Application\Resume\ResumeFactory;
use Smoothie\ResumeExporter\Domain\Resume\Resume;

class PdfResumeFactory implements ResumeFactory
{
    public function toArray(Resume $resume): array
    {
        return $resume->toArray();
    }
}
