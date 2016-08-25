<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

use League\Flysystem\Config;

/**
 * Description of AbstractStorage
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * metadata to store
     * @var array
     */
    protected $properties = [
        'mimetype',
        'size',
        'timestamp',
        'basename',
        'extension',
        'filename',
        'type',
    ];
    
     /**
     * return filtered metadata
     * @param array $data
     * @return array
     */
    protected function parseData(Config $data) {
        $result = [];
        foreach ($this->properties as $property) {
            if($data->has($property)) {
                $value = $data->get($property);
                $this->setParam($result, $property, $value);
            }
        }
        return $result;
    }
    
    protected function setParam(&$result , $property , $value) {
        if(!empty($value)) {
            $result[$property] = $value;
        }
        return $result;
    }
    
}
