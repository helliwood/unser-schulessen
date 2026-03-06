<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-27
 * Time: 16:05
 */

namespace App\Menu;

use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class MenuBuilder
 * @package App\Menu
 */
class MenuBuilder
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var string
     */
    private $stateCountry;

    /**
     * MenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param Security $security
     * @param MasterDataService $masterDataService
     * @param QualityCheckService $qualityCheckService
     * @param ParameterBagInterface $params
     */
    public function __construct(
        FactoryInterface $factory,
        Security $security,
        ParameterBagInterface $params
    ) {
        $this->factory = $factory;
        $this->security = $security;
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * @return ItemInterface
     * @throws \Exception
     */
    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('home', ['label' => 'Überblick', 'route' => 'home'])
            ->setChildrenAttribute('class', 'nav');

        if ($this->security->isGranted('ROLE_SCHOOL_AUTHORITY')) {
            $menu->addChild('dashboard', ['label' => 'Dashboard', 'route' => 'school_authority_dashboard'])
                ->setAttribute('data-icon', 'fas fa-home')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $menu->addChild('school_authority_schools', ['label' => 'Meine Schulen', 'route' => 'school_authority_schools'])
                ->setAttribute('data-icon', 'fas fa-building')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $menu->addChild('school_authority_surveys', ['label' => 'Umfragen', 'route' => 'school_authority_surveys'])
                ->setAttribute('data-icon', 'fas fa-chart-bar')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $menu->addChild('school_authority_profile', ['label' => 'Trägerprofil', 'route' => 'school_authority_profile'])
                ->setAttribute('data-icon', 'fas fa-user')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');
        } elseif ($this->security->getUser() && ! \is_null($this->security->getUser()->getCurrentSchool())) {
            $menu->addChild('dashboard', ['label' => 'Überblick', 'route' => 'home'])
                ->setAttribute('data-icon', 'fas fa-home')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');


            if (($this->stateCountry === 'rp' && $this->security->getUser()->getCurrentSchool()->getAuditEnd() >= new \DateTime())
                || $this->stateCountry !== 'rp'
                || $this->security->isGranted('ROLE_ADMIN')
            ) {
                if ($this->security->isGranted(User::ROLE_SCHOOL_AUTHORITIES)) {
                    $md = $menu->addChild('master_data', ['label' => 'Unsere Schule', 'route' => 'master_data_home'])
                        ->setAttribute('data-icon', 'fas fa-university')
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                    $md->addChild('master_data_members_inactive', ['label' => 'Inaktive Mitarbeiter', 'route' => 'master_data_members_inactive'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                    $md->addChild('master_data_edit_school', ['label' => 'Schule bearbeiten', 'route' => 'master_data_edit_school'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                    $md->addChild('master_data_edit', ['label' => 'Stammdaten bearbeiten', 'route' => 'master_data_edit'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                    $md->addChild('master_data_show', ['label' => 'Stammdaten anzeigen', 'route' => 'master_data_show'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                }

                if (! \is_null($this->security->getUser())) {
                    $menu->addChild('quality_check', ['label' => 'Qualitäts-Check', 'route' => 'quality_check_home'])
                        ->setAttribute('data-icon', 'fas fa-clipboard-check')
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                }

                if (! \is_null($this->security->getUser())
                    && $this->security->isGranted('ROLE_KITCHEN')) {
                    $menu->addChild('quality_circle', ['label' => 'Qualitätsprozess', 'route' => 'quality_circle_home'])
                        ->setAttribute('data-icon', 'fas fa-circle-notch')
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link')
                        ->addChild('todo_new', ['label' => 'Neues To Do', 'route' => 'quality_circle_todo_new'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                }

                if (! \is_null($this->security->getUser())) {
                    $menu->addChild('survey', ['label' => 'Zufriedenheitsumfragen', 'route' => 'survey_home'])
                        ->setAttribute('data-icon', 'fas fa-chart-bar')
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link')
                        ->addChild('survey_new', ['label' => 'Neue Umfrage', 'route' => 'survey_new'])
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');

                    $menu->addChild('food-survey', ['label' => 'Teller-Check', 'route' => 'food_survey_home'])
                        ->setAttribute('data-icon', 'fas fa-chart-bar')
                        ->setAttribute('class', 'w-100')
                        ->setLinkAttribute('class', 'nav-link');
                }

                $menu->addChild('master_data_media', ['label' => 'Dokumente', 'route' => 'master_data_media_home'])
                    ->setAttribute('data-icon', 'fas fa-file')
                    ->setAttribute('class', 'w-100')
                    ->setLinkAttribute('class', 'nav-link');
            }
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $admin = $menu->addChild('admin', ['label' => 'Administration', 'route' => 'admin_home'])
                ->setAttribute('data-icon', 'fas fa-cogs')
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $admin->addChild('school', ['label' => 'Schulen', 'route' => 'admin_school_home'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $admin->addChild('school_authority', ['label' => 'Schulträger', 'route' => 'admin_school_authority_home'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $admin->addChild('questionnaire', ['label' => 'Fragebögen', 'route' => 'admin_questionnaire_home'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link')
                ->addChild('questionnaire_new', ['label' => 'Neuer Fragebogen', 'route' => 'admin_questionnaire_new'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $admin->addChild('survey', ['label' => 'Umfragen', 'route' => 'admin_survey_home'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link')
                ->addChild('survey_new', ['label' => 'Neue Kategorie', 'route' => 'admin_survey_new'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');

            $admin->addChild('employee', ['label' => 'Mitarbeiter', 'route' => 'admin_employee_home'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link')
                ->addChild('employee_new', ['label' => 'Neuer Mitarbeiter', 'route' => 'admin_employee_new'])
                ->setAttribute('class', 'w-100')
                ->setLinkAttribute('class', 'nav-link');
        }
        return $menu;
    }
}
