<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\Adapter\Cache;

/**
 * Description of MetaDataFactory
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class MetaDataFactory {
    
    protected static $classes = [
        'Apcu',
        'Php',
        'Json',
        'Txt',
    ];
    
    protected $ns = '\\oat\\flysystem\\Adapter\\Cache\\Metadata';

    static function build() {
        foreach (self::$classes as $adapter) {
            $className = self::$ns . '\\' . $adapter . 'Storage';
            if($className::enable()) {
                return new $className;
            }
        }
    }
    
}
