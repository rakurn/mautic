<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Model;

use Mautic\CoreBundle\Model\FormModel;

/**
 * Class AddonModel
 */
class AddonModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\AddonBundle\Entity\AddonModel
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticAddonBundle:Addon');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'addon:addons';
    }

    /**
     * {@inheritdoc}
     *
     * @param object $entity
     *
     * @return bool  Force browser refresh
     */
    public function togglePublishStatus($entity)
    {
        parent::togglePublishStatus($entity);

        //clear the cache
        /** @var \Mautic\CoreBundle\Helper\CacheHelper $cacheHelper */
        $cacheHelper = $this->factory->getHelper('cache');
        $cacheHelper->clearCache();

        return true;
    }
}
