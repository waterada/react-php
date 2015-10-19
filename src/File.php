<?php
namespace ReactPHP;

class File {
    public static function load($path) {
        return json_decode(file_get_contents($path), true);
    }

    public static function save($path, $contents) {
        file_put_contents($path, json_encode($contents));
    }
}

