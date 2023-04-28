<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class Item
{
    public function __construct(
        private readonly string $label,
        private readonly string $value,
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function value(): string
    {
        return $this->value;
    }
}
