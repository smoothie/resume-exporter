<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

class DomPdfPageText
{
    public const KEY_PAGE_NUMBERS = 'pageNumbers';
    public const KEY_TEXT = 'text';
    public const KEY_FONT = 'font';
    public const KEY_AXIS_X = 'x';
    public const KEY_AXIS_Y = 'y';
    public const KEY_COLOR = 'color';
    public const KEY_SIZE = 'size';
    public const KEY_WORD_SPACE = 'wordSpace';
    public const KEY_CHAR_SPACE = 'charSpace';
    public const KEY_ANGLE = 'angle';

    public function __construct(
        private readonly string $text,
        private readonly string $font,
        private readonly int $axisX,
        private readonly int $axisY,
        private readonly array $color = [0.0, 0.0, 0.0],
        private readonly int $size = 10,
        private readonly float $wordSpace = 0.0,
        private readonly float $charSpace = 0.0,
        private readonly float $angle = 0.0,
    ) {
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getFont(): string
    {
        return $this->font;
    }

    public function getX(): int
    {
        return $this->axisX;
    }

    public function getY(): int
    {
        return $this->axisY;
    }

    public function getColor(): array
    {
        return $this->color;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getWordSpace(): float
    {
        return $this->wordSpace;
    }

    public function getCharSpace(): float
    {
        return $this->charSpace;
    }

    public function getAngle(): float
    {
        return $this->angle;
    }
}
