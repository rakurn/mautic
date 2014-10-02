<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Doctrine\ORM\EntityRepository;

/**
 * NonceRepository
 */
class NonceRepository extends EntityRepository
{

    /**
     * Delete nonces that are older than $timestamp
     */
    public function removeOutdatedNonces($timestamp)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticApiBundle:oAuth1\Nonce', 'n')
            ->andWhere($qb->expr()->lte('n.timestamp', ':timestamp'))
            ->setParameter(':timestamp', $timestamp);

        $qb->getQuery()->execute();
    }
}