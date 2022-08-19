<?php

namespace model;

class AssetNote extends \QuickNote {
    static function forAsset($asset) {
        return static::objects()->filter(array('ext_id' => 'I'.$asset->getId()));
    }
}