<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Mapping\InputMapping;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume\EmptyResume;

class EmptyInputMapping implements InputMapping
{
    public function translateToCanonical(Input $input, array $canonicalData): Resume
    {
        return EmptyResume::create();
    }
}
