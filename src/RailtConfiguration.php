<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class RailtConfiguration
 */
class RailtConfiguration implements ConfigurationInterface
{
    private const DEFAULT_SCHEMA = __DIR__ . '/Resources/graphql/schema.graphqls';
    private const DEFAULT_AUTOLOAD_DIRECTORY = __DIR__ . '/Resources/graphql';

    /**
     * @var string
     */
    private $root;

    /**
     * @var bool
     */
    private $debug;

    /**
     * GuardConfiguration constructor.
     * @param string $root
     * @param bool $debug
     */
    public function __construct(string $root, bool $debug = false)
    {
        $this->root = $root;
        $this->debug = $debug;
    }

    /**
     * @return TreeBuilder
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder();

        $builder = $tree->root($this->root)->children();
            $builder->scalarNode('debug')
                ->defaultValue($this->debug)
            ->end();

            $builder->scalarNode('schema')
                ->cannotBeEmpty()
                ->defaultValue(self::DEFAULT_SCHEMA)
            ->end();

            $builder->arrayNode('autoload')
                ->defaultValue([self::DEFAULT_AUTOLOAD_DIRECTORY])
                ->prototype('scalar')->end()
            ->end();

            $builder->arrayNode('extensions')
                ->defaultValue([])
                ->prototype('scalar')->end()
            ->end();
        $builder->end();

        return $tree;
    }

}
