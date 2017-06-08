<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\test\filecache\cache\metadata;

/**
 * Description of ApcuStorageTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class ApcuStorageTest extends \oat\flysystem\test\helper\PhpUnitTestHelper 
{
    
    public function setUp() {
        
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped(
              'The apcu extension is not available. Required to test ApcuStorage'
            );
            return;
        }
        if(!apcu_enabled()) {
            $this->markTestSkipped(
              'The apcu extension is not enable. Required to test ApcuStorage'
            );
            return;
        }
        
        $this->instance = new \oat\flysystem\Adapter\Cache\Metadata\ApcuStorage();
    }

    public function testCopy() {
        
        $fixturePath = 'test1.txt'; 
        
        $fixtureCopy = 'test2.txt';  
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        apcu_add($fixturePath, $meta);
        
        $this->assertSame($this->instance , $this->instance->copy($fixturePath ,$fixtureCopy));
        $this->assertSame(apcu_fetch($fixtureCopy) , apcu_fetch($fixturePath));
    }
    
    public function testRename() {
        
        $fixturePath = 'test1.txt'; 
        
        $fixtureCopy = 'test2.txt';  
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        apcu_add($fixturePath, $meta);
        
        $this->assertSame($this->instance , $this->instance->rename($fixturePath ,$fixtureCopy));
        $this->assertSame($meta , apcu_fetch($fixtureCopy));
        $this->assertFalse(apcu_exists($fixturePath));
    }
    
    public function testDelete() {
        
        $fixturePath = 'test1.txt'; 
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        apcu_add($fixturePath, $meta);
        
        $this->assertSame($this->instance , $this->instance->delete($fixturePath));
        $this->assertFalse(apcu_exists($fixturePath));
    }
    
    public function getProvider() {
        return 
        [
            ['test1.txt' , 'mimetype' , ['mimetype' => 'text/html']],
            ['test2.txt' , 'mimetype' , false],
            ['test1.txt' , 'toto' , false],
        ];
    }
    /**
     * @dataProvider getProvider
     * @param string $path
     * @param string $key
     * @param mixed $expected
     */
    public function testGet($path , $key , $expected) {
        $fixturePath = 'test1.txt'; 
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        apcu_add($fixturePath, $meta);
        
        $this->assertSame($expected , $this->instance->get($path , $key));
    }

    public function testLoad() {
        
        $fixturePath = 'test1.txt'; 
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        apcu_add($fixturePath, $meta);
        
        $this->assertSame($meta , $this->instance->load($fixturePath));
        
    }
    
    public function testSet() {
        $fixturePath = 'test1.txt'; 
        
        $now = time();
        $size = rand(1024, 4096);
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        $value = 'test';
        $key   = 'custom';
        
        $expected = array_merge($meta , [$key => $value]);
        
        apcu_add($fixturePath, $meta);
        $this->assertSame($this->instance , $this->instance->set($fixturePath , $key , $value ));
        $this->assertSame($expected, apcu_fetch($fixturePath));
    }
    
    public function testSave() {
        
        $this->instance = $this->getMock(\oat\flysystem\Adapter\Cache\Metadata\ApcuStorage::class , ['parseData']);
        
        $now = time();
        $size = rand(1024, 4096);
        
        $fixturePath   = 'test.txt';
        
        $fixtureSettings = [
            'custom'   => 'test',
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            'type'     => '',
            ];
        
        $meta = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        $Config = new \League\Flysystem\Config($fixtureSettings);
        
        $this->instance->expects($this->once())->method('parseData')
                ->with($Config)
                ->willReturn($meta);
        
        $this->assertSame($this->instance, $this->instance->save($fixturePath , $Config));
        $this->assertSame($meta, apcu_fetch($fixturePath));
        
    }
    
    public function tearDown() {
        $this->instance = null;
        apcu_clear_cache();
    }
}
