<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Application\Resume;

use Smoothie\ResumeExporter\Domain\Mapping\Output;

interface ResumeRepository
{
    public function persist(Output $output): void;
}
