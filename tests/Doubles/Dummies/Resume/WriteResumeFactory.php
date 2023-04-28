<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume;

use Smoothie\ResumeExporter\Application\Resume\ResumeFactory;
use Smoothie\ResumeExporter\Domain\Resume\Resume;

class WriteResumeFactory implements ResumeFactory
{
    public function toArray(Resume $resume): array
    {
        return [];
    }
}
