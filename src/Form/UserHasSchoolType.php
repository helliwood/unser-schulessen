<?php

namespace App\Form;

use App\Entity\UserHasSchool;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserHasSchoolType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['add_email_field']) {
            $builder
                ->add('email', EmailType::class, [
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [new NotBlank(), new Email()]
                ]);
        }

        if ($options['data'] instanceof UserHasSchool &&
            $options['data']->getState() === UserHasSchool::STATE_REQUESTED) {
            $builder->add('sendInvitation', CheckboxType::class, [
                'mapped' => false,
            ]);
        }

        $builder->add('personType', null, [
            'placeholder' => '-- Bitte wählen --',
        ]);

        switch ($this->getStateCountry()) {
            case 'sl':
                $builder->add('role', ChoiceType::class, [
                    'placeholder' => '-- Bitte wählen --',
                    'choices' => UserHasSchool::ROLES_SL
                ]);
                break;

            case 'rp':
                $builder->add('role', ChoiceType::class, [
                    'placeholder' => '-- Bitte wählen --',
                    'choices' => $options['show_consultant_role'] ? UserHasSchool::ROLES_ADMIN_AREA : UserHasSchool::ROLES
                ]);
                break;

            default:
                $builder->add('role', ChoiceType::class, [
                    'placeholder' => '-- Bitte wählen --',
                    'choices' => UserHasSchool::ROLES
                ]);
                break;
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserHasSchool::class,
            'add_email_field' => false,
            'show_consultant_role' => false,
        ]);
    }
}
