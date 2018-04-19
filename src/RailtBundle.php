<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class RailtBundle
 */
class RailtBundle extends Bundle
{
    /**
     * @return ExtensionInterface
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return new RailtExtension();
    }
}
