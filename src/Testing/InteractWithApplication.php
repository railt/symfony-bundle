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
use Railt\Testing\TestRequestInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Trait InteractWithApplication
 */
trait InteractWithApplication
{
    /**
     * @var Application\Configurator
     */
    private $factory;

    /**
     * @return void
     */
    public function bootInteractWithApplication(): void
    {
        \assert(\property_exists($this, 'kernel') && $this->kernel instanceof KernelInterface,
            'Symfony HttpKernel should be sets up in $this->kernel test class field');

        $this->factory = $this->kernel->getContainer()
            ->get(Application\Configurator::class);
    }

    /**
     * @return void
     */
    public function destroyInteractWithApplication(): void
    {
        $this->factory = null;
    }

    /**
     * @return TestRequestInterface
     */
    protected function app(): TestRequestInterface
    {
        return $this->appSchema($this->factory->getSchema(), $this->factory->create());
    }
}
