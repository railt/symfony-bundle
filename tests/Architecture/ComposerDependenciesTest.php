<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ComposerDependenciesTest
{
    public function testDependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::namespace('Railt\SymfonyBundle'))
            ->canOnlyDependOn()
            ->classes(
                Selector::namespace('Railt\SymfonyBundle'),
                // @dependencies
                Selector::namespace('Psr\EventDispatcher'),
                Selector::namespace('Railt\Foundation'),
                Selector::namespace('Railt\Contracts\Http'),
                Selector::namespace('Railt\Contracts\Http\Factory'),
                Selector::namespace('Railt\Contracts\Http\Middleware'),
                Selector::namespace('Railt\Http\Factory'),
                Selector::namespace('Railt\TypeSystem'),
                Selector::namespace('GraphQL'),
            )
        ;
    }
}
