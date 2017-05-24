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

namespace oat\flysystem\Adapter\Cache;

/**
 * factory using metadata storage stategy to define
 * which one is better
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
    
    protected static $ns = '\\oat\\flysystem\\Adapter\\Cache\\Metadata';
    /**
     * return better metadata storage instance
     * @return \oat\flysystem\Adapter\Cache\Metadata\AbstractStorage
     */
    static function build() {
        foreach (self::$classes as $adapter) {
            $className = self::$ns . '\\' . $adapter . 'Storage';
            if($className::enable()) {
                return new $className;
            }
        }
    }
    
}
