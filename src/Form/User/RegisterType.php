<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\User;

use App\Form\UserTrait\OnlyEmailType;
use App\Form\UserTrait\SetPasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class RegisterType extends AbstractUserType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('profile', OnlyEmailType::class, ['inherit_data' => true, 'label' => false]);
        $builder->add('password', SetPasswordType::class, ['inherit_data' => true, 'label' => false]);
    }
}
