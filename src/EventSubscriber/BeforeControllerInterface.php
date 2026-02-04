<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface BeforeControllerInterface
{
    public function before(ControllerEvent $event): void;
}
