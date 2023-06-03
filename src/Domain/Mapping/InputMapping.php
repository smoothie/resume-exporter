<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Resume\Exceptions\InvalidCanonicalReceivedException;
use Smoothie\ResumeExporter\Domain\Resume\Resume;

interface InputMapping
{
    /**
     * @throws InvalidCanonicalReceivedException
     */
    public function translateToCanonical(Input $input, array $canonicalData): Resume;
}
