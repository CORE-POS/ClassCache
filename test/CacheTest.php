<?php

class CacheTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $cachefile = tempnam(sys_get_temp_dir(), 'cct');
        
        $cache = new COREPOS\ClassCache\ClassCache($cachefile);

        // test adding a namespaced class
        $this->assertEquals(true, $cache->add('Test\\ClassA'));

        // test adding same class twice
        $this->assertEquals(true, $cache->add('Test\\ClassA'));

        // test adding non-namespaced
        $this->assertEquals(true, $cache->add('ClassB'));
        
        // test path-specific class
        $this->assertEquals(false, $cache->add('ClassC'));

        // internal classes can't be cached
        $this->assertEquals(false, $cache->add('DateTime'));

        // non-existant classes cannot be cached
        $this->assertEquals(false, $cache->add('Foobar\\Nonsense'));

        $this->assertEquals($cachefile, $cache->get());

        $this->assertEquals(true, $cache->has('ClassB'));
        $this->assertEquals(false, $cache->has('DateTime'));

        // new cache from existing file
        $newcache = new COREPOS\ClassCache\ClassCache($cachefile);
        $this->assertEquals(true, $newcache->has('ClassB'));
        $newcache->clean();
        $this->assertEquals(false, $newcache->has('ClassB'));

        // make sure clean persisted
        $newercache = new COREPOS\ClassCache\ClassCache($cachefile);
        $this->assertEquals(false, $newcache->has('ClassB'));

        unlink($cachefile);
    }
}

