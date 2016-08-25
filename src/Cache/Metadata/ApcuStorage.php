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

/**
 * Description of ApcuStorage
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class ApcuStorage extends AbstractStorage {
    
    public static function enable() {
        return (extension_loaded('apcu') && apcu_enabled());
    }

    public function copy($path, $newpath) {
        if(apcu_exists($path)) {
            $data = apcu_fetch($path);
            apcu_add($newpath, $data);
        }
        return $this;
    }

    public function delete($path) {
        if(apcu_exists($path)) {
            apcu_delete($path);
        }
        return $this;
    }
    
    public function rename($path, $newpath) {
        if(apcu_exists($path)) {
            $data = apcu_fetch($path);
            apcu_add($newpath, $data);
            apcu_delete($path);
        }
        return $this;
    }
    
    public function get($path, $key) {
        if(apcu_exists($path)) {
            $data = apcu_fetch($path);
            if(array_key_exists($key, $data)) {
                return $data[$key];
            }
        }
        return false;
    }

    public function load($path) {
        if(apcu_exists($path)) {
            return apcu_fetch($path);
        }
        return false;
    }

    public function save($path, \League\Flysystem\Config $data) {
        $cache = $this->parseData($data);
        apcu_store($path, $cache);
        return $this;
    }

    public function set($path, $key, $value) {
        $data = [];
         if(apcu_exists($path)) {
             $data = apcu_fetch($path);
         }
         $data[$key] = $value;
         apcu_store($path, $data);
         return $this;
    }

}
