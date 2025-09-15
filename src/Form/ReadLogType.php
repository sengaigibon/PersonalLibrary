<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\ReadLog;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReadLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate')
            ->add('finishDate')
            ->add('rating')
            ->add('notes')
            ->add('book', EntityType::class, [
                'class' => Book::class,
                'choice_label' => 'title',
                'placeholder' => 'Choose a book',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReadLog::class,
        ]);
    }
}
