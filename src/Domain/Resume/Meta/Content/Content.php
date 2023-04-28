<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Meta\Content;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class Content
{
    public function __construct(private readonly Labels $labels)
    {
    }

    public function labels(): Labels
    {
        return $this->labels;
    }
}
