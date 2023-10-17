<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Psr\SimpleCache\CacheInterface;
use Railt\SymfonyBundle\Compiler\CacheAdapter;
use Railt\SymfonyBundle\Compiler\DirectoryLoader;
use Railt\SymfonyBundle\Controller\GraphQLRequestHandler;
use Railt\SymfonyBundle\Controller\PlaygroundRequestHandler;
use Railt\SymfonyBundle\Router\GraphQLRoutingLoader;
use Railt\SymfonyBundle\Router\PlaygroundRoutingLoader;
use Railt\Contracts\Http\Factory\ErrorFactoryInterface;
use Railt\Contracts\Http\Factory\RequestFactoryInterface;
use Railt\Contracts\Http\Factory\ResponseFactoryInterface;
use Railt\Contracts\Http\Middleware\MiddlewareInterface;
use Railt\Executor\Webonyx\WebonyxExecutor;
use Railt\Extension\Router\RouterExtension;
use Railt\Foundation\Application;
use Railt\Foundation\ApplicationInterface;
use Railt\Foundation\Connection;
use Railt\Foundation\ConnectionInterface;
use Railt\Foundation\ExecutorInterface;
use Railt\Foundation\Extension\ExtensionInterface;
use Railt\Http\Factory\GraphQLErrorFactory;
use Railt\Http\Factory\GraphQLRequestFactory;
use Railt\Http\Factory\GraphQLResponseFactory;
use Railt\SDL\Compiler;
use Railt\SDL\CompilerInterface;
use Railt\SDL\Config;
use Railt\SDL\Config\GenerateSchema;
use Railt\SDL\Dictionary;
use Railt\TypeSystem\DictionaryInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class RailtExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configs = $this->processConfiguration(
            configuration: new RailtConfiguration(),
            configs: $configs,
        );

        $this->registerGlobalServices($container);
        $this->registerExtensions($container);

        $this->registerConfigAwareCompilers($configs, $container);
        $this->registerConfigAwareApplications($configs, $container);
        $this->registerConfigAwarePlaygrounds($configs, $container);

        if ($container->hasParameter('kernel.debug')
            && $container->getParameter('kernel.debug')
            && $container->hasDefinition(Stopwatch::class)
        ) {
            $container->register(RailtProfiler::class)
                ->setArgument('$stopwatch', new Reference(Stopwatch::class))
                ->setArgument('$dispatcher', new Reference(EventDispatcherInterface::class))
                ->addTag('kernel.event_listener', [
                    'method' => 'boot',
                    'event' => 'kernel.request',
                ])
            ;
        }
    }

    private function registerGlobalServices(ContainerBuilder $container): void
    {
        $container->register(ErrorFactoryInterface::class, GraphQLErrorFactory::class);
        $container->register(RequestFactoryInterface::class, GraphQLRequestFactory::class);
        $container->register(ResponseFactoryInterface::class, GraphQLResponseFactory::class);
    }

    private function registerExtensions(ContainerBuilder $container): void
    {
        $this->registerRouterExtension($container);
    }

    private function registerRouterExtension(ContainerBuilder $container): void
    {
        $container->register(RouterExtension::class)
            ->setArgument('$container', new Reference('service_container'))
        ;
    }

    private function registerConfigAwareCompilers(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     cache: non-empty-string|null,
         *     specification: Config\Specification,
         * } $config
         */
        foreach (($configs['compilers'] ?? []) as $name => $config) {
            $definition = $container->register("railt.$name.compiler", CompilerInterface::class)
                ->setClass(Compiler::class)
                ->setArgument('$config', $this->createConfig($config))
                ->setArgument('$types', $this->createDictionary($config, $container))
            ;

            if ($config['cache'] ?? null) {
                $cacheAdapterDefinition = (new Definition(CacheAdapter::class))
                    ->addArgument(new Reference($config['cache']))
                ;

                $cacheDefinition = $container->register("railt.$name.cache", CacheInterface::class)
                    ->setFactory([$cacheAdapterDefinition, 'create'])
                ;

                $definition->setArgument('$cache', $cacheDefinition);
            }

            foreach ($config['autoload'] ?? [] as $directory) {
                $definition->addMethodCall('addLoader', [
                    (new Definition(DirectoryLoader::class))
                        ->addArgument($directory)
                ]);
            }
        }
    }

    private function registerConfigAwareApplications(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     route: non-empty-string,
         *     schema: non-empty-string,
         *     variables: array<non-empty-string, mixed>,
         *     executor: non-empty-string|null,
         *     compiler: non-empty-string|null,
         *     middleware: list<non-empty-string|class-string<MiddlewareInterface>>,
         *     extensions: list<non-empty-string|class-string<ExtensionInterface>>
         * } $app
         */
        foreach (($configs['endpoints'] ?? []) as $name => $app) {
            $container->setParameter("railt.$name.route", $app['route']);

            $application = $container->register("railt.$name.application", ApplicationInterface::class)
                ->setClass(Application::class)
                ->setArgument('$executor', $this->createExecutor($app, $container))
                ->setArgument('$compiler', $this->createCompiler($app, $container))
                ->setArgument('$middleware', $this->createPipeline($name, $app, $container))
                ->setArgument('$extensions', $this->createExtensions($name, $app, $container))
                ->setArgument('$dispatcher', new Reference(EventDispatcherInterface::class))
            ;

            $container->register("railt.$name.connection", ConnectionInterface::class)
                ->setClass(Connection::class)
                ->setFactory([$application, 'connect'])
                ->setArgument('$schema', $this->createSchema($app, $container))
                ->setArgument('$variables', $app['variables'])
            ;

            $container->register("railt.$name.controller", GraphQLRequestHandler::class)
                ->setPublic(true)
                ->setArgument('$connection', new Reference("railt.$name.connection"))
                ->setArgument('$requests', new Reference(RequestFactoryInterface::class))
            ;

            $container->register("railt.$name.loader", GraphQLRoutingLoader::class)
                ->setArgument('$name', $name)
                ->setArgument('$controller', "railt.$name.controller")
                ->setArgument('$route', $app['route'])
                ->addTag('routing.loader')
            ;
        }
    }

    private function registerConfigAwarePlaygrounds(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     route: non-empty-string,
         *     endpoint: non-empty-string,
         *     headers: array<non-empty-string, non-empty-string>
         * } $playground
         */
        foreach (($configs['playground'] ?? []) as $name => $playground) {
            $container->register("railt.$name.graphiql_controller", PlaygroundRequestHandler::class)
                ->setPublic(true)
                ->setArgument('$route', new Parameter("railt.{$playground['endpoint']}.route"))
                ->setArgument('$headers', $playground['headers'])
            ;

            $container->register("railt.$name.graphiql_loader", PlaygroundRoutingLoader::class)
                ->setArgument('$name', $name)
                ->setArgument('$controller', "railt.$name.graphiql_controller")
                ->setArgument('$route', $playground['route'])
                ->addTag('routing.loader')
            ;
        }
    }

    /**
     * @param non-empty-string $name
     * @param array{extensions: list<non-empty-string|class-string<ExtensionInterface>>} $config
     */
    private function createExtensions(string $name, array $config, ContainerBuilder $container): IteratorArgument
    {
        $extensions = [];

        foreach ($config['extensions'] ?? [] as $extension) {
            $extensions[] = $this->createExtension($name, $extension, $container);
        }

        return new IteratorArgument($extensions);
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $extension
     */
    private function createExtension(string $name, string $extension, ContainerBuilder $container): Reference|Definition
    {
        if ($container->hasDefinition($extension)) {
            return new Reference($extension);
        }

        return $container->autowire("railt.$name.extension.$extension", $extension);
    }

    /**
     * @param array{schema: non-empty-string} $config
     */
    private function createSchema(array $config, ContainerBuilder $container): Definition|string
    {
        if (\is_file($config['schema'])) {
            return $container->register(\SplFileInfo::class)
                ->setClass(\SplFileInfo::class)
                ->addArgument($config['schema'])
            ;
        }

        return $config['schema'];
    }

    /**
     * @param array{types: non-empty-string|null} $config
     */
    private function createDictionary(array $config, ContainerBuilder $container): Definition|Reference
    {
        if (isset($config['types'])) {
            return new Reference($config['types']);
        }

        if ($container->hasDefinition(DictionaryInterface::class)) {
            return new Reference(DictionaryInterface::class);
        }

        return new Definition(Dictionary::class);
    }

    /**
     * @param array{
     *     spec: non-empty-string,
     *     generate: array{
     *         query: non-empty-string|null,
     *         mutation: non-empty-string|null,
     *         subscription: non-empty-string|null
     *     },
     *     cast: array{
     *         int_to_float: bool,
     *         scalar_to_string: bool
     *     },
     *     extract: array{
     *         nullable: bool,
     *         list: bool
     *     }
     * } $config
     */
    private function createConfig(array $config): Definition
    {
        return (new Definition(Config::class))
            ->setArgument('$spec', (new Definition(Config\Specification::class))
                ->setFactory([Config\Specification::class, 'from'])
                ->addArgument($config['spec'])
            )
            // Generate
            ->setArgument('$generateSchema', (new Definition(GenerateSchema::class))
                ->setArgument('$queryTypeName', $config['generate']['query'])
                ->setArgument('$mutationTypeName', $config['generate']['mutation'])
                ->setArgument('$subscriptionTypeName', $config['generate']['subscription'])
            )
            // Auto Casting
            ->setArgument('$castIntToFloat', $config['cast']['int_to_float'])
            ->setArgument('$castScalarToString', $config['cast']['scalar_to_string'])
            // Auto Extraction
            ->setArgument('$castNullableTypeToDefaultValue', $config['extract']['nullable'])
            ->setArgument('$castListTypeToDefaultValue', $config['extract']['list'])
        ;
    }

    /**
     * @param non-empty-string $name
     * @param array{middleware: list<non-empty-string|class-string<MiddlewareInterface>>} $config
     */
    private function createPipeline(string $name, array $config, ContainerBuilder $container): IteratorArgument
    {
        $result = [];

        foreach ($config['middleware'] as $middleware) {
            $result[] = $this->createMiddleware($name, $middleware, $container);
        }

        return new IteratorArgument($result);
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string|class-string<MiddlewareInterface> $middleware
     */
    private function createMiddleware(string $name, string $middleware, ContainerBuilder $builder): Reference|Definition
    {
        if ($builder->hasDefinition($middleware)) {
            return new Reference($middleware);
        }

        if ($builder->hasDefinition($middleware)) {
            return new Definition($middleware);
        }

        return $builder->autowire("railt.$name.middleware.$middleware", $middleware);
    }

    /**
     * @param array{compiler: non-empty-string|null} $config
     */
    private function createCompiler(array $config, ContainerBuilder $container): Reference|Definition
    {
        if ($config['compiler'] !== null) {
            if ($container->hasDefinition($config['compiler'])) {
                return new Reference($config['compiler']);
            }

            return new Reference(\sprintf('railt.%s.compiler', $config['compiler']));
        }

        if ($container->hasDefinition(CompilerInterface::class)) {
            return new Reference(CompilerInterface::class);
        }

        return new Definition(Compiler::class);
    }

    /**
     * @param array{executor: non-empty-string|null} $config
     */
    private function createExecutor(array $config, ContainerBuilder $container): Reference|Definition
    {
        if ($config['executor'] !== null) {
            return new Reference($config['executor']);
        }

        if ($container->hasDefinition(ExecutorInterface::class)) {
            return new Reference(ExecutorInterface::class);
        }

        return new Definition(WebonyxExecutor::class);
    }
}
