<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle\Router;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class PlaygroundRoutingLoader extends Loader
{
    private bool $isLoaded = false;

    /**
     * @param non-empty-string $name
     * @param non-empty-string $controller
     * @param non-empty-string $route
     */
    public function __construct(
        private readonly string $name,
        private readonly string $controller,
        private readonly string $route,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, string $type = null): RouteCollection
    {
        if ($this->isLoaded === true) {
            throw new \RuntimeException('Do not add the "railt" loader twice');
        }

        $routes = new RouteCollection();

        $routes->add("railt.{$this->name}.graphiql", new Route(
            path: $this->route,
            defaults: ['_controller' => $this->controller],
            methods: ['GET', 'OPTIONS', 'HEAD'],
        ));

        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return $this->isLoaded === false;
    }
}
