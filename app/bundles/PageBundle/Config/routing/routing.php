<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_page_buildertoken_index', new Route('/pages/buildertokens/{page}',
    array(
        '_controller' => 'MauticPageBundle:SubscribedEvents\BuilderToken:index',
        'page'        => 1
    ),
    array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_page_index', new Route('/pages/{page}',
    array(
        '_controller' => 'MauticPageBundle:Page:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_page_action', new Route('/pages/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticPageBundle:Page:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_page_tracker', new Route('/p/mtracking.gif',
    array(
        '_controller' => 'MauticPageBundle:Public:trackingImage'
    )
));

$collection->add('mautic_page_public', new Route('/p/page/{slug1}/{slug2}/{slug3}',
    array(
        '_controller' => 'MauticPageBundle:Public:index',
        "slug2"       => '',
        "slug3"       => ''
    )
));

$collection->add('mautic_page_redirect', new Route('/p/redirect/{redirectId}',
    array(
        '_controller' => 'MauticPageBundle:Public:redirect',
    )
));

return $collection;
