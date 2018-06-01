<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Controller;

use Railt\Container\ContainerInterface;
use Railt\Foundation\Application\Configurator;
use Railt\Http\Provider\SymfonyProvider;
use Railt\Http\Request;
use Railt\Http\RequestInterface;
use Railt\Http\ResponseInterface;
use Railt\Storage\Storage;
use Railt\SymfonyBundle\Storage\PSR6StorageBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as OriginalRequest;

/**
 * Class GraphQLController
 */
class GraphQLController
{
    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var Configurator
     */
    private $factory;

    /**
     * GraphQLController constructor.
     * @param ContainerInterface $container
     * @param Configurator $config
     */
    public function __construct(ContainerInterface $container, Configurator $config)
    {
        $this->di = $container;

        if (! $config->isDebug()) {
            $container->alias(PSR6StorageBridge::class, Storage::class);
        }

        $this->factory = $config;
    }

    /**
     * @param OriginalRequest $symfony
     * @return mixed
     * @throws \LogicException
     */
    public function handleAction(OriginalRequest $symfony)
    {
        $request = new Request(new SymfonyProvider($symfony));

        // Register the http request
        $this->di->instance(RequestInterface::class, $request);

        /** @var ResponseInterface $response */
        $response = $this->factory->request($request);

        return new JsonResponse($response->render(), $response->getStatusCode(), [], true);
    }
}
