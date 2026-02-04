<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 14.08.19
 * Time: 16:10
 */

namespace App\Form\QualityCircle;

use App\Entity\QualityCircle\ToDoNew;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToDoType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ToDoNew $toDo */
        //$toDo = $builder->getData();
        $builder->add('note', TextareaType::class, [
            'required' => false
            //'required' => ! $toDo->getCompleted(),
            //'help' => ! $toDo->getCompleted() ? 'Begründen Sie warum das ToDo nicht geschafft wurde.' : ''
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToDoNew::class,
        ]);
    }
}
