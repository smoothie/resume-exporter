<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

enum OutputFormat: string
{
    case PDF = 'PDF';
    case DOCTRINE = 'DOCTRINE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
