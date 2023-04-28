<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume;

use Smoothie\ResumeExporter\Domain\Resume\Exceptions\InvalidCanonicalReceivedException;

interface ResumeFactory
{
    public function fromArray(array $canonicalData): Resume;

    /**
     * @psalm-assert array<array-key, mixed> $canonicalData
     * todo: create psalm canonical type
     *
     * @throws InvalidCanonicalReceivedException when canonical data is not in a canonical form
     */
    public function validate(array $canonicalData): void;
}
