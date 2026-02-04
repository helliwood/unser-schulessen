<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 10:22
 */

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Class AbstractController
 * @package App\Controller
 * @method \App\Entity\User getUser
 */
class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $stateCountry;

    /**
     * AbstractController constructor.
     * @param MailerInterface       $mailer
     * @param ParameterBagInterface $params
     */
    public function __construct(MailerInterface $mailer, ParameterBagInterface $params)
    {
//        parent::__construct($mailer, $params);
        $this->mailer = $mailer;
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * @param string $message
     * @return bool
     */
    protected function getSuccessMessage(string $message = "Der Datensatz wurde erfolgreich gespeichert!"): bool
    {

        $this->addFlash(
            'success',
            $message
        );

        return true;
    }

    /**
     * @param string $message
     * @return bool
     */
    protected function getErrorMessage(string $message = "Die Anfrage wurde abgelehnt!"): bool
    {
        $this->addFlash(
            'danger',
            $message
        );

        return true;
    }

    /**
     * @return string
     */
    public function getStateCountry(): string
    {
        return $this->stateCountry;
    }
}
