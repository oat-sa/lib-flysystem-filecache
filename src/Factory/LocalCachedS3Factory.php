<?php
/**
 * Created by PhpStorm.
 * User: christophe
 * Date: 24/05/17
 * Time: 10:59
 */

namespace oat\flysystem\Adapter\Factory;

use oat\awsTools\factory\AbstractFlysystemFactory;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use oat\flysystem\Adapter\Cache\LocalCopy;
use oat\flysystem\Adapter\Cache\Metadata\StorageInterface;
use oat\flysystem\Adapter\Cache\MetaDataFactory;
use oat\flysystem\Adapter\LocalCacheAdapter;

class LocalCachedS3Factory extends AbstractFlysystemFactory
{

    const CLIENT_SERVICE = 'generis/awsClient';

    public function getClient()
    {
        return $this->getServiceLocator()->get(self::CLIENT_SERVICE)->getS3Client();
    }

    public function __invoke($options)
    {

        $remote = new AwsS3Adapter($this->getClient(), $options[self::OPTION_BUCKET], $options[self::OPTION_PREFIX]);

        /**
         * @var $cache StorageInterface
         */
        $cache = MetaDataFactory::build();
        $local = new LocalCopy($options[self::OPTION_PATH] , $cache);
        $adapter = new LocalCacheAdapter($remote, $local, true);

        return $adapter;

    }

}