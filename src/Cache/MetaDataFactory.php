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
    
    protected $classes = [
        'Apcu',
        'Php',
        'Json',
        'Txt',
    ];
    
    protected $ns = '\\oat\\flysystem\\Adapter\\Cache\\Metadata';


    static function build() {
        foreach ($this->classes as $adapter) {
            $className = $this->ns . '\\' . $adapter . 'Storage';
            if($className::enable()) {
                return new $className;
            }
        }
    }
    
}
