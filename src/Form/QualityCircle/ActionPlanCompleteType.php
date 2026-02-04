<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 14.08.19
 * Time: 16:10
 */

namespace App\Form\QualityCircle;

use App\Entity\QualityCircle\ActionPlanNew;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionPlanCompleteType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('completed')
            ->add('note', TextareaType::class, [
                'required' => false,
                /*'constraints' => [
                    new NotBlank(['groups' => []])
                ]*/
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            if (! $event->getForm()->get('completed')->getData() && empty($event->getForm()->get('note')->getData())) {
                $event->getForm()->get('note')->addError(
                    new FormError('Muss für unerledigte Aktionspläne ausgefüllt werden!')
                );
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActionPlanNew::class,
        ]);
    }
}
