<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container->setDefinition(
    'mautic.campaign.type.form',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaign'
    ));

$container->setDefinition(
    'mautic.campaignrange.type.action',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\EventType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaignevent'
    ));

$container->setDefinition(
    'mautic.campaign.type.campaignlist',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignListType',
        array(
            new Reference('mautic.factory')
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaign_list'
    ));

$container->setDefinition(
    'mautic.campaign.type.trigger.leadchange',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignEventLeadChangeType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaignevent_leadchange'
    ));

$container->setDefinition(
    'mautic.campaign.type.action.addremovelead',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaignevent_addremovelead'
    ));

$container->setDefinition(
    'mautic.campaign.type.canvassettings',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\EventCanvasSettingsType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaignevent_canvassettings'
    ));