<?php

namespace App\Form;

use App\Entity\QualityCheck\Questionnaire;
use App\Repository\QuestionnaireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionnaireType extends AbstractType
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
            ->add('name')
            ->add('basedOn', EntityType::class, [
                'label' =>'Basierend auf',
                'class' => Questionnaire::class,
                'query_builder' => static function (QuestionnaireRepository $qr) {
                    return $qr->createQueryBuilder('q')
                        ->where('q.state IN (:states)')
                        ->setParameter('states', [Questionnaire::STATE_ACTIVE, Questionnaire::STATE_ARCHIVED])
                        ->orderBy('q.date', 'DESC');
                },
                'mapped' => false
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Questionnaire::class,
        ]);
    }
}
