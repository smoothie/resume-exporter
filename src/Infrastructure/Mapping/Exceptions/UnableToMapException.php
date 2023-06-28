<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class UnableToMapException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly array $invalidItems,
        private readonly array $unreadableItems,
        private readonly array $invalidArrayItems,
        private readonly array $from,
        private readonly array $map,
    ) {
        $message = 'UnableToMapException: Unable to map properties.';

        parent::__construct(message: $message, code: static::CODE_PROPERTIES_NOT_FOUND);
    }

    /**
     * @return array[]
     *
     * @psalm-return array{invalidItems: array, unreadableItems: array, invalidArrayItems: array, from: array, map: array}
     */
    public function getContext(): array
    {
        return [
            'invalidItems' => $this->invalidItems,
            'unreadableItems' => $this->unreadableItems,
            'invalidArrayItems' => $this->invalidArrayItems,
            'from' => $this->from,
            'map' => $this->map,
        ];
    }
}
