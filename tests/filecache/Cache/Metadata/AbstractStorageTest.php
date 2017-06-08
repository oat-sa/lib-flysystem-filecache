<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oat\flysystem\test\filecache\cache\metadata;

use League\Flysystem\Config;
use oat\flysystem\Adapter\Cache\Metadata\AbstractStorage;
use oat\flysystem\test\helper\PhpUnitTestHelper;

/**
 * Description of AbstractStorageTest
 *
 * @author Christophe GARCIA <christopheg@taotesting.com>
 */
class AbstractStorageTest extends PhpUnitTestHelper {
    
    public function testParseData() {
        
        $now = time();
        $size = rand(1024, 4096);
        
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
        
        $expected = [
            'mimetype' => 'text/html',
            'size'     => $size,
            'timestamp'=> $now,
            'basename' => 'test.html',
            'extension'=> 'html',
            'filename' => 'test',
            ];
        
        $Config = new Config($fixtureSettings);
        
        $this->instance = $this->getMockForAbstractClass(AbstractStorage::class, [], '', false , false, true ,
                ['setParam']);
        
        $this->instance->expects($this->exactly(7))
                ->method('setParam')
                ->withConsecutive(
                    [[] , 'mimetype' , 'text/html'],
                    [['mimetype' => 'text/html']  ,'size'     , $size],
                    [['mimetype' => 'text/html'   , 'size'     => $size] ,'timestamp', $now],
                    [['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now] , 'basename' , 'test.html'],
                    [['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html'] ,  'extension', 'html'],
                    [['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html' , 'extension'=> 'html'] ,'filename' , 'test'],
                    [['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html' , 'extension'=> 'html' , 'filename' => 'test'] ,'type'      , '']
                  )
                ->willReturnOnConsecutiveCalls(
                        ['mimetype' => 'text/html'],
                        ['mimetype' => 'text/html'   , 'size'     => $size],
                        ['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now ],
                        ['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html'],
                        ['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html' ,  'extension' => 'html'],
                        ['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html' ,  'extension' => 'html','filename' => 'test'],
                        ['mimetype' => 'text/html'   , 'size'     => $size , 'timestamp'=> $now , 'basename' => 'test.html' ,  'extension' => 'html','filename' => 'test']
                    );
        
        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'parseData' , [$Config]));
        
    }
    
    public function setParamProvider()  {
        return 
        [
            [
                [[] , 'mimetype' , ''] , [] 
            ],
            [
                [[] , 'mimetype' , null] , [] 
            ],
            [
                [[] , 'mimetype' , 'text/html'] , ['mimetype' => 'text/html'] 
            ],
        ];
    }
    /**
     * 
     * @param array $parameters
     * @param array $expected
     * @dataProvider setParamProvider
     */
    public function testSetParam(array $parameters , array $expected) {
        
        $this->instance = $this->getMockForAbstractClass(AbstractStorage::class, [], '', false , false, true ,[]);
        
        $this->assertSame($expected, $this->invokeProtectedMethod($this->instance, 'setParam' , $parameters));
    }

    public function tearDown() {
        $this->instance = null;
    }
    
}
