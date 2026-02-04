<?php

namespace App\EventListener;

use App\Controller\IndexController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Security;

final class CheckCurrentSchoolListener
{
    /**
     * @var string
     */
    protected $stateCountry;

    /**
     * @var Security
     */
    protected $security;

    public function __construct(Security $security, ParameterBagInterface $params)
    {
        $this->security = $security;
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * @param ControllerEvent $event
     * @throws \Exception
     */
    public function __invoke(ControllerEvent $event): void
    {
        if ($this->security->getUser()
            && \is_array($event->getController())
            && ! \in_array($event->getController()[1], ['changeSchool', 'acceptInvite', 'declineInvite'])
            && ! \in_array('ROLE_ADMIN', $this->security->getUser()->getRoles())
            && ! \is_null($this->security->getUser()->getCurrentSchool())
            && (
                $this->stateCountry === 'rp'
                && $this->security->getUser()->getCurrentSchool()->getAuditEnd() <= new \DateTime()
            )
        ) {
            if (! $event->getController()[0] instanceof IndexController || $event->getController()[1] !== 'index') {
                $event->setController(static function () {
                    return new RedirectResponse('/');
                });
            }
        }
    }
}
