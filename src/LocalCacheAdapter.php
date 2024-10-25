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
 * Copyright (c) 2016-2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\flysystem\Adapter;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Class LocalCacheAdapter
 * use two storage, a remote and a local
 * local has priority on read operation
 *
 * @package oat\flysystem\Adapter
 */
class LocalCacheAdapter implements FilesystemAdapter
{
    /**
     * remote flysystem adapter
     * @var FilesystemOperator
     */
    protected $remoteStorage;

    /**
     * local flysystem adapter
     * @var FilesystemOperator
     */
    protected $localStorage;

    /**
     * true if local cache must to be write immediatly
     * if false, cache is writing on destructor
     *
     * Only implemented for read operation
     *
     * @var boolean
     */
    protected $synchronous;

    /**
     * list of required local config entry
     * @var array
     */
    protected $requiredConfig = [
        'mimetype' => 'getMimetype',
        'size' => 'getSize',
        'visibility' => 'getVisibility',
    ];

    /**
     * Whether or not caching results of method listContents.
     * @var array
     */
    protected $cacheListContents = false;

    /**
     * Whether or not caching results of method has when directories are involved.
     * @var bool
     */
    protected $cacheHasDirectory = false;


    /**
     * array to store local file to write on destructor
     * @var array
     */
    protected $deferedSave = [];

    /**
     * DualStorageAdapter constructor.
     * @param FilesystemAdapter $remoteStorage
     * @param FilesystemAdapter $localStorage
     * @param boolean $synchronous true if local cache must to be write immediatly
     */
    public function __construct(
        FilesystemAdapter $remoteStorage,
        FilesystemAdapter $localStorage,
        $synchronous = true
    ) {
        $this->remoteStorage = $remoteStorage;
        $this->localStorage = $localStorage;
        $this->synchronous = boolval($synchronous);
    }

    /**
     * return remote storage adapter
     * @return FilesystemOperator
     */
    public function getRemoteStorage()
    {
        return $this->remoteStorage;
    }

    /**
     * return local storage adapter
     * @return FilesystemOperator
     */
    public function getLocalStorage()
    {
        return $this->localStorage;
    }

    /**
     * return autosave value
     * @return boolean
     */
    public function getSynchronous()
    {
        return $this->synchronous;
    }

    /**
     * change auto save value
     * @param boolean $synchronous
     */
    public function setSynchronous($synchronous)
    {
        $this->synchronous = boolval($synchronous);
        return $this;
    }

    /**
     * return cacheListContents value
     * @return boolean
     */
    public function getCacheListContents()
    {
        return $this->cacheListContents;
    }

    /**
     * change cacheListContents value
     * @param boolean $cacheListContents
     */
    public function setCacheListContents($cacheListContents)
    {
        $this->cacheListContents = $cacheListContents;
    }

    /**
     * change chacheHasDirectory value
     * @param $cacheHasDirectory
     */
    public function setCacheHasDirectory($cacheHasDirectory)
    {
        $this->cacheHasDirectory = $cacheHasDirectory;
    }

    /**
     * return cacheHasDirectory value
     * @return bool
     */
    public function getCacheHasDirectory()
    {
        return $this->cacheHasDirectory;
    }

    public function fileExists(string $path): bool
    {
        return $this->callWithFallback('fileExists', [$path]);
    }

    /**
     * Check whether a directory exists.
     */
    public function directoryExists(string $path): bool
    {
        if ($this->getCacheHasDirectory() === true && $this->isPathDir($path)) {
            $cachePath = $this->getHasDirectoryCacheExpectedPath($path);

            if ($this->localStorage->directoryExists($cachePath) && ($data = $this->localStorage->read($cachePath)) !== false) {
                // In cache, let's decode data.
                return json_decode($data['contents']);
            } else {
                // Not in cache, cache it!
                $directoryExistsVal = $this->callWithFallback('directoryExists', [$path]);

                $this->localStorage->write(
                    $cachePath,
                    json_encode($directoryExistsVal),
                    new Config()
                );

                return $directoryExistsVal;
            }
        } else {
            return $this->callWithFallback('directoryExists', [$path]);
        }
    }

    private function isPathDir($path)
    {
        return preg_match('/.+\..+$/', $path) === 0;
    }

    /**
     * Read a file.
     */
    public function read(string $path): string
    {
        if (($this->localStorage->fileExists($path)) !== false) {
            return $this->localStorage->read($path);
        }
        $result = $this->remoteStorage->read($path);
        if ($result !== '') {
            if ($this->synchronous) {
                $config = $this->setConfigFromResult($this->transformResultToConfigArray($path, $result));
                $this->localStorage->write($path, $result['contents'] ?? $result, $config ?? new Config());
            } elseif ($result !== false) {
                $this->deferedSave[] = $this->transformResultToConfigArray($path, $result);
            }
        }
        return $result;
    }

    /**
     * Read a file as a stream.
     */
    public function readStream(string $path)
    {
        if ($this->localStorage->fileExists($path)) {
            $result = $this->localStorage->readStream($path);
            if (is_resource($result['stream'] ?? $result)) {
                return $result['stream'] ?? $result;
            }
        }
        $result = $this->remoteStorage->readStream($path);
        if (is_resource($result)) {
            if ($this->synchronous) {
                $resource = $result['stream'] ?? $result;
                $config = $this->setConfigFromResult($this->transformResultToConfigArray($path, $result));
                $this->localStorage->writeStream($path, $resource, $config);
                $result = $this->localStorage->readStream($path);
                $result = $result['stream'] ?? $result;
            } elseif (is_resource($result)) {
                $this->deferedSave[] = $this->transformResultToConfigArray($path, $result);
            }
        }
        return $result;
    }

