<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class UnableToFindParentFromItemException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly string $toItem,
        private readonly string $fromItem,
        private readonly array $from,
    ) {
        $message = 'ParentFromItemNotFoundException: Unable to find parent item.';

        parent::__construct(message: $message, code: static::CODE_UNABLE_TO_FIND_PARENT_ITEM_FROM);
    }

    /**
     * @return (array|string)[]
     *
     * @psalm-return array{fromItem: string, toItem: string, from: array}
     */
    public function getContext(): array
    {
        return [
            'fromItem' => $this->fromItem,
            'toItem' => $this->toItem,
            'from' => $this->from,
        ];
    }
}
