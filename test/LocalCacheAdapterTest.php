<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 15/07/16
 * Time: 10:12
 */

namespace oat\libFlysystemFilecache\test;


use League\Flysystem\FileAttributes;
use oat\flysystem\Adapter\LocalCacheAdapter;
use oat\tao\test\TaoPhpUnitTestRunner;

class LocalCacheAdapterTest extends TaoPhpUnitTestRunner
{
    /**
     * @var LocalCacheAdapter
     */
    protected $instance;

    public function testConstruct()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $synchronous = true;

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, $synchronous);

        $this->assertSame($remoteMock, $this->getInaccessibleProperty($this->instance, 'remoteStorage'));
        $this->assertSame($localMock, $this->getInaccessibleProperty($this->instance, 'localStorage'));
        $this->assertSame($synchronous, $this->getInaccessibleProperty($this->instance, 'synchronous'));
    }

    public function callWithFallbackProvider()
    {
        return
            [
                [
                    'has',
                    ['/path/test1'],
                    true,
                    false,
                ],
                [
                    'getTimestamp',
                    ['/path/test1'],
                    false,
                    false,
                ],
                [
                    'getMetadata',
                    ['/path/test1'],
                    false,
                    true,
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

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);

        $this->assertSame(
            $expected,
            $this->invokeProtectedMethod($this->instance, 'callWithFallback', [$method, $args])
        );
    }

    public function testGetters()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);

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

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);

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

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);

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
                ['test2.txt', false, false, true, false],
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
                ['test2.txt', false, false, false, false],
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

        $this->instance = $this->getMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult'],
            [],
            '',
            false
        );


        $localProphet->fileExists($path)->willReturn($localResult);

        if ($localResult === false) {
            $remoteProphet->read($path)->willReturn($remoteResult);
        } else {
            $localProphet->read($path)->willReturn($localResult);
        }

        $expectedSave = [];

        if ($remoteResult !== false && $synchronous) {
            $this->instance->expects($this->once())->method('setConfigFromResult')
                ->with($remoteResult)->willReturn($config);

            $localProphet->write($path, $remoteResult['contents'], $config)->willReturn($remoteResult);
        } elseif ($remoteResult !== false) {
            $expectedSave[] = $remoteResult;
        }

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous', $synchronous);

        $this->assertSame($expected, $this->instance->read($path));
        $this->assertSame($expectedSave, $this->getInaccessibleProperty($this->instance, 'deferedSave'));
        $this->setInaccessibleProperty($this->instance, 'deferedSave', []);
    }

    public function readStreamProvider()
    {
        $tmp1 = tmpfile();
        $tmp2 = tmpfile();

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
                    'test1.txt',
                    ['path' => 'test1.txt', 'stream' => $tmp1],
                    false,
                    false,
                    ['path' => 'test1.txt', 'stream' => $tmp1],
                ],
                [
                    'test2.txt',
                    false,
                    ['path' => 'test2.txt', 'stream' => $tmp2],
                    false,
                    ['path' => 'test2.txt', 'stream' => $tmp2],
                ],
                ['test3.txt', false, false, false, false],
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
        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $this->instance = $this->getMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult'],
            [],
            '',
            false
        );

        $localProphet->fileExists($path)->willReturn($localResult);

        $expectedSave = [];

        if ($localResult === false) {
            $remoteProphet->readStream($path)->willReturn($remoteResult);
        } else {
            $localProphet->readStream($path)->willReturn($localResult);
        }

        if ($remoteResult !== false && $synchronous) {
            $this->instance->expects($this->once())->method('setConfigFromResult')
                ->with($remoteResult)->willReturn($config);
            $localProphet->writeStream($path, $remoteResult['stream'], $config)->willReturn($remoteResult);
            $localProphet->readStream($path)->willReturn($remoteResult);
        } elseif ($remoteResult !== false) {
            $expectedSave[] = $remoteResult;
        }

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->setInaccessibleProperty($this->instance, 'remoteStorage', $remoteMock);
        $this->setInaccessibleProperty($this->instance, 'localStorage', $localMock);
        $this->setInaccessibleProperty($this->instance, 'synchronous', $synchronous);

        $this->assertEquals($expected, $this->instance->readStream($path));
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

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock, $config);
        $this->assertSame($fixtureList, $this->instance->listContents($fixtureDirectory, $fixtureRecursive));
    }

    public function testInitStream()
    {
        $remoteMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();
        $localMock = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter')->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);

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

        $returnLocal = [
            'path' => $path,
            'stream' => $file,
            'local',
        ];

        $returnDist = [
            'path' => $path,
            'stream' => $file,
            'remote',
        ];

        $localProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $remoteProphet = $this->prophesize('League\Flysystem\Local\LocalFilesystemAdapter');
        $config = $this->prophesize('League\Flysystem\Config')->reveal();

        $remoteProphet->writeStream($path, $file, $config)->willReturn($returnDist);
        $localProphet->writeStream($path, $file, $config)->willReturn($returnLocal);

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);
        $this->assertSame($returnDist, $this->instance->writeStream($path, $file, $config));
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


        $remoteProphet->getVisibility('test.txt')->willReturn(['visibility' => 'public']);
        $remoteProphet->getSize('test.txt')->willReturn(['size' => 180]);

        $localMock = $localProphet->reveal();
        $remoteMock = $remoteProphet->reveal();

        $this->instance = new LocalCacheAdapter($remoteMock, $localMock);
        $config = $this->invokeProtectedMethod($this->instance, 'setConfigFromResult', [$fixtureResult]);
        $this->assertInstanceOf('League\Flysystem\Config', $config);
        $this->assertEquals($expected, $this->getInaccessibleProperty($config, 'settings'));
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

        $this->instance = $this->getMock(
            'oat\flysystem\Adapter\LocalCacheAdapter',
            ['setConfigFromResult'],
            [],
            '',
            false
        );

        $localProphet->write($pathContent, $contents, $config)->willReturn(true);
        $localProphet->writeStream($pathStream, $stream, $config)->willReturn(true);

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

    public function tearDown()
    {
        $this->instance = null;
    }
}