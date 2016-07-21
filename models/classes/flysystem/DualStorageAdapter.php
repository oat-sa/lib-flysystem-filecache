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
namespace oat\libFlysystemFilecache\model\flysystem;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;

/**
 * Class DualStorageAdapter
 * use two storage, a remote and a local
 * local has priority on read operation
 *
 * @package oat\libFlysystemFilecache\model\flysystem
 */
class DualStorageAdapter extends AbstractAdapter
{
    /**
     * remote flysystem adapter
     * @var AbstractAdapter
     */
    protected $remoteStorage;

    /**
     * local flysystem adapter
     * @var AbstractAdapter
     */
    protected $localStorage;
    /**
     * config use to store to local from remote
     * on read operation
     * @var Config
     */
    protected $localConfig;

    /**
     * DualStorageAdapter constructor.
     * @param AbstractAdapter $remoteStorage
     * @param AbstractAdapter $localStorage
     * @param Config $localConfig
     */
    public function __construct(AbstractAdapter $remoteStorage ,AbstractAdapter $localStorage, Config $localConfig)
    {
        $this->remoteStorage = $remoteStorage;
        $this->localStorage  = $localStorage;
        $this->localConfig   = $localConfig;
    }
    /**
     * return remote storage adapter
     * @return AbstractAdapter
     */
    public function getRemoteStorage() {
        return $this->remoteStorage;
    }
    /**
     * return local storage adapter
     * @return AbstractAdapter
     */
    public function getLocalStorage() {
        return $this->localStorage;
    }
    /**
     * return local storage config
     * @return Config
     */
    public function getLocalConfig() {
        return $this->localConfig;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->callWithFallback('has' , [$path]);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        if(($result = $this->localStorage->read($path)) !== false) {
            return $result;
        }
        $result = $this->remoteStorage->read($path);
        if($result !== false) {
            $this->localStorage->write($path , $result['contents'] , $this->localConfig);
        }
        return $result;
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        
        if(($result = $this->localStorage->readStream($path)) !== false) {
            return $result;
        }
        
        $result = $this->remoteStorage->readStream($path);
        if($result !== false) {
            $this->localStorage->writeStream($path , $result['stream'] , $this->localConfig);
        }
        return $result;
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->remoteStorage->listContents($directory , $recursive);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        return $this->callWithFallback('getMetadata' , [$path]);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->callWithFallback('getSize' , [$path]);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->callWithFallback('getMimetype' , [$path]);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->callWithFallback('getTimestamp' , [$path]);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return $this->callWithFallback('getVisibility' , [$path]);
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->callOnBoth('write' , [$path, $contents, $config]);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $localResource = &$resource;
        $this->localStorage->writeStream($path, $localResource, $config);
        return $this->remoteStorage->writeStream($path, $resource, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->callOnBoth('update' , [$path, $contents, $config]);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path,  $resource, Config $config)
    {
        return $this->callOnBoth('updateStream' , [$path, $resource, $config]);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        return $this->callOnBoth('rename' , [$path, $newpath]);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        return $this->callOnBoth('rename' , [$path, $newpath]);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return $this->callOnBoth('delete' , [$path]);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        return $this->callOnBoth('deleteDir' , [$dirname]);
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        return $this->callOnBoth('createDir' , [$dirname , $config]);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        return $this->callOnBoth('setVisibility' , [$path, $visibility]);
    }

    /**
     * Call a method on local.
     * if local return false, try on remote
     *
     * @param string $method
     * @param array $args method arguments
     *
     * @return mixed
     */
    protected function callWithFallback($method, array $args = [])
    {
 
        try  {
            $result = call_user_func_array([$this->localStorage , $method] , $args);
        } catch (\Exception $e) {
            $result = false;
        }
        if ($result !== false) {
            return $result;
        }
        return call_user_func_array([$this->remoteStorage , $method] , $args);

    }

    /**
     * call method on both storage
     * return remote result
     * @param $method
     * @param array $args
     * @return mixed
     */
    protected function callOnBoth($method, array $args = []) {

        call_user_func_array([$this->localStorage , $method] , $args);
        return call_user_func_array([$this->remoteStorage , $method] , $args);
    }

}