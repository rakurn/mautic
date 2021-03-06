<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//Check to see if the entire page should be displayed or just main content
if ($tmpl == 'index'):
    $view->extend('MauticApiBundle:Client:index.html.php');
endif;
?>

<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered client-list">
        <thead>
        <tr>
            <th class="col-client-actions"></th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'client',
                'orderBy'    => 'c.name',
                'text'       => 'mautic.api.client.thead.name',
                'default'    => true,
                'class'      => 'col-client-name'
            ));
            ?>
            <th class="visible-md visible-lg col-client-publicid"><?php echo $view['translator']->trans('mautic.api.client.thead.publicid'); ?></th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'client',
                'orderBy'    => 'c.id',
                'text'       => 'mautic.api.client.thead.id',
                'class'      => 'visible-md visible-lg col-client-id'
            ));
            ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                        'item'      => $item,
                        'templateButtons' => array(
                            'edit'      => $permissions['edit'],
                            'delete'    => $permissions['delete']
                        ),
                        'routeBase' => 'client',
                        'langVar'   => 'api.client',
                        'pull'      => 'left'
                    ));
                    ?>
                </td>
                <td>
                    <?php echo $item->getName(); ?>
                </td>
                <td class="visible-md visible-lg">
                    <?php echo $item->getPublicId(); ?>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems" => count($items),
        "page"       => $page,
        "limit"      => $limit,
        "baseUrl"    =>  $view['router']->generate('mautic_client_index'),
        'sessionVar' => 'client',
        'tmpl'       => $tmpl
    )); ?>
</div>