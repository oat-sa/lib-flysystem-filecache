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

namespace oat\flysystem\test\filecache\cache\metadata;

/**
 * Description of AbstractFileStorageTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class AbstractFileStorageTest  extends \oat\flysystem\test\helper\PhpUnitTestHelper 
{
    
    public function getFromMemoryProvider() {
        return 
        [
            ['root/var/test.html' , 'mimetype' , 'text/html'],
            ['root/var/test.html' , 'timestamp' , false],
            ['root/tmp/test.html' , null , false],
            ['root/var/test.html' , null ,  [
                'mimetype' => 'text/html',
                'size'     => 1024,
            ]],
        ];
    }

        /**
     * @dataProvider getFromMemoryProvider
     * @param string $path
     * @param string|null $key
     * @param mixed $expected
     */
    public function testGetFromMemory($path , $key , $expected) {
        
        $fixtureMemory = [
            'root/var/test.html' => 
            [
                'mimetype' => 'text/html',
                'size'     => 1024,
            ]
        ];
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                []);
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache', $fixtureMemory);
         
        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'getFromMemory' , [$path , $key]));
        
    }
    
    public function setToMemoryProvider() {
        $now = time();
        return 
        [
            ['root/var/test.html' , $now , 'timestamp' , ['mimetype' => 'text/html','size'     => 1024, 'timestamp' => $now]],
            ['root/var/test.html' ,  ['custom' => 'test' , 'mimetype' => 'text/html','size'     => 1024, 'timestamp' => $now] , null , ['custom' => 'test' , 'mimetype' => 'text/html','size'     => 1024, 'timestamp' => $now]],
        ];
    }
    
    /**
     * @dataProvider setToMemoryProvider
     * @param string $path
     * @param mixed $value
     * @param string $key
     * @param array $expected
     */
    public function testSetToMemory($path , $value , $key , $expected) {
        
        $fixtureMemory = [
            'root/var/test.html' => 
            [
                'mimetype' => 'text/html',
                'size'     => 1024,
            ]
        ];
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                []);
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache', $fixtureMemory);
        $this->assertSame($this->instance, $this->invokeProtectedMethod($this->instance, 'setToMemory' , [$path , $value , $key ]));
        
        $memory = $this->getInaccessibleProperty($this->instance, 'memoryCache');
        $this->assertSame($expected , $memory[$path] );
    }
    
    public function testGetCachePath() {
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,[]);
        
        $fixtureExtension = 'txt';
        $fixtureDirectory = '.meta';

        $fixturePath = 'root/var/test.html';
        $expected    = 'root/var/' . $fixtureDirectory . '/test.html.' . $fixtureExtension;

        $this->setInaccessibleProperty($this->instance, 'cacheDirectoryName', $fixtureDirectory);
        $this->setInaccessibleProperty($this->instance, 'cacheExtension', $fixtureExtension);

        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'getCachePath' , [$fixturePath]));
        
    }
    
    public function testCopy() {
        
        \org\bovigo\vfs\vfsStream::setup('var');
        
        $originalFile = \org\bovigo\vfs\vfsStream::url('var/test1.txt');
        $copyFile     = \org\bovigo\vfs\vfsStream::url('var/test2.txt');
        
        $cacheFile        = \org\bovigo\vfs\vfsStream::url('var/.test1.txt.json');
        $cacheFileCopy    = \org\bovigo\vfs\vfsStream::url('var/.test2.txt.json');
        
        $data = 
                [
                    $originalFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ]
                ];
        
        $expectedMemory = [
                    $originalFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ],
                    $copyFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ],
                ];
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,['getCachePath']);
        
        $this->instance->expects($this->exactly(2))
                ->method('getCachePath')
                ->withConsecutive(
                        [$originalFile],
                        [$copyFile]
                        )
                ->willReturnOnConsecutiveCalls(
                        $cacheFile,
                        $cacheFileCopy
                        );
        
        file_put_contents($cacheFile , json_encode($data));
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , $data);
        
        $this->assertSame($this->instance, $this->instance->copy($originalFile , $copyFile));
        $this->assertSame($expectedMemory, $this->getInaccessibleProperty($this->instance, 'memoryCache'));
        
        $this->assertFileEquals($cacheFile , $cacheFileCopy);
        
    }
    
    public function testRename() {
        $originalFile = \org\bovigo\vfs\vfsStream::url('var/test1.txt');
        $copyFile     = \org\bovigo\vfs\vfsStream::url('var/test2.txt');
        
        $cacheFile        = \org\bovigo\vfs\vfsStream::url('var/.test1.txt.json');
        $cacheFileCopy    = \org\bovigo\vfs\vfsStream::url('var/.test2.txt.json');
        
        $data = 
                [
                    $originalFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ]
                ];
        
        $expectedMemory = [
                    $copyFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ],
                ];
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,['getCachePath']);
        
        $this->instance->expects($this->exactly(2))
                ->method('getCachePath')
                ->withConsecutive(
                        [$originalFile],
                        [$copyFile]
                        )
                ->willReturnOnConsecutiveCalls(
                        $cacheFile,
                        $cacheFileCopy
                        );
        
        file_put_contents($cacheFile , json_encode($data));
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , $data);
        
        $this->assertSame($this->instance, $this->instance->rename($originalFile , $copyFile));
        $this->assertSame($expectedMemory, $this->getInaccessibleProperty($this->instance, 'memoryCache'));
        
        $this->assertSame(json_encode($data) , file_get_contents($cacheFileCopy));
        $this->assertFalse(file_exists($cacheFile));
    }

    public function testDelete() {
        $originalFile = \org\bovigo\vfs\vfsStream::url('var/test1.txt');
        
        $cacheFile        = \org\bovigo\vfs\vfsStream::url('var/.test1.txt.json');
        
        $data = 
                [
                    $originalFile => 
                        [
                            'mimetype' => 'text/plain',
                            'size'     => 1024,
                        ]
                ];
        
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,['getCachePath']);
        
        $this->instance->expects($this->once())
                ->method('getCachePath')
                ->with(
                        $originalFile
                        )
                ->willReturn(
                        $cacheFile
                        );
        
        file_put_contents($cacheFile , json_encode($data));
        
        $this->setInaccessibleProperty($this->instance, 'memoryCache' , $data);
        
        $this->assertSame($this->instance, $this->instance->delete($originalFile));
        $this->assertEmpty($this->getInaccessibleProperty($this->instance, 'memoryCache'));

        $this->assertFalse(file_exists($cacheFile));
    }
    
    public function providerGet_Memory() {
        
        return 
        [
            ['test.txt' , 'mimetype' , 'plain/text' , ['mimetype' => 'plain/text']],
            ['test.txt' , 'mimetype' , false , false],
        ];
        
    }

     /**
     * @dataProvider providerGet_Memory
     * @param string $path
     * @param string $key
     * @param string $value
     * @param mixed $expected
     */
    public function testGet_Memory($path , $key , $value , $expected) {
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['getFromMemory' , 'getCachePath' , 'readFile']);
        
        $this->instance->expects($this->once())
                ->method('getFromMemory')
                ->with($path , $key)
                ->willReturn($value);
        
        $this->assertSame($expected ,  $this->instance->get($path , $key));
        
    }
    
    public function providerGet_File() {
        
        return 
        [
            ['test.txt' , 'mimetype' , ['mimetype' => 'plain/text'] , ['mimetype' => 'plain/text']],
            ['test.txt' , 'mimetype' , ['size' => 1024] , false],
        ];
        
    }
    /**
     * @dataProvider providerGet_File
     * @param string $path
     * @param string $key
     * @param string $value
     * @param mixed $expected
     */
    public function testGet_File($path , $key , $value , $expected) {

        \org\bovigo\vfs\vfsStream::setup('var');
        
        $fixtureCacheFile = \org\bovigo\vfs\vfsStream::url('var/test.meta.json'); 
        file_put_contents($fixtureCacheFile , '');
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['getFromMemory' , 'getCachePath' , 'readFile']);
        
        $this->instance->expects($this->once())
                ->method('getFromMemory')
                ->with($path , $key)
                ->willReturn(false);
        
        $this->instance->expects($this->once())
                ->method('getCachePath')
                ->with($path)
                ->willReturn($fixtureCacheFile);
        
        $this->instance->expects($this->once())
                ->method('readFile')
                ->with($fixtureCacheFile)
                ->willReturn($value);
        
        $this->assertSame($expected ,  $this->instance->get($path , $key));
        
    }

    public function testSet() {
        
        $fixturePath   = 'test.txt';
        $fixtureCache  = '.test.txt.json';
        $fixtureKey    = 'mimetype'; 
        $fixtureValue  = 'application/csv';
        
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['load' , 'getFromMemory' , 'getCachePath' , 'writeFile' , 'setToMemory']);
        
        $this->instance->expects($this->once())
                ->method('load')
                ->with($fixturePath)
                ->willReturn($this->instance);
        
        $this->instance->expects($this->once())
                ->method('getCachePath')
                ->with($fixturePath)
                ->willReturn($fixtureCache);
        
        $this->instance->expects($this->once())
                ->method('getFromMemory')
                ->with($fixturePath)
                ->willReturn(false);
        
        $this->instance->expects($this->once())
                ->method('writeFile')
                ->with($fixtureCache , [$fixtureKey => $fixtureValue])
                ->willReturn($this->instance);
        
        $this->instance->expects($this->once())
                ->method('setToMemory')
                ->with($fixturePath , [$fixtureKey => $fixtureValue])
                ->willReturn($this->instance);
        
        $this->assertSame($this->instance ,  $this->instance->set($fixturePath , $fixtureKey , $fixtureValue));
        
    }
    
    public function testLoad_Memory() {
        $fixturePath   = 'test.txt';
        $fixtureValue  = ['mimetype' => 'application/csv']; 
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['getFromMemory']);
        
        $this->instance->expects($this->once())
                ->method('getFromMemory')
                ->with($fixturePath)
                ->willReturn($fixtureValue);

        $this->assertSame($fixtureValue ,  $this->instance->load($fixturePath));
        
    }

    public function testLoad_File() {
        
        $fixturePath   = 'test.txt';

        $fixtureKey    = 'mimetype'; 
        $fixtureValue  = 'application/csv';
        
        \org\bovigo\vfs\vfsStream::setup('var');
        
        $fixtureCacheFile = \org\bovigo\vfs\vfsStream::url('var/test.meta.json'); 
        file_put_contents($fixtureCacheFile , '');
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['getFromMemory' , 'getCachePath' , 'readFile' , 'setToMemory']);
        
        $this->instance->expects($this->once())
                ->method('getCachePath')
                ->with($fixturePath)
                ->willReturn($fixtureCacheFile);
        
        $this->instance->expects($this->once())
                ->method('getFromMemory')
                ->with($fixturePath)
                ->willReturn(false);
        
        $this->instance->expects($this->once())
                ->method('readFile')
                ->with($fixtureCacheFile )
                ->willReturn([$fixtureKey => $fixtureValue]);
        
        $this->instance->expects($this->once())
                ->method('setToMemory')
                ->with($fixturePath , [$fixtureKey => $fixtureValue])
                ->willReturn($this->instance);
        
        $this->assertSame([$fixtureKey => $fixtureValue] ,  $this->instance->load($fixturePath));
        
    }
    
    public function testSave() {
        
        $now = time();
        $size = rand(1024, 4096);
        
        $fixturePath   = 'test.txt';
        $fixtureCache  = '.test.txt.json';
        
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
        
        $this->instance = $this->getMockForAbstractClass(\oat\flysystem\Adapter\Cache\Metadata\AbstractFileStorage::class, [], '', false , false, true ,
                ['parseData' , 'getCachePath' , 'writeFile' , 'setToMemory']);
        
        
        $this->instance->expects($this->once())
                ->method('getCachePath')
                ->with($fixturePath)
                ->willReturn($fixtureCache);
        
        $this->instance->expects($this->once())
                ->method('parseData')
                ->with($Config)
                ->willReturn($meta);
        
        $this->instance->expects($this->once())
                ->method('writeFile')
                ->with($fixtureCache , $meta)
                ->willReturn($this->instance);
        
        $this->instance->expects($this->once())
                ->method('setToMemory')
                ->with($fixturePath , $meta)
                ->willReturn($this->instance);
        
        $this->assertSame($this->instance ,  $this->instance->save($fixturePath , $Config));
        
    }

    public function tearDown() {
        $this->instance = null;
    }
}
