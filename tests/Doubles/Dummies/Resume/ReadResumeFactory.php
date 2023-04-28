<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeFactory;

class ReadResumeFactory implements ResumeFactory
{
    public function fromArray(array $canonicalData): Resume
    {
        return EmptyResume::create();
    }

    public function validate(array $canonicalData): void
    {
    }
}
