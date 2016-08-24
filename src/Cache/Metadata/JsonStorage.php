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
 * store file metadata in a json file
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class JsonStorage extends AbstractFileStorage
{
   
    protected $cacheExtension = 'meta.json';

    /**
     * {@inheritdoc}
     */
    public function get($path, $key) {
        if(($result = $this->getFromMemory($path)) === false) {
            $cacheFile = $this->getCachePath($path);
            $result = json_decode(file_get_contents($cacheFile) , true);
        }
        return (array_key_exists($key, $result))?$result[$key] : false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function load($path) {
        if(($result = $this->getFromMemory($path)) !== false) {
            return $result;
        }
        $cacheFile = $this->getCachePath($path);
        if(file_exists($cacheFile)) {
            return json_decode(file_get_contents($cacheFile) , true);
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function save($path, Config $data) {
        $cache = $this->parseData($data);
        $this->setToMemory($path, $cache);
        
        $cacheFile = $this->getCachePath($path);
        file_put_contents($cacheFile , json_encode($cache));
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($path, $key, $value) {
        $this->setToMemory($path , $value , $key );
        $cacheFile = $this->getCachePath($path);
        file_put_contents($cacheFile , json_encode($this->getFromMemory($path)));
        return $this;
    }

}
