<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

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
    ];
    /**
     * return filtered metadata
     * @param array $data
     * @return array
     */
    protected function parseData(\League\Flysystem\Config $data) {
        $result = [];
        foreach ($this->properties as $property) {
            if($data->has($property)) {
                $result[$property] = $data->get($property);
            }
        }
        return $result;
    }
    
}
