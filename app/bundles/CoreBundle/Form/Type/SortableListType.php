<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SortableListType
 */
class SortableListType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('list', 'collection', array(
            'label'        => false,
            'options'      => array(
                'label'    => false,
                'required' => false,
                'attr'     => array(
                    'class'         => 'form-control',
                    'preaddon'      => $options['remove_icon'],
                    'preaddon_attr' => array(
                        'onclick' => $options['remove_onclick']
                    ),
                    'postaddon'     => $options['sortable']
                ),
                'constraints' => ($options['option_notblank']) ? array(
                    new NotBlank(
                        array('message' => 'mautic.form.lists.notblank')
                    )
                ) : array(),
                'error_bubbling' => true
            ),
            'allow_add'    => true,
            'allow_delete' => true,
            'prototype'    => true,
            'constraints'  => ($options['option_required']) ? array(
                new Count(array(
                    'minMessage' => 'mautic.form.lists.count',
                    'min'        => 1
                ))
            ) : '',
            'error_bubbling' => false
        ));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            //reorder list in case keys were dynamically removed
            $data = $event->getData();
            if (isset($data['list'])) {
                $data['list'] = array_values($data['list']);
                $event->setData($data);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['isSortable'] = (!empty($options['sortable']));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'remove_onclick'  => 'Mautic.removeFormListOption(this);',
            'option_required' => true,
            'option_notblank' => true,
            'remove_icon'     => 'fa fa-times',
            'sortable'        => 'fa fa-ellipsis-v handle'
        ));

        $resolver->setOptional(array(
            'sortable',
            'remove_onclick',
            'option_required',
            'option_notblank',
            'remove_icon'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sortablelist';
    }
}
