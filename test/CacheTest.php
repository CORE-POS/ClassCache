<?php

use COREPOS\ClassCache\ClassCache;

class CacheTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $cachefile = tempnam(sys_get_temp_dir(), 'cct');
        
        $cache = new ClassCache($cachefile);

        // test adding a namespaced class
        $this->assertEquals(ClassCache::E_OK, $cache->add('Test\\ClassA'));

        // test adding same class twice
        $this->assertEquals(ClassCache::E_OK, $cache->add('Test\\ClassA'));

        // test adding non-namespaced
        $this->assertEquals(ClassCache::E_OK, $cache->add('ClassB'));
        
        // test path-specific class
        $this->assertEquals(ClassCache::E_PATH_SPECIFIC, $cache->add('ClassC'));

        // internal classes can't be cached
        $this->assertEquals(ClassCache::E_INTERNAL_CLASS, $cache->add('DateTime'));

        // non-existant classes cannot be cached
        $this->assertEquals(ClassCache::E_NOSUCH_CLASS, $cache->add('Foobar\\Nonsense'));

        $this->assertEquals($cachefile, $cache->get());

        $this->assertEquals(true, $cache->has('ClassB'));
        $this->assertEquals(false, $cache->has('DateTime'));

        // new cache from existing file
        $newcache = new ClassCache($cachefile);
        $this->assertEquals(true, $newcache->has('ClassB'));
        $newcache->clean();
        $this->assertEquals(false, $newcache->has('ClassB'));

        // make sure clean persisted
        $newercache = new ClassCache($cachefile);
        $this->assertEquals(false, $newcache->has('ClassB'));

        $fp = fopen($cachefile, 'a');
        fwrite($fp, "\nnamespace { class ClassD { } }\n");
        fclose($fp);
        include($cachefile);
        $this->assertEquals(ClassCache::E_DEF_IN_CACHE, $cache->add('ClassD'));

        unlink($cachefile);
    }
}

