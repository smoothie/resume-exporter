<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class MismatchedMapItemDepthException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly string $toItem,
        private readonly string $fromItem,
        private readonly int $toItemDepth,
        private readonly int $fromItemDepth,
        private readonly array $map,
    ) {
        $message = 'MismatchedMapItemDepthException: Unable to normalize, because $fromItem length differs from $toItem length.';

        parent::__construct(message: $message, code: static::CODE_MISMATCHED_MAP_ITEM_DEPTH);
    }

    /**
     * @return (array|int|string)[]
     *
     * @psalm-return array{fromItem: string, toItem: string, fromItemDepth: int, toItemDepth: int, map: array}
     */
    public function getContext(): array
    {
        return [
            'fromItem' => $this->fromItem,
            'toItem' => $this->toItem,
            'fromItemDepth' => $this->fromItemDepth,
            'toItemDepth' => $this->toItemDepth,
            'map' => $this->map,
        ];
    }
}
