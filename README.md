flysystem Adapter
========

Dual Storage Adapter for league/flysystem
Use to keep a cached local copy of each read of remote file.

It use two storage, a remote location and a local location.
Local location has priority on read operation.
All write operation are made on both.

usage :
 
```php
$remote = new League\Flysystem\AwsS3V3\AwsS3Adapter(...);
$local = new League\Flysystem\Adapter\Local(...);

$synchronous = true;

$adapter = new oat\flysystem\Adapter\LocalCacheAdapter($remote, $local ,$synchronous);
```

That's possible to use a specific Local adapter using a metadata cache module. It can store localy 
metadata set up explicitly in your remote storage.

let library decide which cache adapter is better to use

```php
$cache = oat\flysystem\Adapter\Cache\MetaDataFactory::build();

$remote = new League\Flysystem\AwsS3V3\AwsS3Adapter(...);
$local = new League\Flysystem\Adapter\Cache\LocalCopy($myPath , $cache);

$autosave = true;

$adapter = new oat\flysystem\Adapter\LocalCacheAdapter($remote, $local ,$autosave);
```

use a specific cache : 

```php
$cache = new oat\flysystem\Adapter\Cache\Metadata\{TxtStorage|JsonStorage|PhpStorage|ApcuStorage}();

$remote = new League\Flysystem\AwsS3V3\AwsS3Adapter(...);
$local = new League\Flysystem\Adapter\Cache\LocalCopy($myPath , $cache);

$autosave = true;

$adapter = new oat\flysystem\Adapter\LocalCacheAdapter($remote, $local ,$autosave);
```

see http://flysystem.thephpleague.com/ to configure your adapters