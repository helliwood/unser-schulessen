<?php

namespace App\Form;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultantToSchoolType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['school'] instanceof School) {
            throw new \Exception('school not set!');
        }
        $builder->add('user', EntityType::class, [
            'class' => User::class,
            'query_builder' => static function (EntityRepository $er) use ($options) {
                return $er
                    ->createQueryBuilder('u')
                    ->leftJoin('u.userHasSchool', 'uhs', Join::WITH, 'uhs.school = :school')
                    ->setParameter('school', $options['school'])
                    ->where('uhs.school IS NULL')
                    ->andWhere('u.roles LIKE :roles')
                    ->setParameter('roles', '%"ROLE_CONSULTANT"%');
            },
            'choice_label' => 'getDisplayName',
            'required' => false,
            'placeholder' => '--- Bitte auswählen falls vorhanden ---',
            'label' => UserHasSchool::ROLE_LABELS[User::ROLE_CONSULTANT],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserHasSchool::class,
            'school' => null
        ]);
    }
}
