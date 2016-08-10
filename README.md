flysystem Adapter
========

Dual Storage Adapter for league/flysystem
Use to keep a cached local copy of each read of remote file.

It use two storage, a remote location and a local location.
Local location has priority on read operation.
All write operation are made on both.

usage : 

$remote = new League\Flysystem\AwsS3V3\AwsS3Adapter(...);
$local = new League\Flysystem\Adapter\Local(...);

$autosave = true;

$adapter = new oat\LibFlysystemFilecache\model\flysystem\DualStorageAdapter($remote, $local ,autosave);


see http://flysystem.thephpleague.com/ to configure your adapters