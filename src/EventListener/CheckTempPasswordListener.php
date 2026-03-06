<?php

namespace App\EventListener;

use App\Controller\SecurityController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Security;

final class CheckTempPasswordListener
{
    public function __construct(protected Security $security)
    {
    }

    /** @throws \Exception */
    public function __invoke(ControllerEvent $event): void
    {
        if ($this->security->getUser()
            && \is_array($event->getController())
            && $this->security->getUser()->isTempPassword()) {
            if (! $event->getController()[0] instanceof SecurityController ||
                $event->getController()[1] !== 'changeTempPassword') {
                $event->setController(static fn () => new RedirectResponse('/change-temp-password'));
            }
        }
    }
}
