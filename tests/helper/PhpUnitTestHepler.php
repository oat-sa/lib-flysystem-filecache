<?php

abstract class PhpUnitTestHelper extends GenerisPhpUnitTestRunner{
	
	const SESSION_KEY = 'TAO_TEST_SESSION';
    /**
     *
     * @var boolean
     */
    private static $connected = false;


    /**
     * shared methods for test initialization
     */
    public static function initTest(){
        //connect the API
        if(!self::$connected){
            \common_session_SessionManager::startSession(new \common_test_TestUserSession());
            self::$connected = true;
        }
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object $object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeProtectedMethod($object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
    
    /**
     * return inaccessible property value
     * @param type $object
     * @param type $propertyName
     * @return mixed
     */
    protected function getInaccessibleProperty($object , $propertyName) {
        $property = new \ReflectionProperty(get_class($object) , $propertyName);
        $property->setAccessible(true);
        $value = $property->getValue($object);
        $property->setAccessible(false);
        return $value;
    }
    /**
     * set inaccessible property value
     * @param type $object
     * @param type $propertyName
     * @param type $value
     * @return \oat\tao\test\TaoPhpUnitTestRunner
     */
    protected function setInaccessibleProperty($object , $propertyName, $value) {
        $property = new \ReflectionProperty(get_class($object) , $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible(false);
        return $this;
    }
    
}


