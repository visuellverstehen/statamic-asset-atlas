<?php

use Statamic\Facades\Blueprint;
use VV\AssetAtlas\AssetScanner;

/*
 * Contract: the scanner must never write back to the item it scans.
 *
 * v0.6.0 briefly aligned $item->data() with the scanned snapshot and restored
 * it in a finally block, on the assumption that data() is a side-effect-free
 * property bag. That assumption is false for items with an asymmetric data()
 * accessor - most importantly Statamic's Eloquent User, whose getter injects
 * computed `roles`/`groups` keys that the setter then materialises as model
 * attributes. The next save of that user then emits `update users set ...,
 * roles = [], groups = []`, crashing on installs whose users table has no such
 * columns (and silently wiping roles on installs that do).
 *
 * This pins the invariant with a minimal asymmetric item so a future change
 * cannot reintroduce the write-back. The scanner reads nested structure from
 * its own $dataToScan snapshot (see AssetScanner's *Children overrides), never
 * by mutating the live item.
 */

it('never writes back to the scanned item', function () {
    $item = new class
    {
        public int $setterCalls = 0;

        public function id()
        {
            return 'spy-item';
        }

        public function blueprint()
        {
            return Blueprint::makeFromFields([
                'assets_field' => ['type' => 'assets', 'container' => 'assets'],
            ]);
        }

        public function data($data = null)
        {
            if (func_num_args() === 0) {
                // Asymmetric getter: injects a computed key the setter would
                // persist, mirroring Eloquent User::data() adding roles/groups.
                return collect([
                    'assets_field' => ['spy/image.jpg'],
                    'computed_key' => 'value',
                ]);
            }

            $this->setterCalls++;

            return $this;
        }
    };

    AssetScanner::item($item)->removeReferences();

    expect($item->setterCalls)->toBe(0);
});
