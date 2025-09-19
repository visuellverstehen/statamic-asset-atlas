<?php

it('tracks top-level markdown field asset references', function () {
    $asset = $this->createAsset('test-markdown-field.jpg');
    $asset->save();

    $markdownContent = "Here is some text with an image:\n\n![](statamic://asset::assets::".$asset->path().")\n\nAnd some more text.";

    $entry = $this->createEntryWithTopLevelAsset('content', $markdownContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);

    $asset2 = $this->createAsset('test-markdown-field-2.jpg');
    $asset2->save();

    $updatedMarkdownContent = $markdownContent."\n\nAnother image:\n\n![](statamic://asset::assets::".$asset2->path().')';

    $entry->set('content', $updatedMarkdownContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});
