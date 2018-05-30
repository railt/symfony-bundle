<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Testing;

use Symfony\Component\DependencyInjection\ResettableContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Trait InteractWithSymfonyKernel
 */
trait InteractWithSymfonyKernel
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @return void
     */
    public function bootInteractWithSymfonyKernel(): void
    {
        $this->ensureKernelShutdown();

        $this->kernel = $this->getAppKernel();
        $this->kernel->boot();
    }

    /**
     * @param string $name
     * @return object
     */
    protected function service(string $name)
    {
        return $this->kernel->getContainer()->get($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function parameter(string $name)
    {
        return $this->kernel->getContainer()->getParameter($name);
    }

    /**
     * Shuts the kernel down if it was used in the test.
     */
    private function ensureKernelShutdown(): void
    {
        if ($this->kernel !== null) {
            $container = $this->kernel->getContainer();

            $this->kernel->shutdown();

            if ($container instanceof ResettableContainerInterface) {
                $container->reset();
            }
        }

        $this->kernel = null;
    }

    /**
     * @return KernelInterface
     */
    abstract protected function getAppKernel(): KernelInterface;

    /**
     * @return void
     */
    public function destroyInteractWithSymfonyKernel(): void
    {
        $this->ensureKernelShutdown();
    }
}
