<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');

$js = <<<JS
Mautic.handleCallback("$network", "$csrfToken", "$code", "$callbackUrl", "{$view['translator']->trans('mautic.social.oauth.popupblocked')}");
JS;
$view['slots']->addScriptDeclaration($js, 'bodyClose');