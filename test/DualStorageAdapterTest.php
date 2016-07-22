<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 15/07/16
 * Time: 10:12
 */

namespace oat\libFlysystemFilecache\test;


use oat\flysystem\Adapter\DualStorageAdapter;
use oat\tao\test\TaoPhpUnitTestRunner;

class DualStorageAdapterTest extends TaoPhpUnitTestRunner
{
    /**
     * @var DualStorageAdapter
     */
    protected $instance;

    public function testConstruct() {

        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $autosave   = true;
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config , $autosave);
        
        $this->assertSame($remoteMock, $this->getInaccessibleProperty($this->instance, 'remoteStorage'));
        $this->assertSame($localMock, $this->getInaccessibleProperty($this->instance, 'localStorage'));
        $this->assertSame($config, $this->getInaccessibleProperty($this->instance, 'localConfig'));
        $this->assertSame($autosave, $this->getInaccessibleProperty($this->instance, 'autosave'));

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
        
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance , 'callWithFallback' , [$method , $args]));

    }
    
    public function testGetters() {
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $this->assertSame($remoteMock, $this->instance->getRemoteStorage());
        $this->assertSame($localMock, $this->instance->getLocalStorage());
        $this->assertSame($config, $this->instance->getLocalConfig());
    }
    
    public function autosaveProvider() {
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
     * @dataProvider autosaveProvider
     * @param type $value
     * @param type $expected
     */
    public function testSetGetAutosave($value , $expected) {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $this->assertSame($this->instance, $this->instance->setAutosave($value));
        $this->assertSame($expected, $this->instance->getAutosave());
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
        
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance , 'callOnBoth' , [$method , $args]));
    }
    
    public function readProvider() {
        
        return 
        [
            ['test1.txt' , ['contents' => 'test1'], false                  , true  ,['contents' => 'test1']],
            ['test2.txt' , false                  , ['contents' => 'test2'], true  ,['contents' => 'test2']],
            ['test2.txt' , false                  , false                  , true  ,false],
            ['test1.txt' , ['contents' => 'test1'], false                  , false ,['contents' => 'test1']],
            ['test2.txt' , false                  , ['contents' => 'test2'], false ,['contents' => 'test2']],
            ['test2.txt' , false                  , false                  , false ,false],
        ];
        
    }

        /**
     * @dataProvider readProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param boolean $autosave
     * @param mixed $expected
     */
    public function testRead($path , $localResult , $remoteResult ,$autosave , $expected) {
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $localProphet->read($path)->willReturn($localResult);
        
        if($localResult === false) {
            $remoteProphet->read($path)->willReturn($remoteResult);
        }
        
        $expectedSave = [];
        
        if($remoteResult !== false && $autosave) {
            $localProphet->write($path , $remoteResult['contents'] , $config)->willReturn($remoteResult);
        } elseif($remoteResult !== false) {
            $expectedSave[] =  $remoteResult;
        }
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config , $autosave);
        $this->assertSame($expected , $this->instance->read($path));
        $this->assertSame($expectedSave , $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave' , []);
    }
    
    public function readStreamProvider() {
        
        $tmp1 = tmpfile();
        $tmp2 = tmpfile();
        
        return 
        [
            ['test1.txt' , ['stream' => $tmp1], false              , true  , ['stream' => $tmp1]],
            ['test2.txt' , false              , ['stream' => $tmp2], true  , ['stream' => $tmp2]],
            ['test3.txt' , false              , false              , true  , false],
            ['test1.txt' , ['stream' => $tmp1], false              , false , ['stream' => $tmp1]],
            ['test2.txt' , false              , ['stream' => $tmp2], false , ['stream' => $tmp2]],
            ['test3.txt' , false              , false              , false , false],
        ];
        
    }

        /**
     * @dataProvider readStreamProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param mixed $expected
     */
    public function testReadStream($path , $localResult , $remoteResult, $autosave , $expected) {
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $localProphet->readStream($path)->willReturn($localResult);
        
        $expectedSave = [];
        
        if($localResult === false) {
            $remoteProphet->readStream($path)->willReturn($remoteResult);
        }
        
        if($remoteResult !== false && $autosave) {
            $localProphet->writeStream($path , $remoteResult['stream'] , $config)->willReturn($remoteResult);
        } elseif($remoteResult !== false) {
            $expectedSave[] =  $remoteResult;
        }
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config , $autosave);
        $this->assertSame($expected , $this->instance->readStream($path));
        $this->assertSame($expectedSave , $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave' , []);
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
        $config         = $this->prophesize('League\Flysystem\Config')->reveal();
                
        $localMock      = $localProphet->reveal();
        $remoteProphet->listContents($fixtureDirectory , $fixtureRecursive)->willReturn($fixtureList);
        $remoteMock     = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->assertSame($fixtureList , $this->instance->listContents($fixtureDirectory , $fixtureRecursive));
    }
    
    public function testInitStream() {
        
        $remoteMock = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $localMock  = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter')->reveal();
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $fixtureStream = tmpfile();
        fputs($fixtureStream, 'test1' . "\n" . 'test2');
        fread($fixtureStream, 10);
        $this->assertSame($fixtureStream, $this->invokeProtectedMethod($this->instance, 'initStream' , [$fixtureStream]));
        $this->assertSame(0, ftell($fixtureStream));
    }
    
    public function testWriteStream() {
        
        $file = tmpfile();
        $path = 'test1.txt';
        
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
        
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();

        $remoteProphet->writeStream($path , $file , $config)->willReturn($returnDist);
        $localProphet->writeStream($path , $file , $config)->willReturn($returnLocal);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->assertSame($returnDist , $this->instance->writeStream($path , $file , $config));
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

        $localProphet->write($pathContent , $contents , $config)->willReturn(true);
        $localProphet->writeStream($pathStream , $stream , $config)->willReturn(true);
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->setInaccessibleProperty($this->instance, 'deferedSave' , $fixtureDeferedSave);
        unset($this->instance);
    }

    public function tearDown()
    {
        $this->instance = null;
    }
}