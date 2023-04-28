<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume;

use Smoothie\ResumeExporter\Domain\Mapping\Input;

interface ResumeRepository
{
    public function firstAndTranslate(Input $input): Resume;
}
