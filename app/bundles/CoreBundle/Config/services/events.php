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
use Symfony\Component\DependencyInjection\Parameter;

//Mautic event listener
$container->setDefinition(
    'mautic.core.subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\CoreSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

$container->setDefinition(
    'mautic.core.auditlog.subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\AuditLogSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');

//Database table prefix
$container->setDefinition ('mautic.tblprefix_subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber'
    )
)->addTag('doctrine.event_subscriber');

$container->setDefinition(
    'mautic.exception.listener',
    new Definition(
        'Mautic\CoreBundle\EventListener\ExceptionListener',
        array(
            'MauticCoreBundle:Exception:show',
            new Reference('monolog.logger.mautic')
        )
    )
)
    ->addTag('kernel.event_listener', array(
        'event'  => 'kernel.exception',
        'method' => 'onKernelException',
        'priority' => 255
    ));

$container->setDefinition(
    'mautic.core.configbundle.subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\ConfigSubscriber',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');
