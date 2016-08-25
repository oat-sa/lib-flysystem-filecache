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
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
interface StorageInterface {
    
    /**
     * check adapter requirements
     * @return boolean
     */
    public static function enable();

    /**
     * 
     * @param string $path
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($path , $key , $value);
    
    /**
     * 
     * @param string $path
     * @param string $key
     * @return array
     */
    public function get($path , $key);
    
    /**
     * 
     * @param string $path
     * @param array $data
     * @return $this
     */
    public function save($path , \League\Flysystem\Config $data);
    
    /**
     * 
     * @param string $path
     * @return array
     */
    public function load($path);
    
    /**
     * @param string $path
     * @return $this
     */
    public function delete($path);
    
    /**
     * 
     * @param string $path
     * @param string $newpath
     * @return $this
     */
    public function copy($path , $newpath);
    
    /**
     * 
     * @param string $path
     * @param string $newpath
     * @return $this
     */
    public function rename($path , $newpath);
    
}
