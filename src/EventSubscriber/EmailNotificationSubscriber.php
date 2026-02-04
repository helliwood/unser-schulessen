<?php

namespace App\EventSubscriber;

use App\Entity\QualityCheck\Result;
use App\Entity\QualityCircle\ActionPlanNew;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailNotificationSubscriber implements EventSubscriber
{

    protected MailerInterface $mailer;

    /**
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @return array|string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    /**
     * @param PreUpdateEventArgs $args
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if ($_ENV["APP_STATE_COUNTRY"] !== 'rp') {
            return;
        }

        $entity = $args->getObject();

        if ($entity instanceof Result && $entity->isFinalised()) {
            $this->checkResultRatio($entity);
        } elseif ($entity instanceof ActionPlanNew) {
            $this->checkActionPlanFinished($entity);
        }
    }

    public const NOTIFICATION_REQUIREMENT = 0.6;

    /**
     * @param Result $result
     * @return void
     */
    private function checkResultRatio(Result $result): void
    {
        if ($result->isFinalised() && $result->getResultRatio() > self::NOTIFICATION_REQUIREMENT) {
            foreach ($this->getSchoolConsultants($result->getSchool()) as $consultant) {
                $this->sendNotificationMail($consultant->getUser(), $result->getSchool(), "Die Schule " . $result->getSchool()->getName() . " hat beim Qualitätscheck über " . self::NOTIFICATION_REQUIREMENT * 100 . "% erreicht!");
            }
        }
    }

    private function checkActionPlanFinished(ActionPlanNew $actionPlan): void
    {
        if ($actionPlan->isCompleted()) {
            foreach ($this->getSchoolConsultants($actionPlan->getToDo()->getSchool()) as $consultant) {
                $this->sendNotificationMail($consultant, $actionPlan->getToDo()->getSchool(), "Die Schule " . $actionPlan->getToDo()->getSchool()->getName() . " hat einen Aktionsplan abgeschlossen!");
            }
        }
    }

    /**
     * @param User|null $user
     * @param School $school
     * @param string $text
     * @return void
     */
    private function sendNotificationMail(?User $user, School $school, string $text): void
    {
        try {
            $email = (new TemplatedEmail())
                ->subject('Unser Schulessen - betreffend "' . $school->getName())
                ->from(new Address($_ENV['APP_STATE_COUNTRY'] . '@unser-schulessen.de', 'Unser Schulessen'))
                ->to($user->getEmail())
                ->htmlTemplate('emails/notification_mail.html.twig')
                ->context(
                    [
                        'school' => $school,
                        'user' => $user,
                        'text' => $text,
                    ]
                );

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            \dump($e->getMessage());
        }
//        \dd($email);
    }

    /**
     * @param School $school
     * @return UserHasSchool[]|ArrayCollection
     */
    public function getSchoolConsultants(School $school)
    {
        return $school->getUserHasSchool()->filter(function (UserHasSchool $userHasSchool) {
            return $userHasSchool->getRole() === User::ROLE_CONSULTANT;
        });
    }
}
