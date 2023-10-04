<?php

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Download;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\PageBundle\Entity\Hit;
use MauticPlugin\MauticFocusBundle\Entity\Stat as FocusStat;

class HistoryActions
{
    public function __construct(private EntityManagerInterface $_em)
    {
    }

    public function clearHits(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->select('ph')
            ->from(Hit::class, 'ph')
            ->delete()
            ->where('ph.lead = :leadId')
            ->andWhere('ph.email IS NULL')
            ->setParameter('leadId', $lead_id);

        $query->getQuery()->execute();
    }

    public function clearLeadEventLog(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->from(LeadEventLog::class, 'lel')
            ->select('lel')
            ->delete()
            ->where('lel.lead = :leadId')
            ->andWhere('lel.bundle = :bundle')
            ->setParameter('leadId', $lead_id)
            ->setParameter('bundle', 'page');

        $query->getQuery()->execute();
    }

    public function clearPageHits(int $lead_id): void
    {
        $this->clearHits($lead_id);
        $this->clearLeadEventLog($lead_id);
    }

    public function clearLastOpened(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->from(Stat::class, 'stat')
            ->update()
            ->set('stat.lastOpened', ':nullValue')
            ->set('stat.openDetails', ':nullValue')
            ->where('stat.lead = :lead_id')
            ->setParameter('nullValue', null)
            ->setParameter('lead_id', $lead_id);

        $query->getQuery()->execute();
    }

    public function removeEmailStatsDevice(int $lead_id): void
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $query = $queryBuilder
            ->from(StatDevice::class, 'ed')
            ->delete()
            ->where(
                $queryBuilder->expr()->in(
                    'ed.stat',
                    $this->_em->createQueryBuilder()
                        ->from(Stat::class, 'stat')
                        ->select('stat.id')
                        ->where('stat.lead = :lead_id')->getDQL()
                )
            )
            ->setParameter('lead_id', $lead_id);

        $query->getQuery()->execute();
    }

    public function removePageHits(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->from(Hit::class, 'ph')
            ->delete()
            ->where('ph.email IS NOT NULL')
            ->andWhere('ph.lead = :lead_id')
            ->setParameter('lead_id', $lead_id);

        $query->getQuery()->execute();
    }

    public function clearAllEmailLinkClicks(int $lead_id): void
    {
        $this->clearLastOpened($lead_id);
        $this->removeEmailStatsDevice($lead_id);
        $this->removePageHits($lead_id);
    }

    public function clearFocusItemsStats(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->from(FocusStat::class, 's')
            ->delete()
            ->where('s.lead = :lead_id')
            ->setParameter('lead_id', $lead_id);

        $query->getQuery()->execute();
    }

    public function clearAssetDownloads(int $lead_id): void
    {
        $query = $this->_em
            ->createQueryBuilder()
            ->from(Download::class, 'd')
            ->delete()
            ->where('d.lead = :lead_id')
            ->setParameter('lead_id', $lead_id);

        $query->getQuery()->execute();
    }

    public function clearAll(int $lead_id): void
    {
        $this->clearAssetDownloads($lead_id);
        $this->clearFocusItemsStats($lead_id);
        $this->clearAllEmailLinkClicks($lead_id);
        $this->clearPageHits($lead_id);
    }
}
