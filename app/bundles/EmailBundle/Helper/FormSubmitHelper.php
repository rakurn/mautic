<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Action;
use Mautic\LeadBundle\Entity\Lead;

class FormSubmitHelper
{
	/**
     * @param       $action
     *
     * @return array
     */
    public static function sendEmail(Action $action, MauticFactory $factory, $feedback)
    {
        $properties = $action->getProperties();
        $emailId    = (isset($properties['useremail'])) ? (int) $properties['useremail']['email'] : (int) $properties['email'];
        $form       = $action->getForm();

        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel  = $factory->getModel('email');
        $email  	 = $emailModel->getEntity($emailId);

		/** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
		$leadModel = $factory->getModel('lead');

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {
		    // Deal with Lead email
		    if (!empty($feedback['lead.create']['lead'])) {
		    	//the lead was just created via the lead.create action
		    	$currentLead = $feedback['lead.create']['lead'];
		    } else {
		        $currentLead = $leadModel->getCurrentLead();
		    }

			if ($currentLead instanceof Lead) {
				//flatten the lead
				$lead = $currentLead;
				$currentLead = array(
					'id' => $lead->getId()
				);
				$leadFields = $leadModel->flattenFields($lead->getFields());

				$currentLead = array_merge($currentLead, $leadFields);
			}

			if (isset($properties['user_id']) && $properties['user_id']) {
				// User email
				$emailModel->sendEmailToUser($email, $properties['user_id'], $currentLead);
			} elseif (isset($currentLead)) {
				// Lead email
                if (!isset($leadFields) && $currentLead instanceof Lead) {
                    $leadFields = $currentLead->getFields();
                }
		    	
		    	if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {

		    		$emailModel->sendEmail($email, array($currentLead['id'] => $currentLead), array('form', $form->getId()));
		    	}
		    }
		}
    }
}