<?php

namespace VV\AssetAtlas\Exceptions;

use RuntimeException;
use Throwable;

class AssetAtlasTransactionFailed extends RuntimeException
{
    public function __construct(
        public readonly string $itemId,
        public readonly ?string $itemType,
        public readonly ?string $itemContext,
        Throwable $previous,
    ) {
        parent::__construct("Asset atlas transaction failed for {$itemType} {$itemId}", 0, $previous);
    }
}
