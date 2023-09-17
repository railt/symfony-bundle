<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class RailtBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new RailtExtension();
    }
}
