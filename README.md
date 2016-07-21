flysystem Adapter
========

Use to keep a cached local copy of each read of remote file.

It use two storage, a remote location and a local location.
Local location has priority on read operation.
All write operation are made on both.
