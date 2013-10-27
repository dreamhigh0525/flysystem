<?php

namespace Flysystem\Adapter;

use Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    public function emulateDirectories($listing)
    {
        $directories = array();

        foreach ($listing as $object) {
            if ( ! empty($object['dirname']))
                $directories[] = $object['dirname'];
        }

        $directories = array_unique($directories);

        foreach ($directories as $directory) {
            $directory = pathinfo($directory) + ['path' => $directory, 'type' => 'dir'];

            if ($directory['dirname'] === '.') {
                $directory['dirname'] = '';
            }

            $listing[] = $directory;
        }

        return $listing;
    }
}
