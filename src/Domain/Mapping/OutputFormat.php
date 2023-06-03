<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

enum OutputFormat: string
{
    case PDF = 'PDF';
    case DOCTRINE = 'DOCTRINE';
}
