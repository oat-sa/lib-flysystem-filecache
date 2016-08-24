<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\Adapter\Cache\Metadata;

/**
 * Description of AbstractFileStorage
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
    
    /**
     * get data from memory cache
     * @param string $path
     * @param string $key
     * @return mixed
     */
    protected function getFromMemory($path, $key = null) {
        if(array_key_exists($path, $this->memoryCache)) {
            return (is_null($key)?$this->memoryCache[$path]:$this->memoryCache[$path][$key]);
        }
        return false;
    }

    /**
     * save data in memory
     * @param string $path
     * @param mixed $value
     * @param string $key
     * @return \oat\flysystem\Adapter\JsonStorage
     */
    protected function setToMemory($path , $value , $key = null) {
        if(is_null($key)) {
            $this->memoryCache[$path][$key] = $value;
        } else {
            $this->memoryCache[$path] = $value;
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
        return $infos['dirname'] . DIRECTORY_SEPARATOR . '.' . $infos['basename'] . '.' . $this->cacheExtension; 
        
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
}
