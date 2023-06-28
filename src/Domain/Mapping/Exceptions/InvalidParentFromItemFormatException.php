<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class InvalidParentFromItemFormatException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly string $parentItem,
        private readonly mixed $parentItemValue,
        private readonly array $from,
    ) {
        $message = 'InvalidParentFromItemFormatException: Parent item must be a list.';

        parent::__construct(message: $message, code: static::CODE_UNABLE_TO_FIND_PARENT_ITEM_FROM);
    }

    /**
     * @return (array|mixed|string)[]
     *
     * @psalm-return array{parentItem: string, parentItemValue: mixed, from: array}
     */
    public function getContext(): array
    {
        return [
            'parentItem' => $this->parentItem,
            'parentItemValue' => $this->parentItemValue,
            'from' => $this->from,
        ];
    }
}
