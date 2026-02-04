<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\QualityCheck\Result;
use App\Entity\QualityCircle\ActionPlanNew;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailNotificationService
{

    protected MailerInterface $mailer;

    protected string $stateCountry;

    protected User $networkingPoint;

    /**
     * @param MailerInterface $mailer
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(MailerInterface $mailer, ParameterBagInterface $parameterBag)
    {
        $this->mailer = $mailer;
        $this->stateCountry = $parameterBag->get('app_state_country');

        $person = new Person();
        $person->setLastName("Schulverpflegung Rheinland-Pfalz");
        $user = new User();
        $user->setPerson($person);
        $user->setEmail("schulverpflegung@dlr.rlp.de");

        $this->networkingPoint = $user;
    }

    public const NOTIFICATION_REQUIREMENT = 0.6;

    /**
     * @param Result $result
     * @return void
     */
    public function sendQualityCheckResultMail(Result $result): void
    {
        if ($result->getResultRatio() >= self::NOTIFICATION_REQUIREMENT && $this->stateCountry === 'rp') {
            $school = $result->getSchool();

            $this->sendNotificationMail(
                $this->networkingPoint,
                'Betreffend die Schule "' . $school->getName() . '"',
                'Der Nutzer '
                . $result->getFinalisedBy()->getDisplayName()
                . ' hat den Qualitäts-Check der Schule "'
                . $school->getName()
                . '" beendet. Die Schule "' . $school->getName()
                . '" hat über '
                . \round($result->getResultRatio() * 100)
                . '% bei den beantworteten Fragen erreicht, wobei die Quote bei den Nachhaltigkeitsfragen bei '
                . \round($result->getResultRatio(['sustainable' => true]) * 100) . '% lag!'
            );

            foreach ($school->getConsultants() as $consultant) {
                $this->sendNotificationMail(
                    $consultant->getUser(),
                    'Betreffend die Schule "' . $school->getName() . '"',
                    'Der Nutzer '
                    . $result->getFinalisedBy()->getDisplayName()
                    . ' hat den Qualitäts-Check der Schule "'
                    . $school->getName()
                    . '" beendet . Die Schule "' . $school->getName()
                    . '" hat über '
                    . \round($result->getResultRatio() * 100)
                    . '% bei den beantworteten Fragen erreicht, wobei die Quote bei den Nachhaltigkeitsfragen bei '
                    . \round($result->getResultRatio(true) * 100) . '% lag!'
                );
            }
        }
    }

    public function sendActionPlanMail(ActionPlanNew $actionPlan): void
    {
        if ($actionPlan->isCompleted() && $this->stateCountry === 'rp') {
            $school = $actionPlan->getToDo()->getSchool();

            $this->sendNotificationMail(
                $this->networkingPoint,
                'Betreffend die Schule "' . $school->getName() . '"',
                'Der Aktionsplan betreffend des To Do\'s "' . $actionPlan->getToDo()->getName() . '" der Schule "'
                . $actionPlan->getToDo()->getSchool()->getName() . '" wurde'
                . ($actionPlan->getNote() ? ' mit dem Hinweis "' . $actionPlan->getNote() . '"' : '')
                . ' abgeschlossen!'
            );

            foreach ($school->getConsultants() as $consultant) {
                $this->sendNotificationMail(
                    $consultant->getUser(),
                    'Betreffend die Schule "' . $school->getName() . '"',
                    'Der Aktionsplan betreffend des To Do\'s "' . $actionPlan->getToDo()->getName() . '" der Schule "'
                    . $actionPlan->getToDo()->getSchool()->getName() . '" wurde'
                    . ($actionPlan->getNote() ? ' mit dem Hinweis "' . $actionPlan->getNote() . '"' : '')
                    . ' abgeschlossen!'
                );
            }
        }
    }

    /**
     * @param User|null $user
     * @param string $subject
     * @param string $text
     * @return void
     */
    private function sendNotificationMail(User $user, string $subject, string $text): void
    {
        try {
            $email = (new TemplatedEmail())
                ->subject('Unser Schulessen - ' . $subject)
                ->from(new Address('rp@unser-schulessen.de', 'Unser Schulessen'))
                ->to($user->getEmail())
                ->htmlTemplate('emails/notification_mail.html.twig')
                ->context(
                    [
                        'user' => $user,
                        'text' => $text,
                    ]
                );

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            \dump($e->getMessage());
        }
    }
}
