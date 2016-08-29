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
 * Description of JsonStorageTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class PhpStorageTest extends \oat\flysystem\test\helper\PhpUnitTestHelper 
{
    
    public function setUp() {
        $this->instance = new \oat\flysystem\Adapter\Cache\Metadata\PhpStorage();
    }
    
    public function testWriteFile() {
        
        $this->instance = $this->getMock(\oat\flysystem\Adapter\Cache\Metadata\PhpStorage::class , ['toPhpCode']);
        
        \org\bovigo\vfs\vfsStream::setup('var');
        
        $file = \org\bovigo\vfs\vfsStream::url('var/test1.php');
        
        
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
        
        $expected = '<?php return ' . var_export($meta, true) . ';';
        
        $this->instance
                ->expects($this->once())
                ->method('toPhpCode')
                ->with($meta)
                ->willReturn($expected);
        
        $this->assertNotFalse($this->invokeProtectedMethod($this->instance, 'writeFile' , [$file , $meta]));
        $this->assertSame($expected, file_get_contents($file));
    }
    
    public function testReadFile() {
        
        \org\bovigo\vfs\vfsStream::setup('var');
        
        $filename = \org\bovigo\vfs\vfsStream::url('var/test1.php');
        $fileTest = \org\bovigo\vfs\vfsStream::url('var/test2.php');
        
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
        
        $data = '<?php return ' . var_export($meta, true) . ';';

        file_put_contents($filename, $data);
        
        $this->assertFalse($this->invokeProtectedMethod($this->instance, 'readFile' , [$fileTest]));
        $this->assertSame($meta , $this->invokeProtectedMethod($this->instance, 'readFile' , [$filename]));
        
    }
    
    public function testToPhpCode() {
         $meta = [
            'mimetype' => 'text/html',
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
        ];
        
        $expected = '<?php return ' . var_export($meta, true) . ';';
        
        $this->assertSame($expected , $this->invokeProtectedMethod($this->instance, 'toPhpCode' , [$meta]));
    }

    public function tearDown() {
        $this->instance = null;
    }
    
}
