<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 15/07/16
 * Time: 10:12
 */

namespace oat\flysystem\test\filecache;

use oat\flysystem\Adapter\LocalCacheAdapter;
use oat\flysystem\test\helper\PhpUnitTestHelper;

class LocalCacheAdapterTest extends PhpUnitTestHelper
{
    /**
     * @var LocalCacheAdapter
     */
    protected $instance;

    public function testConstruct() {

        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        
        $synchronous   = true;
        
        $this->instance = new LocalCacheAdapter($remoteMock , $localMock , $synchronous);
        
        $this->assertSame($remoteMock, $this->getInaccessibleProperty($this->instance, 'remoteStorage'));
        $this->assertSame($localMock, $this->getInaccessibleProperty($this->instance, 'localStorage'));
        $this->assertSame($synchronous, $this->getInaccessibleProperty($this->instance, 'synchronous'));

    }

    public function callWithFallbackProvider() {
        return
            [
                [
                    'has' , ['/path/test1'] , true, false
                ],
                [
                    'getTimestamp' , ['/path/test1'] , false, false
                ],
                [
                    'getMetadata' , ['/path/test1'] , false, true
                ],
            ];

    }

    /**
     * @dataProvider callWithFallbackProvider
     * @param string $method
     * @param array $args 
     * @param boolean $localResult
     * @param boolean $remoteResult
     */
    public function testCallWithFallback($method , $args , $localResult , $remoteResult) {

        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $localProphet->$method()->withArguments($args)->willReturn($localResult);
        $localMock    = $localProphet->reveal();

        $expected     = $localResult;

        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        if($localResult === false) {
            $remoteProphet->$method()->withArguments($args)->willReturn($remoteResult);
            $expected = $remoteResult;
        }
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance , 'callWithFallback' , [$method , $args]));

    }
    
    public function testGetters() {
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock);
        
        $this->assertSame($remoteMock, $this->instance->getRemoteStorage());
        $this->assertSame($localMock, $this->instance->getLocalStorage());
    }
    
    public function synchronousProvider() {
        return 
            [
                [true , true],
                [false , false],
                [0 , false],
                [1 , true],
                ['0' , false],
                ['1' , true],
            ];
    }
    /**
     * @dataProvider synchronousProvider
     * @param type $value
     * @param type $expected
     */
    public function testSetGetSynchronous($value , $expected) {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $this->assertSame($this->instance, $this->instance->setSynchronous($value));
        $this->assertSame($expected, $this->instance->getSynchronous());
    }
    
    public function callOnBothProvider() {
        return
            [
                [
                    'has' , ['/path/test1'] , true, false
                ],
                [
                    'getTimestamp' , ['/path/test1'] , false, false
                ],
                [
                    'getMetadata' , ['/path/test1'] , false, true
                ],
            ];

    }
    /**
     * @dataProvider callOnBothProvider
     * @param string $method
     * @param array $args 
     * @param boolean $localResult
     * @param boolean $remoteResult
     */
    public function testCallOnBoth($method , $args , $localResult , $remoteResult) {
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $localProphet->$method()->withArguments($args)->willReturn($localResult);
        $localMock    = $localProphet->reveal();

        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');

        $remoteProphet->$method()->withArguments($args)->willReturn($remoteResult);
        $expected = $remoteResult;
        
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance , 'callOnBoth' , [$method , $args]));
    }
    
    public function readProvider() {
        
        return 
        [
            ['test1.txt' , ['path' => 'test1.txt' ,'contents' => 'test1'], false                  , true  ,['path' => 'test1.txt', 'contents' => 'test1']],
            ['test2.txt' , false                  , ['path' => 'test2.txt' ,'contents' => 'test2'], true  ,['path' => 'test2.txt', 'contents' => 'test2']],
            ['test2.txt' , false                  , false                  , true  ,false],
            ['test1.txt' , ['path' => 'test1.txt' ,'contents' => 'test1'], false                  , false ,['path' => 'test1.txt', 'contents' => 'test1']],
            ['test2.txt' , false                  , ['path' => 'test2.txt' ,'contents' => 'test2'], false ,['path' => 'test2.txt', 'contents' => 'test2']],
            ['test2.txt' , false                  , false                  , false ,false],
        ];
        
    }

        /**
     * @dataProvider readProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param boolean $synchronous
     * @param mixed $expected
     */
    public function testRead($path , $localResult , $remoteResult ,$synchronous , $expected) {
        $localProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config        = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $this->instance = $this->getMock(
                'oat\flysystem\Adapter\LocalCacheAdapter' , 
                ['setConfigFromResult'],
                [],
                '',
                false
                );
        
        
        $localProphet->has($path)->willReturn($localResult);
        
        if($localResult === false) {
            $remoteProphet->read($path)->willReturn($remoteResult);
        } else {
            $localProphet->read($path)->willReturn($localResult);
        }
        
        $expectedSave = [];
        
        if($remoteResult !== false && $synchronous) {
            
            $this->instance->expects($this->once())->method('setConfigFromResult')
                ->with($remoteResult)->willReturn($config);
            
            $localProphet->write($path , $remoteResult['contents'] , $config)->willReturn($remoteResult);
            
                    
        } elseif($remoteResult !== false) {
            $expectedSave[] =  $remoteResult;
        }
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous', $synchronous);
        
        $this->assertSame($expected , $this->instance->read($path));
        $this->assertSame($expectedSave , $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave' , []);
    }
    
    public function testReadStreamLocal() {
        $localProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
       
        $fixturepath = "test.txt";
        
        $return  = [
            'path'     => 'test.txt',
            'stream'   => tmpfile(),
            'mimetype' => 'text/plain',
            ];
        $localProphet->has($fixturepath)->willReturn(true);
        $localProphet->readStream($fixturepath)->willReturn($return);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new LocalCacheAdapter($remoteMock , $localMock);
        $this->assertSame($return, $this->instance->readStream($fixturepath));
    }
    
    public function testReadStreamRemonteFalse() {
        $localProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
      
        
        $fixturepath = "test.txt";

        $localProphet->has($fixturepath)->willReturn(false);
        $remoteProphet->readStream($fixturepath)->willReturn(false);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new LocalCacheAdapter($remoteMock , $localMock);
        $this->assertFalse($this->instance->readStream($fixturepath));
    }
    
    public function testReadStreamRemonteSync() {
        $localProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config        = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $remoteResource = tmpfile();
        $copyResource   = tmpfile();
        $localResource = tmpfile();
        
        $returnRemote  = [
            'path'     => 'test.txt',
            'stream'   => $remoteResource,
            'mimetype' => 'text/plain',
            ];
        
        $returnLocal  = [
            'path'     => 'test.txt',
            'stream'   => $localResource,
            'mimetype' => 'text/plain',
            ];
        
        $fixturepath = "test.txt";

        $localProphet->has($fixturepath)->willReturn(false);
        $remoteProphet->readStream($fixturepath)->willReturn($returnRemote);
        
        $localProphet->writeStream($fixturepath , $copyResource ,  $config)->willReturn(['path' => $fixturepath]);
        $localProphet->readStream($fixturepath)->willReturn($returnLocal);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = $this->getMock(
                'oat\flysystem\Adapter\LocalCacheAdapter' , 
                ['setConfigFromResult' , '__destruct' , 'copyStream'],
                [],
                '',
                false
                );
        
        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous'  , true);
        
         $this->instance
                ->expects($this->once())
                ->method('copyStream')
                ->willReturn($copyResource);
        
        $this->instance
                ->expects($this->once())
                ->method('setConfigFromResult')
                ->with($returnRemote)
                ->willReturn($config);
        
        $this->assertSame( $returnLocal , $this->instance->readStream($fixturepath));
    }
    
    public function testReadStreamRemonteAsync() {
        $localProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config        = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $remoteResource = tmpfile();
        $localResource = tmpfile();
        
        $returnRemote  = [
            'path'     => 'test.txt',
            'stream'   => $remoteResource,
            'mimetype' => 'text/plain',
            ];
        
        $expected = [
            'path'     => 'test.txt',
            'stream'   => $localResource,
            'mimetype' => 'text/plain',
            ];
        
        $fixturepath = "test.txt";

        $localProphet->has($fixturepath)->willReturn(false);
        $remoteProphet->readStream($fixturepath)->willReturn($returnRemote);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = $this->getMock(
                'oat\flysystem\Adapter\LocalCacheAdapter' , 
                ['copyStream'  , '__destruct'],
                [],
                '',
                false
                );
        
        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage' , $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous'  , false);
        
        $this->instance
                ->expects($this->once())
                ->method('copyStream')
                ->willReturn($localResource);
        
        $this->assertSame( $returnRemote , $this->instance->readStream($fixturepath));
        $this->assertTrue(in_array($expected,  $this->getInaccessibleProperty($this->instance, 'deferedSave')));
    }
    
    public function testListContents() {
        
        $fixtureDirectory = '/tmp';
        $fixtureRecursive = false;
        
        $fixtureList = 
                [
                    'test1.txt',
                    'test2.txt',
                    'test3.txt',
                ];
                
        $localProphet   = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $localMock      = $localProphet->reveal();
        $remoteProphet->listContents($fixtureDirectory , $fixtureRecursive)->willReturn($fixtureList);
        $remoteMock     = $remoteProphet->reveal();
        
        $this->instance = new LocalCacheAdapter($remoteMock , $localMock , $config);
        $this->assertSame($fixtureList , $this->instance->listContents($fixtureDirectory , $fixtureRecursive));
    }
    
    public function testInitStream() {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $fixtureStream = tmpfile();
        fputs($fixtureStream, 'test1' . "\n" . 'test2');
        fread($fixtureStream, 10);
        $this->assertSame($fixtureStream, $this->invokeProtectedMethod($this->instance, 'initStream' , [$fixtureStream]));
        $this->assertSame(0, ftell($fixtureStream));
    }
    
    public function testWriteStream() {
        
        $file = tmpfile();
        $path = 'test1.txt';
        
        $copy = tmpfile();
        
        $returnLocal = [
            'path'   => $path,
            'stream' => $file,
            'local'
        ];
        
        $returnDist = [
            'path'   => $path,
            'stream' => $file,
            'remote'
        ];
        
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        $configCopy = $this->prophesize('League\Flysystem\Config')->reveal();
        
        
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');

        $remoteProphet->writeStream($path , $file , $config)->willReturn($returnDist);
        $localProphet->writeStream($path , $copy , $configCopy)->willReturn($returnLocal);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = $this->getMock(
                LocalCacheAdapter::class , 
                ['copyStream' , 'setConfigFromResult'],
                [$remoteMock , $localMock],
                '',
                true
                );
        
        $this->instance
                ->expects($this->once())
                ->method('copyStream')
                ->with($file)
                ->willReturn($copy);
        
        $this->instance
                ->expects($this->once())
                ->method('setConfigFromResult')
                ->with($returnDist)
                ->willReturn($configCopy);
        
        $this->assertSame($returnDist , $this->instance->writeStream($path , $file , $config));
    }
    
    public function testSetConfigFromResult() {
        
        $fixtureResult = 
                [
                    'path'      => 'test.txt',
                    'mimetype'  => 'text/plain',
                ];
        
        $requiredConfig = [
            'mimetype'   => 'getMimetype',
            'size'       => 'getSize',
            'timestamp'  => 'getTimestamp',
        ];
        
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        
        $remoteProphet->getVisibility('test.txt')->willReturn(['visibility' => 'public']);
        $remoteProphet->getSize('test.txt')->willReturn(['size'       => 180]);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $this->instance = $this->getMock(
                LocalCacheAdapter::class , 
                ['getPropertyFromRemote' , 'getConfig' ],
                [$remoteMock , $localMock],
                '',
                true
                );
        
        $this->instance
                ->expects($this->once())
                ->method('getConfig')
                ->willReturn($config);
        
        $this->instance
                ->expects($this->exactly(2))
                ->method('getPropertyFromRemote')
                ->withConsecutive(
                        ['test.txt' , 'size' , 'getSize' ,  $config],
                        ['test.txt' , 'timestamp' , 'getTimestamp' ,  $config]
                        )
                ->willReturn($config);
        
        $this->setInaccessibleProperty($this->instance, 'requiredConfig', $requiredConfig);
        
        $config = $this->invokeProtectedMethod($this->instance, 'setConfigFromResult', [$fixtureResult]);
        $this->assertInstanceOf('League\Flysystem\Config' , $config);
    }
    
    public function testDestructor() {
        
        $pathContent = 'file1.txt';
        $pathStream  = 'file2.txt';
        
        $contents = 'test';
        $stream = tmpfile();
        
        $fixtureDeferedSave = 
                [
                    [
                        'path'     => $pathContent,
                        'contents' => $contents,
                        'stream'   => null,
                    ],
                    [
                        'path'     => $pathStream,
                        'contents' => null,
                        'stream'   => $stream,
                    ],
                ];
        
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $this->instance = $this->getMock(
                'oat\flysystem\Adapter\LocalCacheAdapter' , 
                ['setConfigFromResult'],
                [],
                '',
                false
                );
        
        $localProphet->write($pathContent , $contents , $config)->willReturn(true);
        $localProphet->writeStream($pathStream , $stream , $config)->willReturn(true);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance->expects($this->exactly(2))
                ->method('setConfigFromResult')
                ->withConsecutive([$fixtureDeferedSave[0]] , [$fixtureDeferedSave[1]])->willReturnOnConsecutiveCalls($config , $config);
        
        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'deferedSave' , $fixtureDeferedSave);
        
        $this->instance->__destruct();
    }
    
    public function testCopyStream() {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $content = 'test ' . time() . ' ' . rand(10000000 , 99999999);
        $origialStream = tmpfile();
        fwrite($origialStream, $content);
        
        $this->assertSame($content, stream_get_contents($this->invokeProtectedMethod($this->instance, 'copyStream', [$origialStream])));
        
    }
    
    public function testNewConfig() {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock , $localMock );
        
        $this->assertInstanceOf(\League\Flysystem\Config::class, $this->invokeProtectedMethod($this->instance, 'newConfig'));
        
    }

    public function tearDown()
    {
        $this->instance = null;
    }
}