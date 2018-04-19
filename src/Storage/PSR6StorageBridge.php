<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Storage;

use Psr\Cache\CacheItemPoolInterface;
use Railt\Io\Readable;
use Railt\Reflection\Contracts\Document;
use Railt\Storage\Drivers\Psr6Storage;

/**
 * Class PSR6StorageBridge
 */
class PSR6StorageBridge extends Psr6Storage
{
    /**
     * CacheBridge constructor.
     * @param CacheItemPoolInterface $pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        parent::__construct($pool, function (Readable $readable, Document $document) use ($pool) {
            $hash = $readable->getHash();
            $item = $pool->getItem($hash);

            if (! $item->isHit()) {
                $item->set($document);
                $pool->save($item);
            }

            return $item;
        });
    }
}
