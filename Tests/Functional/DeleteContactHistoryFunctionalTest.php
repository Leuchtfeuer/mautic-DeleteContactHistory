<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Tests\Functional;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat as EmailStat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\PageBundle\Entity\Hit;
use MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Form\ActionSelectionType;
use MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Tests\Fixtures\FixtureHelper;
use MauticPlugin\MauticFocusBundle\Entity\Stat as FocusStat;
use PHPUnit\Framework\Assert;

class DeleteContactHistoryFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private FixtureHelper $fixtureHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureHelper = new FixtureHelper($this->em);
        $this->fixtureHelper->enablePlugin();
    }

    public function testClearPageHitsOnly(): void
    {
        // Create target contact with data
        $targetContact = $this->fixtureHelper->createContact('target-pagehits@test.com');
        $this->fixtureHelper->createPageHit($targetContact);
        $this->fixtureHelper->createPageHit($targetContact);
        $this->fixtureHelper->createPageEventLog($targetContact);

        // Create another contact with data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-pagehits@test.com');
        $this->fixtureHelper->createPageHit($otherContact);
        $this->fixtureHelper->createPageEventLog($otherContact);

        // Create and execute campaign
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [ActionSelectionType::PAGE_HITS]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's page hits are deleted
        $targetHits = $this->em->getRepository(Hit::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetHits, 'Target contact should have no page hits');

        // Verify target contact's page event logs are deleted
        $targetEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'lead'   => $targetContact->getId(),
            'bundle' => 'page',
        ]);
        Assert::assertCount(0, $targetEventLogs, 'Target contact should have no page event logs');

        // Verify other contact's data remains
        $otherHits = $this->em->getRepository(Hit::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherHits, 'Other contact should still have 1 page hit');

        $otherEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'lead'   => $otherContact->getId(),
            'bundle' => 'page',
        ]);
        Assert::assertCount(1, $otherEventLogs, 'Other contact should still have 1 page event log');
    }

    public function testClearEmailOpensAndLinkClicksOnly(): void
    {
        // Create target contact with data
        $targetContact = $this->fixtureHelper->createContact('target-email@test.com');
        $email         = $this->fixtureHelper->createEmail('Test Email');
        $stat          = $this->fixtureHelper->createEmailStat($targetContact, $email);
        $this->fixtureHelper->createStatDevice($stat);
        $this->fixtureHelper->createEmailPageHit($targetContact, $email);

        // Create another contact with data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-email@test.com');
        $otherStat    = $this->fixtureHelper->createEmailStat($otherContact, $email);
        $this->fixtureHelper->createStatDevice($otherStat);
        $this->fixtureHelper->createEmailPageHit($otherContact, $email);

        // Create and execute campaign
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [ActionSelectionType::EMAIL_OPEN_LINK_CLICKS]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's email stats are updated (not deleted)
        $targetStats = $this->em->getRepository(EmailStat::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(1, $targetStats, 'Target contact should still have email stat');
        $targetStat = $targetStats[0];
        Assert::assertFalse($targetStat->isRead(), 'Target contact email stat should have isRead=false');
        Assert::assertNull($targetStat->getLastOpened(), 'Target contact email stat should have lastOpened=null');
        Assert::assertNull($targetStat->getOpenDetails(), 'Target contact email stat should have openDetails=null');

        // Verify target contact's stat devices are deleted
        $targetStatDevices = $this->em->getRepository(StatDevice::class)->findBy(['stat' => $targetStat->getId()]);
        Assert::assertCount(0, $targetStatDevices, 'Target contact should have no stat devices');

        // Verify target contact's email page hits are deleted
        $targetEmailHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NOT NULL')
            ->setParameter('leadId', $targetContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(0, $targetEmailHits, 'Target contact should have no email page hits');

        // Verify other contact's data remains
        $otherStats = $this->em->getRepository(EmailStat::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherStats, 'Other contact should still have 1 email stat');
        $otherStatEntity = $otherStats[0];
        Assert::assertTrue($otherStatEntity->isRead(), 'Other contact email stat should still have isRead=true');
        Assert::assertNotNull($otherStatEntity->getLastOpened(), 'Other contact email stat should still have lastOpened');

        $otherStatDevices = $this->em->getRepository(StatDevice::class)->findBy(['stat' => $otherStatEntity->getId()]);
        Assert::assertCount(1, $otherStatDevices, 'Other contact should still have 1 stat device');

        $otherEmailHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NOT NULL')
            ->setParameter('leadId', $otherContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(1, $otherEmailHits, 'Other contact should still have 1 email page hit');
    }

    public function testClearFocusItemStatsOnly(): void
    {
        // Create target contact with data
        $targetContact = $this->fixtureHelper->createContact('target-focus@test.com');
        $focus         = $this->fixtureHelper->createFocus('Test Focus');
        $this->fixtureHelper->createFocusStat($targetContact, $focus);

        // Create another contact with data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-focus@test.com');
        $this->fixtureHelper->createFocusStat($otherContact, $focus);

        // Create and execute campaign
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [ActionSelectionType::FOCUS_ITEMS_STATS]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's focus stats are deleted
        $targetFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetFocusStats, 'Target contact should have no focus stats');

        // Verify other contact's data remains
        $otherFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherFocusStats, 'Other contact should still have 1 focus stat');
    }

    public function testClearAssetDownloadsOnly(): void
    {
        // Create target contact with data
        $targetContact = $this->fixtureHelper->createContact('target-asset@test.com');
        $asset         = $this->fixtureHelper->createAsset('Test Asset');
        $this->fixtureHelper->createAssetDownload($targetContact, $asset);

        // Create another contact with data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-asset@test.com');
        $this->fixtureHelper->createAssetDownload($otherContact, $asset);

        // Create and execute campaign
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [ActionSelectionType::ASSET_DOWNLOADS]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's asset downloads are deleted
        $targetDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetDownloads, 'Target contact should have no asset downloads');

        // Verify other contact's data remains
        $otherDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherDownloads, 'Other contact should still have 1 asset download');
    }

    public function testClearAll(): void
    {
        // Create target contact with all types of data
        $targetContact = $this->fixtureHelper->createContact('target-all@test.com');
        $email         = $this->fixtureHelper->createEmail('Test Email All');
        $asset         = $this->fixtureHelper->createAsset('Test Asset All');
        $focus         = $this->fixtureHelper->createFocus('Test Focus All');

        $this->fixtureHelper->createPageHit($targetContact);
        $this->fixtureHelper->createPageEventLog($targetContact);
        $stat = $this->fixtureHelper->createEmailStat($targetContact, $email);
        $this->fixtureHelper->createStatDevice($stat);
        $this->fixtureHelper->createEmailPageHit($targetContact, $email);
        $this->fixtureHelper->createAssetDownload($targetContact, $asset);
        $this->fixtureHelper->createFocusStat($targetContact, $focus);

        // Create another contact with all types of data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-all@test.com');
        $this->fixtureHelper->createPageHit($otherContact);
        $this->fixtureHelper->createPageEventLog($otherContact);
        $otherStat = $this->fixtureHelper->createEmailStat($otherContact, $email);
        $this->fixtureHelper->createStatDevice($otherStat);
        $this->fixtureHelper->createEmailPageHit($otherContact, $email);
        $this->fixtureHelper->createAssetDownload($otherContact, $asset);
        $this->fixtureHelper->createFocusStat($otherContact, $focus);

        // Create and execute campaign
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [ActionSelectionType::ALL]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's page hits are deleted (regular page hits)
        $targetPageHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NULL')
            ->setParameter('leadId', $targetContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(0, $targetPageHits, 'Target contact should have no page hits');

        // Verify target contact's page event logs are deleted
        $targetEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'lead'   => $targetContact->getId(),
            'bundle' => 'page',
        ]);
        Assert::assertCount(0, $targetEventLogs, 'Target contact should have no page event logs');

        // Verify target contact's email stats are updated
        $targetStats = $this->em->getRepository(EmailStat::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(1, $targetStats, 'Target contact should still have email stat');
        Assert::assertFalse($targetStats[0]->isRead(), 'Target contact email stat should have isRead=false');

        // Verify target contact's stat devices are deleted
        $targetStatDevices = $this->em->getRepository(StatDevice::class)->findBy(['stat' => $targetStats[0]->getId()]);
        Assert::assertCount(0, $targetStatDevices, 'Target contact should have no stat devices');

        // Verify target contact's email page hits are deleted
        $targetEmailHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NOT NULL')
            ->setParameter('leadId', $targetContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(0, $targetEmailHits, 'Target contact should have no email page hits');

        // Verify target contact's asset downloads are deleted
        $targetDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetDownloads, 'Target contact should have no asset downloads');

        // Verify target contact's focus stats are deleted
        $targetFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetFocusStats, 'Target contact should have no focus stats');

        // Verify other contact's data remains
        $otherPageHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NULL')
            ->setParameter('leadId', $otherContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(1, $otherPageHits, 'Other contact should still have 1 page hit');

        $otherStats = $this->em->getRepository(EmailStat::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherStats, 'Other contact should still have 1 email stat');
        Assert::assertTrue($otherStats[0]->isRead(), 'Other contact email stat should still have isRead=true');

        $otherDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherDownloads, 'Other contact should still have 1 asset download');

        $otherFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherFocusStats, 'Other contact should still have 1 focus stat');
    }

    public function testMultipleOptionsSelected(): void
    {
        // Create target contact with page hits and asset downloads
        $targetContact = $this->fixtureHelper->createContact('target-multiple@test.com');
        $asset         = $this->fixtureHelper->createAsset('Test Asset Multiple');

        $this->fixtureHelper->createPageHit($targetContact);
        $this->fixtureHelper->createPageEventLog($targetContact);
        $this->fixtureHelper->createAssetDownload($targetContact, $asset);

        // Create a focus stat that should remain (not selected)
        $focus = $this->fixtureHelper->createFocus('Test Focus Multiple');
        $this->fixtureHelper->createFocusStat($targetContact, $focus);

        // Create another contact with data (should remain unchanged)
        $otherContact = $this->fixtureHelper->createContact('other-multiple@test.com');
        $this->fixtureHelper->createPageHit($otherContact);
        $this->fixtureHelper->createPageEventLog($otherContact);
        $this->fixtureHelper->createAssetDownload($otherContact, $asset);
        $this->fixtureHelper->createFocusStat($otherContact, $focus);

        // Create and execute campaign with PAGE_HITS and ASSET_DOWNLOADS (not FOCUS)
        $campaign = $this->createCampaignWithHistoryAction($targetContact, [
            ActionSelectionType::PAGE_HITS,
            ActionSelectionType::ASSET_DOWNLOADS,
        ]);
        $this->executeCampaign($campaign->getId());

        // Clear entity manager to get fresh data
        $this->em->clear();

        // Verify target contact's page hits are deleted
        $targetPageHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NULL')
            ->setParameter('leadId', $targetContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(0, $targetPageHits, 'Target contact should have no page hits');

        // Verify target contact's asset downloads are deleted
        $targetDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(0, $targetDownloads, 'Target contact should have no asset downloads');

        // Verify target contact's focus stats remain (not selected)
        $targetFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $targetContact->getId()]);
        Assert::assertCount(1, $targetFocusStats, 'Target contact should still have 1 focus stat (not selected for deletion)');

        // Verify other contact's data remains
        $otherPageHits = $this->em->createQueryBuilder()
            ->select('h')
            ->from(Hit::class, 'h')
            ->where('h.lead = :leadId')
            ->andWhere('h.email IS NULL')
            ->setParameter('leadId', $otherContact->getId())
            ->getQuery()
            ->getResult();
        Assert::assertCount(1, $otherPageHits, 'Other contact should still have 1 page hit');

        $otherDownloads = $this->em->getRepository(Download::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherDownloads, 'Other contact should still have 1 asset download');

        $otherFocusStats = $this->em->getRepository(FocusStat::class)->findBy(['lead' => $otherContact->getId()]);
        Assert::assertCount(1, $otherFocusStats, 'Other contact should still have 1 focus stat');
    }

    /**
     * Creates a campaign with lead.history action event and adds contact directly to the campaign.
     *
     * @param array<string> $options
     */
    private function createCampaignWithHistoryAction(Lead $contact, array $options): Campaign
    {
        // Create campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign '.uniqid());
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        // Add contact directly to campaign
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $campaign->addLead($contact->getId(), $campaignLead);
        $this->em->flush();

        // Create lead.history event
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Delete Contact History');
        $event->setType('lead.history');
        $event->setEventType('action');
        $event->setTriggerMode('immediate');
        $event->setProperties([
            'clearHistory' => $options,
        ]);

        // Add event to campaign's events collection
        $campaign->addEvent(1, $event);
        $this->em->persist($event);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function executeCampaign(int $campaignId): void
    {
        // Clear entity manager so doctrine will re-fetch the campaign with events
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaignId]);
    }
}
