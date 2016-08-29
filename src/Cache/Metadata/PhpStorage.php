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
     * {@inheritdoc}
     */
    public static function enable() {
        return (extension_loaded('Zend OPcache') && opcache_get_status());
    }
    
    /**
     * 
     * @param array $data
     * @return string
     */
    protected function toPhpCode(array $data) {
        return '<?php return ' . var_export($data, true) . ';';
    }
    
    protected function refesh($file) {
        if(self::enable()) {
            opcache_compile_file($file);
        }
        return $this;
    }

    protected function readFile($path) {
        
        if(file_exists($path)) {
            return include $path;
        }

        return false;
    }

    protected function writeFile($path, array $data) {
        $result = file_put_contents($path , $this->toPhpCode($data));
        $this->refesh($path);
        return $result;
    }

}

