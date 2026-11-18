<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\ReadLog;
use App\Repository\BookRepository;
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
                'query_builder' => fn (BookRepository $repository) => $repository->createQueryBuilder('book')
                    ->where('book.owner = :owner')
                    ->setParameter('owner', $options['owner_id'])
                    ->orderBy('book.title', 'ASC'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReadLog::class,
        ]);
        $resolver->setRequired('owner_id');
        $resolver->setAllowedTypes('owner_id', 'int');
    }
}
