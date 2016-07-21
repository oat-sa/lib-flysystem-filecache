<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 15/07/16
 * Time: 10:12
 */

namespace oat\libFlysystemFilecache\test;


use oat\libFlysystemFilecache\Flysystem\DualStorageAdapter;
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

        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        
        $this->assertSame($remoteMock, $this->getInaccessibleProperty($this->instance, 'remoteStorage'));
        $this->assertSame($localMock, $this->getInaccessibleProperty($this->instance, 'localStorage'));
        $this->assertSame($config, $this->getInaccessibleProperty($this->instance, 'localConfig'));

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
            ['test1.txt' , ['contents' => 'test1'], false ,  ['contents' => 'test1']],
            ['test2.txt' , false , ['contents' => 'test2'],  ['contents' => 'test2']],
            ['test2.txt' , false , false,  false],
        ];
        
    }

        /**
     * @dataProvider readProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param mixed $expected
     */
    public function testRead($path , $localResult , $remoteResult , $expected) {
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $localProphet->read($path)->willReturn($localResult);
        
        if($localResult === false) {
            $remoteProphet->read($path)->willReturn($remoteResult);
        }
        
        if($remoteResult !== false) {
            $localProphet->write($path , $remoteResult['contents'] , $config)->willReturn(true);
        }
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->assertSame($expected , $this->instance->read($path));
    }
    
    public function readStreamProvider() {
        
        return 
        [
            ['test1.txt' , ['stream' => 'test1'], false ,  ['stream' => 'test1']],
            ['test2.txt' , false , ['stream' => 'test2'],  ['stream' => 'test2']],
            ['test2.txt' , false , false,  false],
        ];
        
    }

        /**
     * @dataProvider readStreamProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param mixed $expected
     */
    public function testReadStream($path , $localResult , $remoteResult , $expected) {
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
        
        $localProphet->readStream($path)->willReturn($localResult);
        
        if($localResult === false) {
            $remoteProphet->readStream($path)->willReturn($remoteResult);
        }
        
        if($remoteResult !== false) {
            $localProphet->writeStream($path , $remoteResult['stream'] , $config)->willReturn(true);
        }
        
        $localMock    = $localProphet->reveal();
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->assertSame($expected , $this->instance->readStream($path));
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
                
        $localProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Adapter\AbstractAdapter');
        $config     = $this->prophesize('League\Flysystem\Config')->reveal();
                
        $localMock    = $localProphet->reveal();
        $remoteProphet->listContents($fixtureDirectory , $fixtureRecursive)->willReturn($fixtureList);
        $remoteMock   = $remoteProphet->reveal();
        
        $this->instance = new DualStorageAdapter($remoteMock , $localMock , $config);
        $this->assertSame($fixtureList , $this->instance->listContents($fixtureDirectory , $fixtureRecursive));
    }

    public function tearDown()
    {
        $this->instance = null;
    }
}