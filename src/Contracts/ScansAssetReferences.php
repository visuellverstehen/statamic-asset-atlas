<?php

namespace VV\AssetAtlas\Contracts;

use Statamic\Fieldtypes\UpdatesReferences;

/**
 * Interface for fieldtypes that can scan their data for asset references.
 *
 * Custom fieldtypes using Statamic's UpdatesReferences trait can implement
 * this interface to participate in AssetAtlas scanning. This enables
 * AssetAtlas to track asset references in third-party or custom fieldtypes.
 *
 * The interface is optional - fieldtypes that don't implement it will still
 * work for Statamic's reference updates, but AssetAtlas won't be able to
 * track their asset references during scans.
 *
 * @see UpdatesReferences
 */
interface ScansAssetReferences
{
    /**
     * Scan field data for asset references.
     *
     * Implementations should extract all asset paths/containers from
     * the provided data and return them as an array of references.
     *
     * For fieldtypes that store container in configuration:
     *   return [['container' => $this->config('container'), 'path' => $path], ...]
     *
     * For fieldtypes that embed container in data:
     *   return [['container' => $extractedContainer, 'path' => $extractedPath], ...]
     *
     * @param  mixed  $data  The field's stored data
     * @return array Array of ['container' => string, 'path' => string] references
     */
    public function scanAssetReferences($data): array;
}