    /**
     * List contents of a directory.
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $contentList = [];

        if ($this->getCacheListContents() === false) {
            // No caching for listContents method calls.
            $contentList = $this->remoteStorage->listContents($path, $deep);
        } else {
            // Caching enabled for listContents method calls.
            $expectedPath = $this->getListContentsCacheExpectedPath($path, $deep);

            if (
                $this->localStorage->fileExists($expectedPath)
                && ($data = $this->localStorage->read($expectedPath)) !== false
            ) {
                // In cache.
                $contentList = json_decode($data['contents'], true);
            } else {
                // Not in cache or could not be read.
                $contentList = $this->remoteStorage->listContents($path, $deep);
                $this->localStorage->write(
                    $expectedPath,
                    json_encode($contentList),
                    new Config()
                );
            }
        }

        return $contentList;
    }

    /**
     * Get List Contents Expected Cache Path
     *
     * Provides the final path where to find cached data about a given listContents call.
     *
     * @param string $directory
     * @param boolean $recursive
     * @return string
     */
    protected function getListContentsCacheExpectedPath($directory, $recursive)
    {
        $key = $this->buildCacheKey($this->localStorage->getPathPrefix() . $directory . strval($recursive));
        $expectedPath = ".oat-lib-flysystem-cache/list-contents-cache/${key}.json";

        return $expectedPath;
    }

    protected function getHasDirectoryCacheExpectedPath($path)
    {
        $key = $this->buildCacheKey($this->localStorage->getPathPrefix() . $path);
        $expectedPath = ".oat-lib-flysystem-cache/has-directory-cache/${key}.json";

        return $expectedPath;
    }

    private function buildCacheKey($origin)
    {
        $key = md5($origin);

        // Add some directory levels to not overload a single filesystem level.
        for ($i = 1; $i < 6; $i += 2) {
            $key = substr_replace($key, '/', $i, 0);
        }

        return $key;
    }

    /**
     * Get size of a file
     */
    public function fileSize(string $path): FileAttributes
    {
        return $this->callWithFallback('fileSize', [$path]);
    }

    /**
     * Get the mimetype of a file.
     */
    public function mimeType(string $path): FileAttributes
    {
        return $this->callWithFallback('mimeType', [$path]);
    }

    /**
     * Get the timestamp of a file.
     */
    public function lastModified(string $path): FileAttributes
    {
        return $this->callWithFallback('lastModified', [$path]);
    }

    /**
     * Get the visibility of a file.
     */
    public function visibility(string $path): FileAttributes
    {
        return $this->callWithFallback('visibility', [$path]);
    }

    /**
     * Write a new or existing file.
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->callOnBoth('write', [$path, $contents, $config]);
    }

    /**
     * Write a new or existing file using a stream.
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->remoteStorage->writeStream($path, $contents, $config);
        $this->localStorage->writeStream($path, $this->initStream($contents), $config);
    }

    /**
     * Move a file.
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $this->callOnBoth('move', [$source, $destination, $config]);
    }

    /**
     * Copy a file.
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $this->callOnBoth('copy', [$source, $destination, $config]);
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): void
    {
        $this->callOnBoth('delete', [$path]);
    }

    /**
     * Delete a directory.
     */
    public function deleteDirectory(string $path): void
    {
        $this->callOnBoth('deleteDirectory', [$path]);
    }

    /**
     * Create a directory.
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->callOnBoth('createDirectory', [$path, $config]);
    }

    /**
     * Set the visibility for a file.
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->callOnBoth('setVisibility', [$path, $visibility]);
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
        try {
            $result = call_user_func_array([$this->localStorage, $method], $args);
        } catch (\Exception $e) {
            $result = false;
        }
        if ($result !== false) {
            return $result;
        }
        return call_user_func_array([$this->remoteStorage, $method], $args);
    }

    protected function initStream($resource)
    {
        rewind($resource);
        return $resource;
    }

    /**
     * call method on both storage
     * return remote result
     * @param $method
     * @param array $args
     * @return mixed
     */
    protected function callOnBoth($method, array $args = [])
    {
        call_user_func_array([$this->localStorage, $method], $args);
        return call_user_func_array([$this->remoteStorage, $method], $args);
    }

    /**
     * set local config from remote read Result or
     * from file metadata has allback
     * @param array $result
     * @return Config
     */
    protected function setConfigFromResult(array $result)
    {
        return new Config($result);
    }

    protected function transformResultToConfigArray(string $path, $data): array
    {
        $result = [
            'path' => $path,
        ];

        if (is_string($data)) {
            $result['contents'] = $data;
        } elseif (is_resource($data))  {
            $result['stream'] = $data;
        }

        return is_array($data)
            ? $data
            : $result;
    }

    /**
     * do deferred write operations
     */
    public function __destruct()
    {
        foreach ($this->deferedSave as $index => $write) {
            $config = $this->setConfigFromResult($this->transformResultToConfigArray($index, $write));
            if (array_key_exists('stream', $write) && is_resource($write['stream'])) {
                $this->localStorage->writeStream($write['path'], $this->initStream($write['stream']), $config);
            } elseif (array_key_exists('contents', $write)) {
                $this->localStorage->write($write['path'], $write['contents'], $config);
            }
            unset($this->deferedSave[$index]);
        }
    }
}
