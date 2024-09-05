<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Application\Resume\ResumeRepository as WriteRepository;
use Smoothie\ResumeExporter\Domain\Mapping\Input;
use Smoothie\ResumeExporter\Domain\Mapping\InputMapping;
use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputMapping;
use Smoothie\ResumeExporter\Domain\Resume\Resume;
use Smoothie\ResumeExporter\Domain\Resume\ResumeRepository as ReadRepository;

class NoOpRepository implements ReadRepository, InputMapping, WriteRepository, OutputMapping
{
    public function translateToCanonical(Input $input, array $canonicalData): Resume|array
    {
        return [];
    }

    public function persist(Output $output): void
    {
    }

    public function firstAndTranslate(Input $input): Resume|array
    {
        return [];
    }

    public function translateFromCanonical(Output $output): array
    {
        return [];
    }
}
