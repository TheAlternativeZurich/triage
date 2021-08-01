<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Event;

use App\Form\DataTransformer\UserToEmailTransformer;
use App\Form\EventTrait\EditEventTraitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ModerateEventType extends AbstractEventType
{
    private $userToEmailTransformer;

    public function __construct(UserToEmailTransformer $transformer)
    {
        $this->userToEmailTransformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('event', EditEventTraitType::class, ['inherit_data' => true, 'label' => false, 'allow_time_edit' => true]);

        $builder->add('lecturer', TextType::class, ['help' => 'lecturer_help']);
        $builder->add('minRegistrations', NumberType::class, ['help' => 'min_registrations_help']);
        $builder->add('experience', TextareaType::class, ['help' => 'experience_help', 'disabled' => true]);
        $builder->add('identifier', TextType::class, ['help' => 'identifier_help']);

        $builder->get('lecturer')->addModelTransformer($this->userToEmailTransformer);
    }
}
