<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\ORM\PersistentCollection;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\CampaignEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class CampaignModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class CampaignModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getEventRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadRepository
     */
    public function getCampaignLeadRepository ()
    {
        return $this->em->getRepository('MauticCampaignBundle:Lead');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase ()
    {
        return 'campaign:campaigns';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(array('Campaign'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();

        return $formFactory->create('campaign', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity ($id = null)
    {
        if ($id === null) {
            return new Campaign();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent ($action, &$entity, $isNew = false, $event = false)
    {
        if ($entity instanceof \Mautic\CampaignBundle\Entity\Lead) {
            return;
        }

        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(array('Campaign'));
        }

        switch ($action) {
            case "pre_save":
                $name = CampaignEvents::CAMPAIGN_PRE_SAVE;
                break;
            case "post_save":
                $name = CampaignEvents::CAMPAIGN_POST_SAVE;
                break;
            case "pre_delete":
                $name = CampaignEvents::CAMPAIGN_PRE_DELETE;
                break;
            case "post_delete":
                $name = CampaignEvents::CAMPAIGN_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\CampaignEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * @param Campaign $entity
     * @param          $sessionEvents
     * @param          $sessionConnections
     * @param          $deletedEvents
     */
    public function setEvents (Campaign &$entity, $sessionEvents, $sessionConnections, $deletedEvents)
    {
        $existingEvents = $entity->getEvents();

        $events = $tempIds = $hierarchy = $parentUpdated = array();

        //set the events from session
        foreach ($sessionEvents as $id => $properties) {
            $isNew = (!empty($properties['id']) && isset($existingEvents[$properties['id']])) ? false : true;
            $event = !$isNew ? $existingEvents[$properties['id']] : new Event();

            foreach ($properties as $f => $v) {
                if ($f == 'id' && strpos($v, 'new') === 0) {
                    //set the temp ID used to be able to match up connections
                    $event->setTempId($v);
                }

                if (in_array($f, array('id', 'order', 'parent')))
                    continue;

                $func = "set" . ucfirst($f);
                if (method_exists($event, $func)) {
                    $event->$func($v);
                }
            }

            $event->setCampaign($entity);
            $events[$id] = $event;

            if (strpos($id, 'new') === false) {
                $tempIds[$event->getTempId()] = $id;
            }
        }

        foreach ($deletedEvents as $deleteMe) {
            if (isset($existingEvents[$deleteMe])) {
                $entity->removeEvent($existingEvents[$deleteMe]);
                unset($events[$deleteMe]);
            }
        }

        //loop again now to assign parents and cleanup endpoints which must be done after $tempIds has been populated
        foreach ($events as $id => $e) {
            $canvasSettings = $e->getCanvasSettings();

            if (!isset($canvasSettings['endpoints'])) {
                $canvasSettings['endpoints'] = array();
            }

            if (isset($sessionConnections[$id])) {
                foreach ($sessionConnections[$id] as $sourceEndpoint => $children) {
                    foreach ($children as $child => $targetEndpoint) {
                        if (!isset($events[$child])) {
                            if (strpos($child, 'new') === false && in_array($child, $tempIds)) {
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);
                                $childId = array_search($child, $tempIds);
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$childId]);
                            } else {
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);
                            }

                            unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);

                        } elseif (!empty($targetEndpoint)) {
                            $anchor = in_array($sourceEndpoint, array('yes', 'no')) ? $sourceEndpoint : null;
                            $events[$child]->setDecisionPath($anchor);

                            $events[$child]->setParent($events[$id]);
                            $hierarchy[$child] = $id;
                            $parentUpdated[] = $child;
                        } elseif (!in_array($child, $parentUpdated)) {
                            if (strpos($child, 'new') === false && in_array($child, $tempIds)) {
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);
                                $childId = array_search($child, $tempIds);
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$childId]);
                            } else {
                                unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);
                            }

                            unset($canvasSettings['endpoints'][$sourceEndpoint][$child]);

                            $events[$child]->removeParent();
                            $hierarchy[$child] = 'null';
                        }
                    }
                }
            } else {
                //get the parent for ordering
                $parent = $events[$id]->getParent();
                $hierarchy[$id] = ($parent !== null) ? $parent->getId() : 'null';
            }

            //cleanup endpoints while here
            foreach ($canvasSettings['endpoints'] as $sourceEndpoint => &$targets) {
                foreach ($targets as $targetId => $targetEndpoint) {
                    //check to see if there are both a temp ID and ID for target
                    if (strpos($targetId, 'new') !== false && isset($tempIds[$targetId]) && isset($targets[$tempIds[$targetId]])) {
                        //campaign has been edited
                        unset($targets[$targetId]);
                    }
                }
            }

            $e->setCanvasSettings($canvasSettings);
            $entity->addEvent($id, $e);
        }

        //set event order used when querying the events
        $this->buildOrder($hierarchy, $events, $entity);

        uasort($events, function ($a, $b) {
            $aOrder = $a->getOrder();
            $bOrder = $b->getOrder();
            if ($aOrder == $bOrder) {
                return 0;
            }

            return ($aOrder < $bOrder) ? -1 : 1;
        });

        return $events;
    }

    /**
     * @param          $hierarchy
     * @param          $events
     * @param Campaign $entity
     * @param string   $root
     * @param int      $order
     */
    private function buildOrder ($hierarchy, &$events, &$entity, $root = 'null', $order = 1)
    {
        $count = count($hierarchy);

        foreach ($hierarchy as $eventId => $parent) {
            if ($parent == $root || $count === 1) {
                $events[$eventId]->setOrder($order);
                $entity->addEvent($eventId, $events[$eventId]);

                unset($hierarchy[$eventId]);
                if (count($hierarchy)) {
                    $this->buildOrder($hierarchy, $events, $entity, $eventId, $order + 1);
                }
            }
        }
    }

    /**
     * Gets array of custom events from bundles subscribed CampaignEvents::CAMPAIGN_ON_BUILD
     *
     * @return mixed
     */
    public function getEvents ()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = array();
            $event  = new Events\CampaignBuilderEvent($this->translator);
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_BUILD, $event);
            $events['decision']     = $event->getLeadDecisions();
            $events['systemaction'] = $event->getSystemChanges();
            $events['action']       = $event->getActions();
        }

        return $events;
    }

    /**
     * Proxy for EventModel::triggerEvent
     *
     * @param string $eventType
     * @param mixed  $eventDetails
     * @param string $eventTypeId
     *
     * @return bool|mixed
     */
    public function triggerEvent ($eventType, $eventDetails = null, $eventTypeId = null)
    {
        /** @var \Mautic\CampaignBundle\Model\EventModel $eventModel */
        $eventModel = $this->factory->getModel('campaign.event');

        return $eventModel->triggerEvent($eventType, $eventDetails, $eventTypeId);
    }

    /**
     * Gets the campaigns a specific lead is part of
     *
     * @param Lead $lead
     * @param bool $forList
     */
    public function getLeadCampaigns (Lead $lead = null, $forList = false)
    {
        static $campaigns = array();
        $leadModel = $this->factory->getModel('lead');

        if ($lead === null) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $lead = $leadModel->getCurrentLead();
        }

        if (!isset($campaigns[$lead->getId()])) {
            $repo   = $this->getRepository();
            $leadId = $lead->getId();
            //get the campaigns the lead is currently part of
            $campaigns[$leadId] = $repo->getPublishedCampaigns(null, $lead->getId(), $forList);
        }

        return $campaigns[$lead->getId()];
    }

    /**
     * Gets a list of published campaigns
     *
     * @param bool $forList
     *
     * @return array
     */
    public function getPublishedCampaigns ($forList = false)
    {
        static $campaigns = array();

        if (empty($campaigns)) {
            $campaigns = $this->getRepository()->getPublishedCampaigns(null, null, $forList);
        }

        return $campaigns;
    }

    /**
     * Add lead to the campaign
     *
     * @param Campaign $campaign
     * @param          $lead
     */
    public function addLead (Campaign $campaign, $lead, $manuallyAdded = false, $persist = true)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
        }

        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if ($campaignLead != null) {
            if ($manuallyAdded && $campaignLead->wasManuallyRemoved()) {
                $campaignLead->setManuallyRemoved(false);
                $campaignLead->setManuallyAdded(true);
                $this->saveEntity($campaignLead, false);
            } else {
                //campaign lead already exists
                return;
            }
        } else {
            $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($lead);
            $campaignLead->setManuallyAdded($manuallyAdded);
            $campaignLead->setDateAdded(new \DateTime());
            $campaign->addLead($lead->getId(), $campaignLead);

            if ($persist) {
                $this->saveEntity($campaign, false);
            }
        }

        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
            $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'added');
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
        }
    }

    /**
     * Add lead(s) to the campaign
     *
     * @param Campaign $campaign
     * @param array    $leads
     */
    public function addLeads (Campaign $campaign, array $leads, $manuallyAdded = false, $persist = true)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
                $lead   = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
                'lead'     => $lead,
                'campaign' => $campaign
            ));

            if ($campaignLead != null) {
                if ($manuallyAdded && $campaignLead->wasManuallyRemoved()) {
                    $campaignLead->setManuallyRemoved(false);
                    $campaignLead->setManuallyAdded(true);
                    $this->saveEntity($campaignLead, false);
                } else {
                    return;
                }
            } else {
                $campaignLead = new \Mautic\CampaignBundle\Entity\Lead();
                $campaignLead->setCampaign($campaign);
                $campaignLead->setDateAdded(new \DateTime());
                $campaignLead->setLead($lead);
                $campaign->addLead($lead->getId(), $campaignLead);
                if ($persist) {
                    $this->saveEntity($campaign, false);
                }
            }

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'added');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }

            unset($campaignLead);
        }

        if ($persist) {
            $this->saveEntity($campaign, false);
        }
    }

    /**
     * Remove lead from the campaign
     *
     * @param Campaign $campaign
     * @param          $lead
     * @param bool     $manuallyRemoved
     */
    public function removeLead (Campaign $campaign, $lead, $manuallyRemoved = false, $persist = true)
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);

        }

        $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
            'lead'     => $lead,
            'campaign' => $campaign
        ));

        if (!$campaignLead) {
            return;
        }

        if ($manuallyRemoved && $campaignLead->wasManuallyAdded()) {
            //lead was manually added and now manually removed so get rid of it
            $campaign->removeLead($campaignLead);
            if ($persist) {
                $this->saveEntity($campaign, false);
            }
        } elseif ($campaignLead->wasManuallyAdded()) {
            //don't do anything because this lead was manually added
            return;
        } else {
            $campaign->removeLead($campaignLead);
            if ($persist) {
                $this->saveEntity($campaign, false);
            }
        }

        //remove scheduled events if the lead was removed
        $this->removeScheduledEvents($campaign, $lead);

        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
            $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'removed');
            $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
        }
    }

    /**
     * Remove lead(s) from the campaign
     *
     * @param Campaign $campaign
     * @param array    $leads
     * @param bool     $manuallyRemoved
     */
    public function removeLeads (Campaign $campaign, array $leads, $manuallyRemoved = false, $persist = true)
    {
        foreach ($leads as $lead) {

            if (!$lead instanceof Lead) {
                $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
                $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            }

            $campaignLead = $this->getCampaignLeadRepository()->findOneBy(array(
                'lead'     => $lead,
                'campaign' => $campaign
            ));

            if ($campaignLead == null) {
                //doesn't exist
                unset($campaignLead);
                continue;
            }

            if ($manuallyRemoved && $campaignLead->wasManuallyAdded()) {
                //lead was manually added and now manually removed so get rid of it
                $campaign->removeLead($campaignLead);
                if ($persist) {
                    $this->saveEntity($campaign, false);
                }
            } elseif ($campaignLead->wasManuallyAdded()) {
                //don't do anything because this lead was manually added
                return;
            } else {
                $campaign->removeLead($campaignLead);
            }

            if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_LEADCHANGE)) {
                $event = new Events\CampaignLeadChangeEvent($campaign, $lead, 'removed');
                $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_LEADCHANGE, $event);
            }

            //remove scheduled events if the lead was removed
            $this->removeScheduledEvents($campaign, $lead);
        }

        if ($persist) {
            $this->saveEntity($campaign, false);
        }
    }

    /**
     * Get event log for a campaign
     *
     * @param      $campaign
     * @param null $event
     * @param null $leads
     *
     * @return mixed
     */
    public function getEventLog ($campaign, $event = null, $leads = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;
        if (is_array($event)) {
            $eventId = $event['id'];
        } elseif ($event instanceof Event) {
            $eventId = $event->getId();
        } else {
            $eventId = $event;
        }

        if ($leads instanceof PersistentCollection) {
            $leads = array_keys($leads->toArray());
        }

        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog')->getCampaignLog($campaignId, $eventId, $leads);
    }

    /**
     * Get details of leads in a campaign
     *
     * @param      $campaign
     * @param null $leads
     *
     * @return mixed
     */
    public function getLeadDetails ($campaign, $leads = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

        if ($leads instanceof PersistentCollection) {
            $leads = array_keys($leads->toArray());
        }

        return $this->em->getRepository('MauticCampaignBundle:Lead')->getLeadDetails($campaignId, $leads);
    }

    /**
     * @param      $campaign
     * @param bool $unlock
     */
    public function saveEntity ($campaign, $unlock = true)
    {
        parent::saveEntity($campaign, $unlock);

        //update leads
        if (empty($campaign->leadsRebuilt)) {
            $changes = $campaign->getChanges();
            $removed = (!empty($changes['lists']) && isset($changes['lists']['removed'])) ? array_keys($changes['lists']['removed']) : null;
            $this->buildCampaignLeads($campaign, $removed);
        }
    }

    /**
     * @param      $campaign
     * @param null $removedLists
     */
    public function buildCampaignLeads ($campaign, $removedLists = null)
    {
        $lists = $campaign->getLists();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $this->factory->getModel('lead.list');
        $leads     = $listModel->getLeadsByList($lists, true);

        foreach ($leads as $list => $listLeads) {
            $this->addLeads($campaign, $listLeads, false, false);
        }

        if ($removedLists != null) {
            $campaignListIds = array_keys($lists->toArray());
            $leads           = $listModel->getLeadsByList($removedLists, true);
            $listRepo        = $this->em->getRepository('MauticLeadBundle:LeadList');

            foreach ($leads as $list => $listLeads) {
                //keyed by lead id then list id
                $listsByLeads = $listRepo->getLeadLists($listLeads, true);

                foreach ($listLeads as $l) {
                    //does this lead belong to another list still in the campaign?
                    $leadsLists = array_keys($listsByLeads[$l]);

                    $inBothLists = array_intersect($leadsLists, $campaignListIds);
                    if (!empty($inBothLists)) {
                        continue;
                    }

                    $this->removeLead($campaign, $l, false, false);
                }
            }
        }

        $campaign->leadsRebuilt = true;
        //prevent another update entry in audit log
        $campaign->resetChanges();
        $this->saveEntity($campaign, false);
    }

    /**
     * Get leads for a campaign.  If $event is passed in, only leads who have not triggered the event are returned
     *
     * @param Campaign $campaign
     * @param array    $event
     *
     * @return mixed
     */
    public function getCampaignLeads($campaign, $event = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;
        $eventId    = (is_array($event) && isset($event['id'])) ? $event['id'] : $event;
        $leads = $this->em->getRepository('MauticCampaignBundle:Lead')->getLeads($campaignId, $eventId);

        return $leads;
    }

    /**
     * @param Campaign $campaign
     * @param          $lead
     */
    public function removeScheduledEvents($campaign, $lead)
    {
        $this->em->getRepository('MauticCampaignBundle:LeadEventLog')->removeScheduledEvents($campaign->getId(), $lead->getId());
    }
}
