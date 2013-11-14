<?php

namespace Flysystem\Adapter;

use LogicException;
use ZipArchive;
use Flysystem\Util;

class Zip extends AbstractAdapter
{
    protected static $resultMap = array(
        'size'  => 'size',
        'mtime' => 'timestamp',
    );

    protected $archive;

    public function __construct($location, ZipArchive $archive = null)
    {
        $this->setArchive($archive ?: new ZipArchive);
        $this->openArchive($location);
    }

    protected function reopenArchive()
    {
        $path = $this->archive->filename;
        $this->archive->close();
        $this->openArchive($path);
    }

    public function setArchive(ZipArchive $archive)
    {
        $this->archive = $archive;
    }

    public function getArchive()
    {
        return $this->archive;
    }

    public function openArchive($location)
    {
        if (($response = $this->archive->open($location, ZipArchive::CREATE)) !== true) {
            throw new LogicException('Could not open zip archive at:'.$location.', error: '.$response);
        }
    }

    public function write($path, $contents, $visibility = null)
    {
        $dirname = Util::dirname($path);

        if ( ! empty($dirname) and  ! $this->has($dirname)) {
            $this->createDir($dirname);
        }

        if ( ! $this->archive->addFromString($path, $contents)) {
            return false;
        }

        return compact('path', 'contents', 'visibility');
    }

    public function update($path, $contents)
    {
        $this->delete($path);

        return $this->write($path, $contents);
    }

    public function rename($path, $newpath)
    {
        return $this->archive->renameName($path, $newpath);
    }

    public function delete($path)
    {
        return $this->archive->deleteName($path);
    }

    public function deleteDir($dirname)
    {
        $path = Util::normalizePrefix($dirname, '/');
        $length = strlen($path);

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $info = $this->archive->statIndex($i);

            if (substr($info['name'], 0, $length) === $path) {
                $this->archive->deleteIndex($i);
            }
        }

        return $this->archive->deleteName($dirname);
    }

    public function createDir($dirname)
    {
        if ( ! $this->has($dirname)) {
            $this->archive->addEmptyDir($dirname);
        }

        return array('path' => $dirname);
    }

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function read($path)
    {
        $this->reopenArchive();

        if ( ! $contents = $this->archive->getFromName($path)) {
            return false;
        }

        return compact('contents');
    }

    public function listContents($dirname = '', $recursive = false)
    {
        $result = array();

        // This is needed to ensure the right numer of
        // files are set to the $numFiles property.
        $this->reopenArchive();

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            if ($info = $this->archive->statIndex($i)) {
                $result[] = $this->normalizeObject($info);
            }
        }

        return $result;
    }

    public function getMetadata($path)
    {
        if ( ! $info = $this->archive->statName($path)) {
            return false;
        }

        return $this->normalizeObject($info);
    }

    protected function normalizeObject(array $object)
    {
        if (substr($object['name'], -1) === '/') {
            return array(
                'path' => trim($object['name'], '/'),
                'type' => 'dir'
            );
        }

        $result = array('type' => 'file');

        return array_merge($result, Util::map($object, static::$resultMap));
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        if ( ! $data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::contentMimetype($data['contents']);

        return $data;
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getVisibility($path)
    {
        throw new LogicException('The Zip adapter does not support visibility settings.');
    }

    public function setVisibility($path, $visibility)
    {
        throw new LogicException('The Zip adapter does not support visibility settings.');
    }

}
