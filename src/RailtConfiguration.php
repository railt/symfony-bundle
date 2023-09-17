<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Railt\SDL\Config\Specification;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class RailtConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('railt');

        $root = $builder->getRootNode();

        $this->configureEndpoints($root);
        $this->configureCompilers($root);
        $this->configurePlayground($root);

        return $builder;
    }

    private function configureCompilers(ArrayNodeDefinition $builder): void
    {
        $builder->children()
            ->arrayNode('compilers')
                ->arrayPrototype()
                    ->children()
                        ->enumNode('spec')
                            ->values(\array_map(
                                static fn (Specification $spec): string => $spec->value,
                                Specification::cases(),
                            ))
                            ->defaultValue(Specification::DEFAULT->value)
                        ->end()
                        ->arrayNode('autoload')
                            ->defaultValue([])
                            ->scalarPrototype()
                            ->end()
                        ->end()
                        ->scalarNode('types')
                            ->cannotBeEmpty()
                            ->defaultNull()
                        ->end()
                        ->arrayNode('generate')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('query')
                                    ->defaultValue('Query')
                                ->end()
                                ->scalarNode('mutation')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('subscription')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('cast')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('int_to_float')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('scalar_to_string')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('extract')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('nullable')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('list')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('cache')
                            ->cannotBeEmpty()
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function configureEndpoints(ArrayNodeDefinition $builder): void
    {
        $builder->children()
            ->arrayNode('endpoints')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('schema')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('variables')
                            ->defaultValue([])
                            ->variablePrototype()
                            ->end()
                        ->end()
                        ->scalarNode('route')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('/graphql')
                        ->end()
                        ->scalarNode('executor')
                            ->cannotBeEmpty()
                            ->defaultNull()
                        ->end()
                        ->scalarNode('compiler')
                            ->cannotBeEmpty()
                            ->defaultNull()
                        ->end()
                        ->arrayNode('middleware')
                            ->defaultValue([])
                            ->scalarPrototype()
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                        ->arrayNode('extensions')
                            ->defaultValue([
                                \Railt\Extension\Router\RouterExtension::class,
                            ])
                            ->scalarPrototype()
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function configurePlayground(ArrayNodeDefinition $builder): void
    {
        $builder->children()
            ->arrayNode('playground')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('endpoint')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('route')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('default')
                        ->end()
                        ->arrayNode('headers')
                            ->defaultValue([])
                            ->scalarPrototype()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
