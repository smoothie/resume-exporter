<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Resume;

use Smoothie\ResumeExporter\Application\Resume\ResumeRepository;
use Smoothie\ResumeExporter\Domain\Mapping\Output;

class WriteResumeRepository implements ResumeRepository
{
    public function persist(Output $output): void
    {
    }
}
