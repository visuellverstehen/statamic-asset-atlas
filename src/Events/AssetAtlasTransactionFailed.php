<?php

namespace VV\AssetAtlas\Events;

use Throwable;

class AssetAtlasTransactionFailed
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $itemType,
        public readonly ?string $itemContext,
        public readonly Throwable $exception,
    ) {}
}
