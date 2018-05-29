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
use Railt\Foundation\Application;
use Railt\Http\Provider\SymfonyProvider;
use Railt\Http\Request;
use Railt\Http\RequestInterface;
use Railt\Http\ResponseInterface;
use Railt\Io\File;
use Railt\Io\Readable;
use Railt\SDL\Schema\CompilerInterface;
use Railt\Storage\Storage;
use Railt\SymfonyBundle\Storage\PSR6StorageBridge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as OriginalRequest;

/**
 * Class GraphQLController
 */
class GraphQLController
{
    private const FILE_EXTENSIONS = [
        '.graphqls',
        '.graphql',
        '.gql',
    ];

    /**
     * @var Application
     */
    private $app;

    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $schema;

    /**
     * @var array|string[]
     */
    private $autoload = [];

    /**
     * @var array|string[]
     */
    private $extensions = [];

    /**
     * GraphQLController constructor.
     * @param ContainerInterface $container
     * @param array $config
     * @throws \Railt\Io\Exception\NotReadableException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(ContainerInterface $container, array $config)
    {
        $this->bootConfig($config);
        $this->bootCacheDriver($container, $this->debug);

        $this->di  = $container;
        $this->app = new Application($container, $this->debug);

        $this->bootExtensions($this->extensions);
        $this->bootAutoload($this->autoload);
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * @return Readable
     */
    public function getSchema(): Readable
    {
        return File::fromPathname($this->schema);
    }

    /**
     * @param array $config
     */
    private function bootConfig(array $config): void
    {
        [
            'debug'      => $this->debug,
            'schema'     => $this->schema,
            'autoload'   => $this->autoload,
            'extensions' => $this->extensions,
        ] = $config;
    }

    /**
     * @param ContainerInterface $container
     * @param bool $debug
     * @return void
     */
    private function bootCacheDriver(ContainerInterface $container, bool $debug): void
    {
        if (! $debug) {
            $container->alias(PSR6StorageBridge::class, Storage::class);
        }
    }

    /**
     * @param array $extensions
     */
    private function bootExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->app->extend($extension);
        }
    }

    /**
     * @param array $directories
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Railt\Io\Exception\NotReadableException
     */
    private function bootAutoload(array $directories): void
    {
        /** @var CompilerInterface $compiler */
        $compiler = $this->di->get(CompilerInterface::class);

        $compiler->autoload(function (string $type) use ($directories): ?Readable {
            foreach (self::FILE_EXTENSIONS as $ext) {
                foreach ($directories as $dir) {
                    $pathName = $dir . '/' . $type . $ext;

                    if (\is_file($pathName)) {
                        return File::fromPathname($pathName);
                    }
                }
            }

            return null;
        });
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
        $response = $this->app->request(File::fromPathname($this->schema), $request);

        return new JsonResponse($response->render(), $response->getStatusCode(), [], true);
    }

    /**
     * @param array $config
     * @return bool
     */
    private function isDebug(array $config): bool
    {
        return $config['debug'] ?? false;
    }
}
