<?php

namespace AppBundle;

use AppBundle\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('author', TextType::class, array(
                'attr' => array(
                    'size' => "50",
                ),
            ))
            ->add('date', DateType::class, array(
                'data' => new \DateTime('now'),
            ))
            ->add('note', TextareaType::class, array(
                'attr' => array(
                    'cols' => "50",
                    'rows' => "10",
                ),
            ))
            ->add('save', SubmitType::class, array('label' => 'Add Note'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Note',
        ));
    }
}