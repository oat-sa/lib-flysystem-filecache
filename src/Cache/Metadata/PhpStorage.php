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
class PhpStorage extends AbstractFileStorage
{
    protected $cacheExtension = 'meta.php'; 
    
    /**
     * 
     * @param array $data
     * @return string
     */
    protected function toPhpCode(array $data) {
        return '<?php return ' . var_export($data, true) . ';';
    }
    
    protected function refesh($file) {
        if(extension_loaded('Zend OPcache') && opcache_get_status()) {
            opcache_compile_file($file);
        }
        return $this;
    }


    public function get($path, $key) {
        if(($result = $this->getFromMemory($path)) !== false) {
            return $result;
        }
        if(($result = $this->load($path)) !== false) {
            $this->setToMemory($path, $result);
            return (array_key_exists($key, $result))?$result[$key] : false;
        }
        return false;
    }

    public function load($path) {
        if(($result = $this->getFromMemory($path)) !== false) {
            return $result;
        }
        $cacheFile =  $this->getCachePath($path);
        if(file_exists($cacheFile)) {
            include $cacheFile;
        }
        return false;
    }

    public function save($path, Config $data) {
        $cache = $this->parseData($data);
        $this->setToMemory($path, $cache);
        $cacheFile = $this->getCachePath($path);
        file_put_contents($cacheFile , $this->toPhpCode($cache));
        return $this;
    }

    public function set($path, $key, $value) {
        $this->setToMemory($path , $value , $key );
        $cacheFile = $this->getCachePath($path);
        file_put_contents($cacheFile , $this->toPhpCode($this->getFromMemory($path)));
        return $this;
    }

}

