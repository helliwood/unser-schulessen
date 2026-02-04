<?php
/**
 * Created by PhpStorm.
 * User: victoria
 * Date: 08.08.19
 * Time: 11:10
 */

namespace App\Form;

use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\IdeaboxIcon;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdeaboxType extends AbstractType
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
            ->add('idea', TextareaType::class, [
                'label' => 'idea'
            ])->add('ideaboxIcons', EntityType::class, [
                'class' => IdeaboxIcon::class,
                'choice_label' => static function (IdeaboxIcon $ideaboxIcon) {
                    return '<span class="ml-1">' . $ideaboxIcon->getCategory() . ' ' . '<i class="' . $ideaboxIcon->getIcon() . '"></i></span>';
                },
                'expanded' => 'true',
                'multiple' => 'false'
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ideabox::class,
        ]);
    }
}
