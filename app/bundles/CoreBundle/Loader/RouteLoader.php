<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteLoader
 */
class RouteLoader extends Loader
{

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->dispatcher = $factory->getDispatcher();
    }

    /**
     * Load each bundles routing.php file
     *
     * @param mixed $resource
     * @param null  $type
     *
     * @return RouteCollection
     * @throws \RuntimeException
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "mautic" loader twice');
        }

        $event = new RouteEvent($this);
        $this->dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);

        $this->loaded = true;

        return $event->getCollection();
    }

    /**
     * @param mixed $resource
     * @param null  $type
     *
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return 'mautic' === $type;
    }
}
