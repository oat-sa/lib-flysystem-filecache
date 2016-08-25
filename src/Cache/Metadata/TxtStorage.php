<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

/**
 * Description of TxtStorage
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class TxtStorage extends AbstractFileStorage {
    
    protected $cacheExtension = 'meta.txt';
    
    /**
     * {@inheritdoc}
     */
    protected function readFile($path) {
        if(fileExists($path)) {
            return unserialize(file_get_contents($path) );
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function writeFile($path, array $data) {
        return file_put_contents($path , serialize($data));
    }

    public static function enable() {
        return true;
    }

}
