<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Testing;

use Railt\Foundation\Application;
use Railt\Io\Readable;
use Railt\SymfonyBundle\Controller\GraphQLController;
use Railt\Testing\TestRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Trait InteractWithApplication
 */
trait InteractWithApplication
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Readable
     */
    private $schema;

    /**
     * @return void
     */
    public function bootInteractWithApplication(): void
    {
        \assert(\property_exists($this, 'kernel') && $this->kernel instanceof KernelInterface,
            'Symfony HttpKernel should be sets up in $this->kernel test class field');

        /** @var ContainerInterface $container */
        $container = $this->kernel->getContainer();

        /** @var GraphQLController $controller */
        $controller = $container->get(GraphQLController::class);

        $this->app    = $controller->getApplication();
        $this->schema = $controller->getSchema();
    }

    /**
     * @return void
     */
    public function destroyInteractWithApplication(): void
    {
        $this->app    = null;
        $this->schema = null;
    }

    /**
     * @return TestRequestInterface
     */
    protected function app(): TestRequestInterface
    {
        return $this->appSchema($this->schema, $this->app);
    }
}
