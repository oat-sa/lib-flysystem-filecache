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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\flysystem\Adapter;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Exception;

/**
 * Class LocalAdapter
 *
 * This class extends the usual League FlySystem Local Adapter. It simply brings a new feature, enabling
 * the client code to set whether or not trying multiple time to 'ensure' directories before writing/updating
 * data on the file system.
 *
 * By default, this adapter behaves as the League FlySystem one. To turn on multiple directory creation attempts,
 * use the following methods:
 *
 * * LocalAdapter::mustEnsureDirectoryWait()
 * * LocalAdapter::setEnsureDirectoryWaitAttempts()
 * * LocalAdapter::setEnsureDirectoryWaitTime()
 *
 * See bug https://github.com/thephpleague/flysystem/issues/690
 *
 * @see LocalAdapter::mustEnsureDirectoryWait()
 * @see LocalAdapter::setEnsureDirectoryWaitAttempts()
 * @see LocalAdapter::setEnsureDirectoryWaitTime()
 *
 * @package oat\flysystem\Adapter
 */
class LocalAdapter extends Local
{
    private $ensureDirectoryWait = false;

    private $ensureDirectoryWaitTime = 10;

    private $ensureDirectoryWaitAttempts = 3;

    /**
     * Must Ensure Directory Wait
     *
     * Wheter trying multiple time to 'ensure' a directory before writing data.
     *
     * @return bool
     */
    public function mustEnsureDirectoryWait()
    {
        return $this->ensureDirectoryWait;
    }

    /**
     * Set Ensure Directory Wait
     *
     * Set whether or not to try multiple time to 'ensure' a directory before writing data.
     *
     * @param bool $ensureDirectoryWait
     */
    public function setEnsureDirectoryWait($ensureDirectoryWait)
    {
        $this->ensureDirectoryWait = $ensureDirectoryWait;
    }

    /**
     * Set Ensure Directory Wait Time
     *
     * Set how much time (microseconds) waiting between two directory creation attempts
     * before writing data.
     *
     * @param integer $ensureDirectoryWaitTime A time in microseconds.
     */
    public function setEnsureDirectoryWaitTime($ensureDirectoryWaitTime)
    {
        $this->ensureDirectoryWaitTime = $ensureDirectoryWaitTime;
    }

    /**
     * Get Ensure Directory Wait Time
     *
     * Get how much time (microseconds) waiting between two directory creation attempts
     * before writing data.
     *
     * @return integer A time in microseconds.
     */
    public function getEnsureDirectoryWaitTime()
    {
        return $this->ensureDirectoryWaitTime;
    }

    /**
     * Set Ensure Directory Wait Attempts
     *
     * Set how much attempts must be performed to create a directory before writing data.
     *
     * @param integer $ensureDirectoryWaitAttempts
     */
    public function setEnsureDirectoryWaitAttempts($ensureDirectoryWaitAttempts)
    {
        if ($ensureDirectoryWaitAttempts <= 0) {
            $ensureDirectoryWaitAttempts = 1;
        }

        $this->ensureDirectoryWaitAttempts = $ensureDirectoryWaitAttempts;
    }

    /**
     * Get Ensure Directory Wait Attempts
     *
     * Get how much attempts must be performed to create a directory before writing data.
     *
     * @return integer
     */
    public function getEnsureDirectoryWaitAttempts()
    {
        return $this->ensureDirectoryWaitAttempts;
    }

    /**
     * Ensure the root directory exists.
     *
     * In case of 'ensureDirectoryWait' option is enabled, the implementation will try
     * to create the directory 'ensureDirectoryWaitAttempts' times before failing.
     *
     * @param string $root root directory path
     * @return void
     * @see LocalAdapter::mustEnsureDirectoryWait()
     * @see LocalAdapter::setEnsureDirectoryWaitAttempts()
     * @see LocalAdapter::setEnsureDirectoryWaitTime()
     *
     * @throws Exception in case the root directory can not be created
     */
    protected function ensureDirectory($root)
    {
        if ($this->mustEnsureDirectoryWait() === true) {

            if (!is_dir($root) || !is_writable($root)) {
                $waitTime = $this->getEnsureDirectoryWaitTime();
                $attempts = $this->getEnsureDirectoryWaitAttempts();
                $i = 0;

                while ($i < $attempts) {
                    $umask = umask(0);
                    @mkdir($root, $this->permissionMap['dir']['public'], true);
                    umask($umask);

                    if (is_dir($root) && is_writable($root)) {
                        break;
                    } else {
                        usleep($waitTime);
                    }

                    $i++;
                }

                if ($i >= $attempts) {
                    throw new Exception(sprintf('Impossible to create the root directory "%s" after %d attempts.', $root, $attempts));
                }
            }
        } else {
            parent::ensureDirectory($root);
        }
    }
}