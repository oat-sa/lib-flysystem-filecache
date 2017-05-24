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
use oat\flysystem\Adapter\Cache\Metadata\AbstractStorage;
use oat\flysystem\Adapter\Cache\Metadata\PhpStorage;
use oat\flysystem\Adapter\Cache\Metadata\StorageInterface;
use oat\flysystem\Adapter\Cache\MetaDataFactory;
use oat\flysystem\Adapter\LocalCacheAdapter;

class LocalCachedS3Factory extends AbstractFlysystemFactory
{

    const OPTION_BUCKET = 'bucket';

    const OPTION_PREFIX = 'prefix';

    const OPTION_CLIENT = 'client';

    const OPTION_PATH   = 'path';

    const CLIENT_SERVICE = 'generis/awsClient';

    public function getClient()
    {
        return $this->getServiceLocator()->get(self::CLIENT_SERVICE)->getS3Client();
    }

    public function __invoke($options)
    {

        $remote = new AwsS3Adapter($this->getClient(), $options[self::OPTION_BUCKET], $options[self::OPTION_PREFIX]);

        if(isset($options['metaStorage']) && is_subclass_of($options['metaStorage'] , AbstractStorage::class)) {
            /**
             * specified metadata cache storage
             */
            $storageClass = $options['metaStorage'];
            $cache = new $storageClass();
        } else {
            /**
             * auto detect from config
             */
            $cache = MetaDataFactory::build();
        }
        /**
         * @var $cache StorageInterface
         */

        $local = new LocalCopy($options[self::OPTION_PATH] , $cache);
        $adapter = new LocalCacheAdapter($remote, $local, true);

        return $adapter;

    }

}