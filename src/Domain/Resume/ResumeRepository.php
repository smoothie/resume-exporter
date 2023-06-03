<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume;

use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Resume\Exceptions\InvalidCanonicalReceivedException;

interface ResumeRepository
{
    /**
     * @throws InvalidCanonicalReceivedException
     */
    public function firstAndTranslate(Input $input): Resume;
}
