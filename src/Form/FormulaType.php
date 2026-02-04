<?php
/**
 * Created by PhpStorm.
 * User: victoria
 * Date: 04.07.19
 * Time: 10:05
 */

namespace App\Form;

use App\Entity\QualityCheck\Formula;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class FormulaType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //Regex matches  <,>,<=,>= space digit
        $builder
            ->add('formula_true', TextType::class, [
                'constraints' => [new NotBlank(), new Regex([
                    'pattern' => '/^(<=?|>=?)[\s]?\d+$/',
                    'message' => 'Die Formel muss mit "< <= == != >= >" beginnen, gefolgt von einer Zahl.'
                ])],
                'label' => 'Trifft zu',
                'required' => false
            ])
            ->add('formula_false', TextType::class, [
                'constraints' => [new NotBlank(), new Regex([
                    'pattern' => '/^(<=?|>=?)[\s]?\d+$/',
                    'message' => 'Die Formel muss mit "< <= == != >= >" beginnen, gefolgt von einer Zahl.'
                ])],
                'label' => 'Trifft nicht zu',
                'required' => false
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formula::class,
        ]);
    }
}
