<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Meta\Content;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class Years
{
    public function __construct(
        private readonly string $singular,
        private readonly string $plural,
    ) {
    }

    public function singular(): string
    {
        return $this->singular;
    }

    public function plural(): string
    {
        return $this->plural;
    }
}
