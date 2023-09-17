<?php

declare(strict_types=1);

namespace Railt\SymfonyBundle;

use Railt\Extension\Router\Event\ActionDispatched;
use Railt\Extension\Router\Event\ActionDispatching;
use Railt\Foundation\Event\Http\RequestReceived;
use Railt\Foundation\Event\Http\ResponseProceed;
use Railt\Foundation\Event\Resolve\FieldResolved;
use Railt\Foundation\Event\Resolve\FieldResolving;
use Railt\Foundation\Event\Schema\SchemaCompiled;
use Railt\Foundation\Event\Schema\SchemaCompiling;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class RailtProfiler
{
    private bool $booted = false;

    public function __construct(
        private readonly Stopwatch $stopwatch,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        $this->dispatcher->addListener(RequestReceived::class, function () {
            $this->stopwatch->start('railt:request', 'railt');
        }, 1024);

        $this->dispatcher->addListener(ResponseProceed::class, function () {
            $this->stopwatch->stop('railt:request');
        }, -1024);

        $this->dispatcher->addListener(SchemaCompiling::class, function () {
            $this->stopwatch->start('railt:compile', 'railt');
        }, 1024);

        $this->dispatcher->addListener(SchemaCompiled::class, function () {
            $this->stopwatch->stop('railt:compile');
        }, -1024);

        $this->dispatcher->addListener(FieldResolving::class, function (FieldResolving $ev) {
            $this->stopwatch->start('railt:field(' . $ev->input->getFieldName() . ')', 'railt');
        }, 1024);

        $this->dispatcher->addListener(FieldResolved::class, function (FieldResolved $ev) {
            $this->stopwatch->stop('railt:field(' . $ev->input->getFieldName() . ')');
        }, -1024);

        $this->dispatcher->addListener(ActionDispatching::class, function (ActionDispatching $ev) {
            $this->stopwatch->start('railt:action(' . $ev->input->getFieldName() . ')', 'railt');
        }, 1024);

        $this->dispatcher->addListener(ActionDispatched::class, function (ActionDispatched $ev) {
            $this->stopwatch->stop('railt:action(' . $ev->input->getFieldName() . ')');
        }, -1024);
    }

    public function boot(): void
    {
        $this->booted = true;
    }
}
