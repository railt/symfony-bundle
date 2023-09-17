<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle\Compiler;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Psr16Cache;

final class CacheAdapter
{
    public function __construct(
        private readonly CacheItemPoolInterface|CacheInterface $cache,
    ) {
    }

    public function create(): CacheInterface
    {
        if ($this->cache instanceof CacheItemPoolInterface) {
            return new Psr16Cache($this->cache);
        }

        return $this->cache;
    }
}
