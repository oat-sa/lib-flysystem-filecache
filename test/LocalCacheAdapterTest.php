<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 15/07/16
 * Time: 10:12
 */

namespace oat\libFlysystemFilecache\test;


use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\FileAttributes;
use oat\flysystem\Adapter\LocalCacheAdapter;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\XmlConfiguration\File;
use Prophecy\Argument;
use ReflectionClass;
use Prophecy\PhpUnit\ProphecyTrait;

class LocalCacheAdapterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var LocalCacheAdapter
     */
    protected $instance;

    public function testConstruct()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $synchronous = true;

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, $synchronous, '/path/to/file');

        $this->assertSame($remoteMock, $this->getInaccessibleProperty($this->instance, 'remoteStorage'));
        $this->assertSame($localMock, $this->getInaccessibleProperty($this->instance, 'localStorage'));
        $this->assertSame($synchronous, $this->getInaccessibleProperty($this->instance, 'synchronous'));
    }

    public function callWithFallbackProvider()
    {
        $fileAttributeLocalMock = $this->createMock(FileAttributes::class);
        $fileAttributeLocalMock->method('lastModified')->willreturn(null);
        $fileAttributeLocalMock->method('fileSize')->willreturn(null);

        $fileAttributeRemoteMock = $this->createMock(FileAttributes::class);
        $fileAttributeRemoteMock->method('lastModified')->willreturn(null);
        $fileAttributeRemoteMock->method('fileSize')->willreturn(10);

        return
            [
                [
                    'fileExists',
                    ['/path/test1'],
                    true,
                    false,
                ],
                [
                    'lastModified',
                    ['/path/test1'],
                    $fileAttributeLocalMock,
                    $fileAttributeLocalMock,
                ],
                [
                    'fileSize',
                    ['/path/test1'],
                    $fileAttributeLocalMock,
                    $fileAttributeRemoteMock,
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
    public function testCallWithFallback($method, $args, $localResult, $remoteResult)
    {
        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $localProphet->$method()->withArguments($args)->willReturn($localResult);
        $localMock = $localProphet->reveal();

        $expected = $localResult;

        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        if ($localResult === false) {
            $remoteProphet->$method()->withArguments($args)->willReturn($remoteResult);
            $expected = $remoteResult;
        }
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');

        $this->assertSame(
            $expected,
            $this->invokeProtectedMethod($this->instance, 'callWithFallback', [$method, $args])
        );
    }

    public function testGetters()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');

        $this->assertSame($remoteMock, $this->instance->getRemoteStorage());
        $this->assertSame($localMock, $this->instance->getLocalStorage());
    }

    public function synchronousProvider()
    {
        return
            [
                [true, true],
                [false, false],
                [0, false],
                [1, true],
                ['0', false],
                ['1', true],
            ];
    }

    /**
     * @dataProvider synchronousProvider
     * @param type $value
     * @param type $expected
     */
    public function testSetGetSynchronous($value, $expected)
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');

        $this->assertSame($this->instance, $this->instance->setSynchronous($value));
        $this->assertSame($expected, $this->instance->getSynchronous());
    }

    public function callOnBothProvider()
    {
        $fileAttributeLocalMock = $this->createMock(FileAttributes::class);
        $fileAttributeLocalMock->method('lastModified')->willreturn(null);
        $fileAttributeLocalMock->method('fileSize')->willreturn(null);

        $fileAttributeRemoteMock = $this->createMock(FileAttributes::class);
        $fileAttributeRemoteMock->method('lastModified')->willreturn(null);
        $fileAttributeRemoteMock->method('fileSize')->willreturn(10);

        return
            [
                [
                    'fileExists',
                    ['/path/test1'],
                    true,
                    false,
                ],
                [
                    'lastModified',
                    ['/path/test1'],
                    $fileAttributeLocalMock,
                    $fileAttributeRemoteMock,
                ],
                [
                    'fileSize',
                    ['/path/test1'],
                    $fileAttributeLocalMock,
                    $fileAttributeRemoteMock,
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
    public function testCallOnBoth($method, $args, $localResult, $remoteResult)
    {
        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $localProphet->$method()->withArguments($args)->willReturn($localResult);
        $localMock = $localProphet->reveal();

        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');

        $remoteProphet->$method()->withArguments($args)->willReturn($remoteResult);
        $expected = $remoteResult;

        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');

        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'callOnBoth', [$method, $args]));
    }

    public function readProvider()
    {
        return
            [
                [
                    'test1.txt',
                    ['path' => 'test1.txt', 'contents' => 'test1'],
                    false,
                    true,
                    ['path' => 'test1.txt', 'contents' => 'test1'],
                ],
                [
                    'test2.txt',
                    false,
                    ['path' => 'test2.txt', 'contents' => 'test2'],
                    true,
                    ['path' => 'test2.txt', 'contents' => 'test2'],
                ],
                ['test2.txt', false, false, true, ''],
                [
                    'test1.txt',
                    ['path' => 'test1.txt', 'contents' => 'test1'],
                    false,
                    false,
                    ['path' => 'test1.txt', 'contents' => 'test1'],
                ],
                [
                    'test2.txt',
                    false,
                    ['path' => 'test2.txt', 'contents' => 'test2'],
                    false,
                    ['path' => 'test2.txt', 'contents' => 'test2'],
                ],
                ['test2.txt', false, false, false, ''],
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
    public function testRead($path, $localResult, $remoteResult, $synchronous, $expected)
    {
        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = $this->createPartialMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult']
        );

        $localProphet->fileExists($path)->willReturn(false !== $localResult);

        $localLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $localLastModified->lastModified()->willReturn(2);
        $remoteLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $remoteLastModified->lastModified()->willReturn(1);
        
        $localProphet->lastModified($path)->willReturn($localLastModified);
        $remoteProphet->lastModified($path)->willReturn($remoteLastModified);

        if ($localResult === false) {
            $remoteProphet->read($path)->willReturn($remoteResult['contents'] ?? $remoteResult);
        } else {
            $localProphet->read($path)->willReturn($localResult['contents'] ?? $localResult);
        }

        $expectedSave = [];

        if ($remoteResult !== false && $synchronous) {
            $this->instance->expects($this->any())->method('setConfigFromResult')
                ->with($remoteResult)->willReturn($config);

            $localProphet->write($path, $remoteResult['contents'] ?? $remoteResult, $config);
        } elseif ($remoteResult !== false) {
            $expectedSave[] = $remoteResult;
        }

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous', $synchronous);

        $this->assertSame($expected['contents'] ?? $expected, $this->instance->read($path));
        $this->assertSame($expectedSave, $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave', []);
    }

    public function readStreamProvider()
    {
        $tmp1 = tmpfile();
        $tmp2 = tmpfile();
        $tmp3 = tmpfile();
        $tmp4 = tmpfile();

        return
            [
                [
                    'test1.txt',
                    ['path' => 'test1.txt', 'stream' => $tmp1],
                    false,
                    true,
                    ['path' => 'test1.txt', 'stream' => $tmp1],
                ],
                [
                    'test2.txt',
                    false,
                    ['path' => 'test2.txt', 'stream' => $tmp2],
                    true,
                    ['path' => 'test2.txt', 'stream' => $tmp2],
                ],
                ['test3.txt', false, false, true, false],
                [
                    'test4.txt',
                    ['path' => 'test4.txt', 'stream' => $tmp3],
                    false,
                    false,
                    ['path' => 'test4.txt', 'stream' => $tmp3],
                ],
                [
                    'test5.txt',
                    false,
                    ['path' => 'test5.txt', 'stream' => $tmp4],
                    false,
                    ['path' => 'test5.txt', 'stream' => $tmp4],
                ],
                ['test6.txt', false, false, false, false],
            ];
    }

    /**
     * @dataProvider readStreamProvider
     * @param string $path
     * @param array|boolean $localResult
     * @param array|boolean $remoteResult
     * @param mixed $expected
     */
    public function testReadStream($path, $localResult, $remoteResult, $synchronous, $expected)
    {
        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');

        $localLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $localLastModified->lastModified()->willReturn(2);
        $remoteLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $remoteLastModified->lastModified()->willReturn(1);

        $localProphet->lastModified($path)->willReturn($localLastModified);
        $remoteProphet->lastModified($path)->willReturn($remoteLastModified);

        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = $this->createPartialMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult']
        );

        $localProphet->fileExists($path)->willReturn((bool)$localResult);

        $expectedSave = [];

        if ($localResult === false) {
            $remoteProphet->readStream($path)->willReturn($remoteResult['stream'] ?? $remoteResult);
        } else {
            $localProphet->readStream($path)->willReturn($localResult['stream'] ?? $localResult);
        }

        if ($remoteResult !== false && $synchronous) {
            $this->instance->expects($this->any())->method('setConfigFromResult')
                ->with($remoteResult)->willReturn($config);
            $localProphet->writeStream($path, $remoteResult['stream'] ?? $remoteResult, $config);
            $localProphet->readStream($path)->willReturn($remoteResult);
        } elseif ($remoteResult !== false) {
            $expectedSave[] = $remoteResult;
        }

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous', $synchronous);

        $this->assertEquals($expected['stream'] ?? $expected, $this->instance->readStream($path));
        $this->assertSame($expectedSave, $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave', []);
    }

    public function testListContents()
    {
        $fixtureDirectory = '/tmp';
        $fixtureRecursive = false;

        $fixtureList =
            [
                'test1.txt',
                'test2.txt',
                'test3.txt',
            ];

        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $localMock = $localProphet->reveal();
        $remoteProphet->listContents($fixtureDirectory, $fixtureRecursive)->willReturn($fixtureList);
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path', $config);
        $this->assertSame($fixtureList, $this->instance->listContents($fixtureDirectory, $fixtureRecursive));
    }

    public function testInitStream()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');

        $fixtureStream = tmpfile();
        fputs($fixtureStream, 'test1' . "\n" . 'test2');
        fread($fixtureStream, 10);
        $this->assertSame(
            $fixtureStream,
            $this->invokeProtectedMethod($this->instance, 'initStream', [$fixtureStream])
        );
        $this->assertSame(0, ftell($fixtureStream));
    }

    public function testWriteStream()
    {
        $file = tmpfile();
        $path = 'test1.txt';

        $returnDist = $file;

        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');

        $localLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $localLastModified->lastModified()->willReturn(2);
        $remoteLastModified = $this->prophesize('League\Flysystem\FileAttributes');
        $remoteLastModified->lastModified()->willReturn(1);
        
        $localProphet->lastModified($path)->willReturn($localLastModified);
        $remoteProphet->lastModified($path)->willReturn($remoteLastModified);
        
        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $remoteProphet->writeStream($path, Argument::any(), $config);
        $localProphet->writeStream($path, Argument::any(), $config);

        $localProphet->fileExists($path)->willReturn(true);
        $localProphet->readStream($path)->willReturn($file);

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');
        $this->instance->writeStream($path, $file, $config);

        $this->assertSame($returnDist, $this->instance->readStream($path));
    }

    public function testSetConfigFromResult()
    {
        $fixtureResult =
            [
                'path' => 'test.txt',
                'mimetype' => 'text/plain',
            ];

        $expected =
            [
                'mimetype' => 'text/plain',
                'visibility' => 'public',
                'size' => 180,
            ];

        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');


        $mockFileAttr = $this->createMock(FileAttributes::class);
        $mockFileAttr->method('visibility')->willReturn('public');
        $mockFileAttr->method('fileSize')->willReturn(180);


        $remoteProphet->visibility('test.txt')->willReturn($mockFileAttr);
        $remoteProphet->fileSize('test.txt')->willReturn($mockFileAttr);

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, 'path/to/file');
        $config = $this->invokeProtectedMethod($this->instance, 'setConfigFromResult', [$fixtureResult]);
        $this->assertInstanceOf('League\Flysystem\Config', $config);
    }

    public function testDestructor()
    {
        $pathContent = 'file1.txt';
        $pathStream = 'file2.txt';

        $contents = 'test';
        $stream = tmpfile();

        $fixtureDeferedSave =
            [
                [
                    'path' => $pathContent,
                    'contents' => $contents,
                    'stream' => null,
                ],
                [
                    'path' => $pathStream,
                    'contents' => null,
                    'stream' => $stream,
                ],
            ];

        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');

        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = $this->createPartialMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult']
        );

        $localProphet->write($pathContent, $contents, $config);
        $localProphet->writeStream($pathStream, $stream, $config);

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->instance->expects($this->exactly(2))
            ->method('setConfigFromResult')
            ->withConsecutive([$fixtureDeferedSave[0]], [$fixtureDeferedSave[1]])->willReturnOnConsecutiveCalls(
                $config,
                $config
            );

        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'deferedSave', $fixtureDeferedSave);

        $this->instance->__destruct();
    }

    protected function tearDown(): void
    {
        $this->instance = null;
    }

    protected function getInaccessibleProperty($object, $propertyName)
    {
        $property = new \ReflectionProperty(get_class($object), $propertyName);
        $property->setAccessible(true);
        $value = $property->getValue($object);
        $property->setAccessible(false);
        return $value;
    }

    protected function setInaccessibleProperty($object, $propertyName, $value)
    {
        $property = new \ReflectionProperty(get_class($object), $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
        return $this;
    }

    protected function invokeProtectedMethod($obj, $method, $params)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $params);
    }
}
