<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeeType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->add('salutation', ChoiceType::class, [
            'choices' => [
                'Herr' => 'Herr',
                'Frau' => 'Frau',
                'Organisation' => 'Organisation',
            ],
            'data' => 'Herr',
            'property_path' => 'person.salutation',
            'required' => false,
        ]);
        $builder->add('academic_title', TextType::class, [
            'property_path' => 'person.academic_title',
            'required' => false,
        ]);
        $builder->add('first_name', TextType::class, [
            'property_path' => 'person.first_name',
            'required' => false,
        ]);
        $builder->add('last_name', TextType::class, [
            'property_path' => 'person.last_name',
            'required' => true,
            'constraints' => [
                new NotBlank(),
//                new Length(['min' => 2]),
            ],
        ]);

        $builder->add('email', EmailType::class, [
            'required' => true,
        ]);

        $choices = ['Administrator' => 'ROLE_ADMIN'];
        switch ($this->stateCountry) {
            case 'rp':
                $choices['Ernährungsberater'] = 'ROLE_CONSULTANT';
                break;
            case 'by':
                $choices['Ernährungscoach'] = 'ROLE_CONSULTANT';
                break;
            case 'he':
                $choices['Berater VNS/ CleZi'] = 'ROLE_CONSULTANT';
                break;
        }

        $builder->add('roles', ChoiceType::class, [
            'expanded' => true,
            'multiple' => true,
            'label' => 'Berechtigungen',
            'required' => false,
            'choices' => $choices,
        ]);

        if ($builder->getOption('edit_form') === true) {
            $builder->add('newPassword', PasswordType::class, [
                'required' => false,
                'label' => 'Neues Passwort',
                'help' => "Freilassen um das Passwort beizubehalten",
                'attr' => [
                    'autocomplete' => 'new-password'
                ],
            ]);
        } else {
            $builder->add('newPassword', PasswordType::class, [
                'required' => true,
                'label' => 'Passwort',
                'attr' => [
                    'autocomplete' => 'new-password'
                ],
                'constraints' => [
                    new NotBlank(),
//                    new Length(['min' => 2]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'edit_form' => false,
            'is_consultant' => false,
        ]);
    }
}
