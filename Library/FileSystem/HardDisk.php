<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\FileSystem;

use Zephir\Di\ContainerAwareTrait;
use Zephir\Di\InjectionAwareInterface;
use Zephir\Version;
use function Zephir\unlink_recursive;

/**
 * Zephir\FileSystem\HardDisk
 *
 * Uses the standard hard-disk as filesystem for temporary operations.
 *
 * @package Zephir\FileSystem
 */
class HardDisk implements InjectionAwareInterface
{
    use ContainerAwareTrait {
        ContainerAwareTrait::__construct as protected __DiInject;
    }

    protected $basePath;

    protected $initialized = false;

    /**
     * HardDisk constructor
     *
     * @param string $basePath
     */
    public function __construct($basePath = '.temp/')
    {
        $this->__DiInject();

        $this->basePath = $basePath;
    }

    /**
     * Checks if the filesystem is initialized
     *
     * @return boolean
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Initialize the filesystem
     */
    public function initialize()
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath);
        }
        $this->basePath = realpath($this->basePath) . DIRECTORY_SEPARATOR;
        $this->initialized = true;
    }

    /**
     * Checks whether a temporary entry does exist
     *
     * @param string $path
     * @return boolean
     */
    public function exists($path)
    {
        return file_exists($this->basePath . $path);
    }

    /**
     * Creates a directory inside the temporary container
     *
     * @param string $path
     * @return boolean
     */
    public function makeDirectory($path)
    {
        return mkdir($this->basePath . $path);
    }

    /**
     * Returns a temporary entry as an array
     *
     * @param string $path
     * @return array
     */
    public function file($path)
    {
        return file($this->basePath . $path);
    }

    /**
     * Returns the modification time of a temporary  entry
     *
     * @param string $path
     * @return boolean
     */
    public function modificationTime($path)
    {
        return filemtime($this->basePath . $path);
    }

    /**
     * Writes data from a temporary entry
     *
     * @param string $path
     */
    public function read($path)
    {
        return file_get_contents($this->basePath . $path);
    }

    /**
     * Writes data into a temporary entry
     *
     * @param string $path
     * @param string $data
     */
    public function write($path, $data)
    {
        return file_put_contents($this->basePath . $path, $data);
    }

    /**
     * Executes a command and saves the result into a temporary entry
     *
     * @param string $command
     * @param string $descriptor
     * @param string $destination
     */
    public function system($command, $descriptor, $destination)
    {
        switch ($descriptor) {
            case 'stdout':
                system($command . ' > ' . $this->basePath . $destination);
                break;

            case 'stderr':
                system($command . ' 2> ' . $this->basePath . $destination);
                break;
        }
    }

    /**
     * Requires a file from the temporary directory
     *
     * @param string $path
     * @return mixed
     */
    public function requireFile($path)
    {
        return require $this->basePath . $path;
    }

    /**
     * Deletes the temporary directory.
     *
     * @return void
     */
    public function clean()
    {
        unlink_recursive($this->basePath);
    }

    /**
     * This function does not perform operations in the temporary
     * directory but it caches the results to avoid reprocessing
     *
     * @param string $algorithm
     * @param string $path
     * @param boolean $cache
     * @return string
     */
    public function getHashFile($algorithm, $path, $cache = false)
    {
        if ($cache == false) {
            return hash_file($algorithm, $path);
        } else {
            $changed = false;
            $version = $this->container->get(Version::class);

            $cacheFile = $this->basePath . $version . DIRECTORY_SEPARATOR . str_replace([DIRECTORY_SEPARATOR, ':', '/'], '_', $path) . '.md5';
            if (!file_exists($cacheFile)) {
                $hash = hash_file($algorithm, $path);
                file_put_contents($cacheFile, $hash);
                $changed = true;
            } else {
                if (filemtime($path) > filemtime($cacheFile)) {
                    $hash = hash_file($algorithm, $path);
                    file_put_contents($cacheFile, $hash);
                    $changed = true;
                }
            }

            if (!$changed) {
                return file_get_contents($cacheFile);
            }

            return $hash;
        }
    }
}
