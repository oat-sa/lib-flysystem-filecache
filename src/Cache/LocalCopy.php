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
use League\Flysystem\Adapter\AbstractAdapter;
/**
 * Description of LocalCopy
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class LocalCopy extends \League\Flysystem\Adapter\Local 
{
    
    protected $metadata;


    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param int    $linkHandling
     * @param array  $permissions
     */
    public function __construct($root, StorageInterface $metadata , $writeFlags = LOCK_EX, $linkHandling = self::DISALLOW_LINKS, array $permissions = [])
    {
        $this->metadata = $metadata;
        parent::__construct($root, $writeFlags, $linkHandling, $permissions);
    }
    
    public function copy($path, $newpath) {
        $this->metadata->copy($path, $newpath);
        return parent::copy($path, $newpath);
    }

    public function getMetadata($path) {
        if(($result = $this->metadata->load($path)) !== false) {
            return $result;
        }
        return parent::getMetadata($path);
    }

    public function getMimetype($path) {
        if(($result = $this->metadata->get($path, 'mimetype')) !== false) {
            return $result;
        }
        parent::getMimetype($path);
    }

    public function getSize($path) {
        if(($result = $this->metadata->get($path, 'size')) !== false) {
            return $result;
        }
        return parent::getSize($path);
    }

    public function getTimestamp($path) {
        if(($result = $this->metadata->get($path, 'timestamp')) !== false) {
            return $result;
        }
        return parent::getTimestamp($path);
    }

    public function getVisibility($path) {
        if(($result = $this->metadata->get($path, 'visibility')) !== false) {
            return $result;
        }
        return parent::getVisibility($path);
    }

    public function rename($path, $newpath) {
        $this->metadata->rename($path, $newpath);
        return parent::rename($path, $newpath);
    }

    public function setVisibility($path, $visibility) {
        $this->metadata->set($path, 'visibility' , $visibility);
        return parent::setVisibility($path, $visibility);
    }

    public function update($path, $contents, \League\Flysystem\Config $config) {
        $this->metadata->save($path, $config);
        return parent::update($path, $contents, $config);
    }

    public function updateStream($path, $resource, \League\Flysystem\Config $config) {
        $this->metadata->save($path, $config);
        return parent::updateStream($path, $resource, $config);
    }

    public function write($path, $contents, \League\Flysystem\Config $config) {
        $file = parent::write($path, $contents, $config);
        if($file !== false) {
            $this->metadata->save($path, $config);
        }
        return $file;
    }

    public function writeStream($path, $resource, \League\Flysystem\Config $config) {
        $file = parent::writeStream($path, $resource, $config);
        if($file !== false) {
            $this->metadata->save($path, $config);
        }
        return $file;
    }

    
}
