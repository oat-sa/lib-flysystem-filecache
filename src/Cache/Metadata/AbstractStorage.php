<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

use League\Flysystem\Config;

/**
 * Abstract explicite metadata storage
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
                $result = $this->setParam($result, $property, $value);
            }
        }
        return $result;
    }
    
    protected function setParam($result , $property , $value) {
        if(!empty($value)) {
            $result[$property] = $value;
        }
        return $result;
    }
    
}
