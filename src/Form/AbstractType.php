<?php

namespace App\Form;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AbstractType extends \Symfony\Component\Form\AbstractType
{
    /**
     * @var string
     */
    protected $stateCountry;

    /**
     * @param ParameterBagInterface $params
     */
    public function __construct(ParameterBagInterface $params)
    {
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * @return string
     */
    public function getStateCountry(): string
    {
        return $this->stateCountry;
    }
}
