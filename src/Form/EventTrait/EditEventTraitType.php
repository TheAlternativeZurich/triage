<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\EventTrait;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditEventTraitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class);
        $builder->add('description', TextareaType::class);
        if ($options['allow_time_edit']) {
            $builder->add('startDate', DateTimeType::class, ['widget' => 'single_text', 'help' => 'start_date_help']);
        } else {
            $builder->add('startDate', DateType::class, ['widget' => 'single_text', 'help' => 'start_date_help']);
        }
        $builder->add('author', TextType::class, ['required' => false]);
        $builder->add('parts', NumberType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'trait_event',
            'data_class' => Event::class,
            'allow_time_edit' => false,
        ]);
    }
}
