<?php

namespace VV\AssetAtlas\Exceptions;

use RuntimeException;
use Throwable;

class AssetAtlasTransactionException extends RuntimeException
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $itemType,
        public readonly ?string $itemContext,
        Throwable $previous,
    ) {
        parent::__construct(
            "AssetAtlas transaction failed for {$itemType} `{$itemId}`",
            0,
            $previous,
        );
    }
}
