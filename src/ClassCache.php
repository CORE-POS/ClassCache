<?php

namespace COREPOS\ClassCache;
use \ReflectionClass;
use \ReflectionException;

/**
 * @class ClassCache
 *
 * Write a whole bunch of class definitions to one file
 */
class ClassCache
{
    /**
     * Array of cached class names
     */
    private $known = array();

    /**
     * File where cache is maintained
     */
    private $file = '';

    /**
     * @constructor
     * @param $cachefile [string] file to use for caching
     */
    public function __construct($cachefile)
    {
        if (!file_exists($cachefile)) {
            $this->initFile($cachefile);
        }
        include($cachefile);
        if (!isset($_class_cache_known_list) || !is_array($_class_cache_known_list)) {
            $this->initFile($cachefile);
        } else {
            $this->known = $_class_cache_known_list;
        }
        $this->file = $cachefile;
    }

    /**
     * Clear & initialize cache file
     * @param $file [string] file to use for caching
     */
    private function initFile($file)
    {
        $fp = fopen($file, 'w');
        fwrite($fp, "<?php\n");
        fwrite($fp, 'namespace { $_class_cache_known_list = ' . $this->varSquash($this->known) . "; }\n");
        fclose($fp);
    }

    /**
     * var_export plus stripping whitespace
     * @param $var [mixed] variable to export
     * @return [string] PHP reprentation of $var w/o whitespace
     */
    private function varSquash($var)
    {
        $code = var_export($var, true);
        $code = preg_replace('/\s+/', '', $code);

        return $code;
    }

    /**
     * Add a class to the cache
     * @param $class [string] class name
     * @return [bool] success
     *
     * The class must be formatted predictably. Its definition must
     * be locatable via reflection & autoloading. The class
     * cannot use more than one namespace, curly bracket'd
     * namespace(s), or PHP close & re-open tags
     */
    public function add($class)
    {
        if (isset($this->known[$class])) {
            return true;
        }
        try {
            $refl = new ReflectionClass($class);
            $def = $refl->getFileName();
            if ($def === false) {
                return false;
            }
        } catch (ReflectionException $ex) {
            return false;
        }
        $code = file_get_contents($def);
        $code = $this->stripNamespace($code);
        $uses = $this->getUseStatements($code);

        list($namespace, $bareClass) = $this->unNamespace($class);

        $currentCache = file_get_contents($this->file);
        $currentCache = $this->stripKnownClasses($currentCache);
        $this->known[$class] = true;

        $this->initFile($this->file);
        $fp = fopen($this->file, 'a');
        fwrite($fp, $currentCache);
        fwrite($fp, "\nnamespace {$namespace} {\n");
        foreach ($uses as $u) {
            $code = str_replace($u, '', $code);
            fwrite($fp, $u . "\n");
        }
        fwrite($fp, "\nif (!class_exists('{$class}', false)) {\n");
        fwrite($fp, $code);
        fwrite($fp, "\n}\n");
        fwrite($fp, "\n}\n");

        return true;
    }

    /**
     * Extract "use" statements from the code
     * @param $content [string] PHP code
     * @return [array] use statements
     */
    private function getUseStatements($content)
    {
        preg_match_all('/^\s*use .+;.*$/m', $content, $matches);
        return is_array($matches) ? $matches[0] : array();
    }

    /**
     * Remove opening PHP tag & namespace tag from code
     * @param $content [string] PHP code
     * @return [string] PHP code
     */
    private function stripNamespace($content)
    {
        $content = str_replace('<?php', '', $content);
        $content = trim($content);
        return preg_replace('/^.*namespace .+$/m', '', $content);
    }

    /**
     * Remove opening PHP tag & known-classes variable from code
     * @param $content [string] PHP code
     * @return [string] PHP code
     */
    private function stripKnownClasses($content)
    {
        $content = str_replace('<?php', '', $content);
        $content = trim($content);
        return preg_replace('/^.*namespace { .+$/m', '', $content);
    }

    /**
     * Convert a namespaced class into base name
     * and namespace
     * @param [string] class name
     * @return [array] namespace, base class name
     */
    private function unNamespace($class)
    {
        if (!strstr($class, '\\')) {
            return array('', $class);
        }
        $last = strrpos($class, '\\');
        $className = substr($class, $last);
        $className = trim($className, '\\');
        $nsName = substr($class, 0, $last);
        $nsName = trim($nsName, '\\');
        $nsName = str_replace('\\\\', '\\', $nsName); 

        return array($nsName, $className);
    }

    /**
     * Clear the current cache
     */
    public function clean()
    {
        $this->known = array();
        $this->initFile($this->file);
    }

    /**
     * Get the filename where classes are cached
     * @return [string] filename
     */
    public function get()
    {
        return $this->file;
    }

    /**
     * Check if class is cached
     * @param $class [string] class name
     * @return [bool]
     */
    public function has($class)
    {
        return isset($this->known[$class]);
    }
}

