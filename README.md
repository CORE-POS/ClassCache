# ClassCache
Pack a bunch of PHP classes into a single file for faster loading

# Usage
```php

$cacheLocation = '/path/to/cache/file.php';
$cache = new COREPOS\ClassCache\ClassCache($cacheLocation);

// cache a class
$cache->add('Class\\To\\Cache');

// load everything in the cache
include($cache->get());

```
