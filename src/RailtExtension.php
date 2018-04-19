<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class RailtExtension
 */
class RailtExtension extends ConfigurableExtension
{
    /**
     * @var string An configuration
     */
    private const CONFIGURATION_ROOT_NODE = 'railt';

    /**
     * @var string Get debug configuration parameter
     */
    private const CONFIGURATION_DEBUG_PARAMETER = 'kernel.debug';

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return RailtConfiguration|mixed|null|\Symfony\Component\Config\Definition\ConfigurationInterface
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new RailtConfiguration(self::CONFIGURATION_ROOT_NODE, $this->isDebug($container));
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    private function isDebug(ContainerBuilder $container): bool
    {
        if ($container->hasParameter(self::CONFIGURATION_DEBUG_PARAMETER)) {
            return (bool)$container->getParameter(self::CONFIGURATION_DEBUG_PARAMETER);
        }

        return false;
    }

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function loadInternal(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter(self::CONFIGURATION_ROOT_NODE, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));

        $loader->load('services.yml');
    }
}
