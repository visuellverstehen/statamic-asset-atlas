<?php

it('tracks top-level markdown field asset references', function () {
    $asset = $this->createTestAsset('test-markdown-field.jpg');
    $asset->save();

    $markdownContent = "Here is some text with an image:\n\n![](statamic://asset::assets::".$asset->path().")\n\nAnd some more text.";

    $entry = $this->createEntryWithTopLevelAsset('content', $markdownContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
