<?php

namespace App\Form;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SchoolType extends AbstractType
{

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('schoolNumber')
            ->add('headmaster')
            ->add('phoneNumber')
            ->add('faxNumber')
            ->add('emailAddress')
            ->add('webpage')
            ->add('educationAuthority')
            ->add('schoolType')
            ->add('schoolOperator')
            ->add('particularity', TextareaType::class, [
                'required' => false
            ]);

        if ($this->stateCountry === 'bb') {
            $builder->add('flags', CheckboxType::class, [
                'label' => 'Teilnahme am Qualitätsprogramm',
                'property_path' => 'flags[quali]',
            ]);
        }

        if ($this->stateCountry === 'rp'
            && $builder->getOption('is_admin_area') === true) {
                $builder
                    ->add('auditEnd', DateType::class, [
                        'label' => 'Audit-Ende',
                        'input' => 'datetime',
                        'widget' => 'single_text',
                        'required' => true,
                        'constraints' => [new NotBlank()]
                    ])
                    ->add('consultant', EntityType::class, [
                        'class' => User::class,
                        'query_builder' => static function (EntityRepository $er) {
                            return $er
                                ->createQueryBuilder('u')
                                ->where('u.roles LIKE :roles')
                                ->setParameter('roles', '%"ROLE_CONSULTANT"%');
                        },
                        'choice_label' => 'getDisplayName',
                        'required' => false,
                        'placeholder' => '--- Bitte auswählen falls vorhanden ---',
                        'label' => UserHasSchool::ROLE_LABELS[User::ROLE_CONSULTANT],
                    ]);
        }

        $builder
            ->add('address', AddressType::class);
    }

        /**
         * @param OptionsResolver $resolver
         */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => School::class,
            'is_admin_area' => false,
        ]);
    }
}
