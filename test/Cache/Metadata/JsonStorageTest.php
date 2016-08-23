<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\libFlysystemFilecache\test\Cache\Metadata;

use oat\flysystem\Adapter\Cache\Metadata\JsonStorage;
use oat\tao\test\TaoPhpUnitTestRunner;

/**
 * Description of JsonStorageTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class JsonStorageTest extends TaoPhpUnitTestRunner 
{
   /**
     * @var JsonStorage
     */
    protected $instance;
    
    public function setUp() {
        $this->instance = new JsonStorage();
    }
    
    public function memoryProvider() {
        
        return 
        [
            [[] , 'file.txt' , 'mimetype' , false],
            [['file.txt' => ['mimetype' => 'text/plain']] , 'file.txt' , 'mimetype' , 'text/plain'],
            [['file.txt' => ['mimetype' => 'text/plain']] , 'file.txt' , null , ['mimetype' => 'text/plain']],
        ];
        
    }
    /**
     * @dataProvider memoryProvider
     * @param array $memoryData
     * @param string $path
     * @param string $key
     * @param mixed $expected
     */
    public function testGetFromMemory($memoryData , $path  , $key , $expected) {
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache', $memoryData);
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance, 'getFromMemory', [$path  , $key]));
    }
    
    public function testParseData() {
       
        $path = 'test.txt';
        
        $fitxureData = 
                [
                    'path' => $path,
                    'size' => 128,
                    'mimetype' => 'text/plain',
                    'content'  => 'test',
                    'timestamp'  => '11545121851151',
                    'test'       => 'test'
                ];
        
        $expected = [
                    'size' => 128,
                    'mimetype' => 'text/plain',
                    'timestamp'  => '11545121851151',
                ];
        
        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'parseData', [$fitxureData]));
        
    }
    
    public function setToMemoryProvider() {
        
        return 
        [
            ['file.txt' , 'mimetype' , 'text/plain' ,['mimetype' => 'text/plain']],
            ['file.txt' , 'size' , 128 , ['size' => '128']],
            ['file.txt' ,  null  , ['mimetype' => 'text/plain' , 'size' => '128' ] ,['mimetype' => 'text/plain' , 'size' => '128']],
        ];
        
    }
    /**
     * @dataProvider setToMemoryProvider
     * @param string $path
     * @param string $key
     * @param mixed $data
     * @param mixed $expected
     */
    public function testSetToMemory($path  , $key , $data , $expected) {
         $this->assertSame($this->instance , $this->invokeProtectedMethod($this->instance, 'setToMemory', [$path , $data , $key ]));
         $this->assertSame($expected ,$this->getInaccessibleProperty($this->instance, 'memoryCache'));
    }
    
    public function testGetCachePath() {
        
        $fixturePath = 'test/var/file.txt';
        $expected    = 'test/var/file.txt.json';
        
        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'getCachePath', [$fixturePath]));
    }
    
    public function testCopy() {
        
        \org\bovigo\vfs\vfsStream::setup('var' , 777, ['test']);
        $path    = \org\bovigo\vfs\vfsStream::url('var/test/file.txt');
        $newPath = \org\bovigo\vfs\vfsStream::url('var/test/file.txt');
        
        $cachePath    = \org\bovigo\vfs\vfsStream::url('var/test/.file.txt.json');
        $cacheNewPath = \org\bovigo\vfs\vfsStream::url('var/test/.file.txt.json');
        
        file_put_contents($cachePath, '{"test" : "content"}');
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , [$path => ['mimetype' => 'text/plain']]);
        
        $this->assertSame($this->instance , $this->instance->copy($cachePath, $cacheNewPath));
        $this->assertFileEquals($cachePath, $cacheNewPath);
        
        $this->assertSame([$cacheNewPath => ['mimetype' => 'text/plain']], $this->getInaccessibleProperty($this->instance, 'memoryCache'));
    }
    
    
    public function testDelete() {
        
        \org\bovigo\vfs\vfsStream::setup('var' , 777, ['test']);
        
        $path    = \org\bovigo\vfs\vfsStream::url('var/test/file.txt');
        $cachePath    = \org\bovigo\vfs\vfsStream::url('var/test/.file.txt.json');
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , [$path => ['mimetype' => 'text/plain']]);
        
        file_put_contents($cachePath, '{"test" : "content"}');
        
        $this->assertSame($this->instance , $this->instance->delete($cachePath));
        $this->assertFalse(file_exists($cachePath));
        
    }
    
    public function testRename() {
        
        \org\bovigo\vfs\vfsStream::setup('var' , 777, ['test']);
        $path    = \org\bovigo\vfs\vfsStream::url('var/test/file.txt');
        $newPath = \org\bovigo\vfs\vfsStream::url('var/test/file.txt');
        
        $cachePath    = \org\bovigo\vfs\vfsStream::url('var/test/.file.txt.json');
        $cacheNewPath = \org\bovigo\vfs\vfsStream::url('var/test/.file.txt.json');
        
        file_put_contents($cachePath, '{"test" : "content"}');
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , [$path => ['mimetype' => 'text/plain']]);
        
        $this->assertSame($this->instance , $this->instance->copy($cachePath, $cacheNewPath));
        $this->assertFileExists( $cacheNewPath);
        $this->assertFalse(file_exists($cachePath));
        
        $this->assertSame([$cacheNewPath => ['mimetype' => 'text/plain']], $this->getInaccessibleProperty($this->instance, 'memoryCache'));
        
    }
    
    public function testGet() {
        
        
        
    }
}
