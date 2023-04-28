<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Resume\Resume;

interface InputMapping
{
    public function translateToCanonical(Input $input, array $canonicalData): Resume;
}
