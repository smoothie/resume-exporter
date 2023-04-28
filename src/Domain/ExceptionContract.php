<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain;

interface ExceptionContract extends \Throwable
{
    /** @var int */
    public const CODE_PROPERTIES_NOT_FOUND = 1682337062;
    public const CODE_UNABLE_TO_FIND_PARENT_ITEM_FROM = 1682337063;
    public const CODE_INVALID_PARENT_FROM_ITEM_FORMAT = 1682337064;
    public const CODE_MISMATCHED_MAP_ITEM_DEPTH = 1682337065;
    public const CODE_INVALID_CANONICAL_RECEIVED = 1682337066;

    public const CODE_UNABLE_TO_PARSE_JSON = 1682337067;

    public const CODE_UNABLE_TO_REPLACE_DOT_NOTATION = 1682337068;

    public function getContext(): array;
}
