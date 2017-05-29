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
 * Abstract explicite metadata storage base on file system.
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
abstract class AbstractFileStorage extends AbstractStorage
{
    /**
     * cache data in memory to improve performance
     * @var array
     */
    protected $memoryCache = [];

    protected $cacheExtension = '';

    protected $cacheDirectoryName = '.meta';

    protected $isLoaded = false;

    /**
     * return file parse content or false
     * @return array|boolean
     */
    abstract protected function readFile($path);

    /**
     * @param $path
     * @param array $data
     * @return boolean
     */
    abstract protected function writeFile($path , array $data);

    /**
     * get data from memory cache
     * @param string $path
     * @param string $key
     * @return mixed
     */
    protected function getFromMemory($path, $key = null) {
        if(array_key_exists($path, $this->memoryCache)) {
            if(is_null($key)) {
                return $this->memoryCache[$path];
            }
            if(array_key_exists($key, $this->memoryCache[$path])) {
                return $this->memoryCache[$path][$key];
            }
        }
        return false;
    }

    protected function eraseOldValues($path , array $values) {
        $this->load($path);
        if(!isset($this->memoryCache[$path])) {
            $this->memoryCache[$path] = [];
        }
        foreach ($values as $key => $value) {
            $this->memoryCache[$path][$key] = $value;
        }
        return $this;
    }

    /**
     * save data in memory
     * @param string $path
     * @param mixed $value
     * @param string $key
     * @return $this
     */
    protected function setToMemory($path , $value , $key = null) {
        if(is_null($key)) {
            return $this->eraseOldValues($path , $value);
        } else {
            $this->memoryCache[$path][$key] = $value;
        }
        return $this;
    }

    /**
     * get cache file path from original file path
     * @param string $path
     * @return string
     */
    protected function getCachePath($path) {

        $infos = pathinfo($path);
        $path  = $infos['dirname'] . DIRECTORY_SEPARATOR;

        if(!empty($this->cacheDirectoryName)) {
            $path .= $this->cacheDirectoryName . DIRECTORY_SEPARATOR;
        }
        if(!is_dir($path)) {
            mkdir($path , 0744 , true);
        }
        $path .= $infos['basename'] . '.' . $this->cacheExtension;
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath) {
        copy($this->getCachePath($path), $this->getCachePath($newpath));
        if(array_key_exists($path, $this->memoryCache)) {
            $this->memoryCache[$newpath] = $this->memoryCache[$path];
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path) {
        unlink($this->getCachePath($path));
        if(array_key_exists($path, $this->memoryCache)) {
            unset($this->memoryCache[$path]);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath) {
        rename($this->getCachePath($path), $this->getCachePath($newpath));
        if(array_key_exists($path, $this->memoryCache)) {
            $this->memoryCache[$newpath] = $this->memoryCache[$path];
            unset($this->memoryCache[$path]);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $key) {

        if(($result = $this->getFromMemory($path, $key)) === false) {
            $cacheFile = $this->getCachePath($path);
            if(file_exists($cacheFile)) {
                $result = $this->readFile($cacheFile);
                return (array_key_exists($key, $result))?[$key => $result[$key]] : false;
            }
            return false;
        }
        return [$key => $result];
    }

    /**
     * {@inheritdoc}
     */
    public function load($path) {
        $this->isLoaded = true;
        if(($result = $this->getFromMemory($path)) !== false) {
            return $result;
        }
        $cacheFile = $this->getCachePath($path);
        if(file_exists($cacheFile)) {
            $data = $this->readFile($cacheFile);
            $this->setToMemory($path, $data);
            return $data;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($path, Config $data) {
        if(!$this->isLoaded) {
            $this->load($path);
        }
        $cache = $this->parseData($data);
        $cache = $this->setToMemory($path, $cache)->getFromMemory($path);
        $cacheFile = $this->getCachePath($path);
        $this->writeFile($cacheFile , $cache);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($path, $key, $value) {
        $this->load($path);
        $cacheFile = $this->getCachePath($path);
        $data = $this->getFromMemory($path);
        if($data === false) {
            $data = [];
        }
        $data[$key] = $value;
        $data = $this->setToMemory($path, $data)->getFromMemory($path);
        $this->writeFile($cacheFile , $data);

        return $this;
    }
}
