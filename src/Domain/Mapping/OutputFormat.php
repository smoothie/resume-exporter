<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

enum OutputFormat: string
{
    case PDF = 'PDF';
    case DOCTRINE = 'DOCTRINE';

    /**
     * @return string[]
     *
     * @psalm-return list{'PDF', 'DOCTRINE'}
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
