<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadLeadListData
 *
 * @package Mautic\LeadBundle\DataFixtures\ORM
 */
class LoadLeadListData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $translator = $this->container->get('translator');
        $adminUser  = $this->getReference('admin-user');

        $list = new LeadList();
        $list->setName($translator->trans('mautic.lead.list.us', array(), 'fixtures'));
        $list->setAlias($translator->trans('mautic.lead.list.usalias', array(), 'fixtures'));
        $list->setCreatedBy($adminUser);
        $list->setIsGlobal(true);
        $list->setFilters(array(
            array(
                'glue'      => 'and',
                'type'      => 'lookup',
                'field'     => 'country',
                'operator'  => '=',
                'filter'    => 'United States',
                'display'   => ''
            )
        ));

        $this->setReference('lead-list', $list);
        $manager->persist($list);
        $manager->flush();

       $this->container->get('mautic.factory')->getModel('lead.list')->regenerateListLeads($list);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }
}