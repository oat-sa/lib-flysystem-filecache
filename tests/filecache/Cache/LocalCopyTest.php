<?php

use oat\flysystem\Adapter\Cache\LocalCopy;
use oat\tao\test\TaoPhpUnitTestRunner;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace oat\libFlysystemFilecache\test\Cache;
/**
 * Description of LocalCopyTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class LocalCopyTest extends TaoPhpUnitTestRunner
{
    /**
     * @var LocalCopy
     */
    protected $instance;
    
    public function setUp() {
        $this->instance = new LocalCopy();
    }
    
}
