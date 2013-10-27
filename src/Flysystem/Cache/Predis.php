<?php

namespace Flysystem\Cache;

use Predis\Client;

class Predis extends Memory
{
    /**
     * @var  \Predis\Client  $client  Predis Client
     */
    protected $client;

    /**
     * @var  string  $key  storage key
     */
    protected $key;

    /**
     * @var  int|null  $expire  seconds until cache expitation
     */
    protected $expire;

    /**
     * Constructor
     *
     * @param \Predis\Client $client predis client
     * @param string         $key    storage key
     * @param int|null       $expire seconds until cache expitation
     */
    public function __construct(Client $client = null, $key = 'flysystem', $expire = null)
    {
        $this->client = $client ?: new Client;
        $this->key = $key;
        $this->expire = $expire;
    }

    public function load()
    {
        $contents = $this->client->get($this->key);

        if ($contents) {
            $this->setFromStorage($contents);
        }
    }

    public function save()
    {
        $contents = $this->getForStorage();
        $this->client->set($this->key, $contents);

        if ($this->expire !== null) {
            $this->client->expire($this->key, $this->expire);
        }
    }
}
