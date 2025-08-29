<?php
namespace App\Hashing;

use Illuminate\Hashing\HashManager;

class Md5HashManager extends HashManager
{
    /**
     * Create an instance of the Md5 hash Driver.
     *
     * @return \App\Hashing\Md5Hasher
     */
    public function createMd5Driver()
    {
        return new Md5Hasher($this->config->get('hashing.md5') ?? []);
    }
}