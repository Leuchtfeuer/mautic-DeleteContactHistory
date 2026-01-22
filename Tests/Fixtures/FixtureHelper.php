<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat as EmailStat;
use Mautic\EmailBundle\Entity\StatDevice;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat as FocusStat;

final class FixtureHelper
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function enablePlugin(): void
    {
        $plugin = new Plugin();
        $plugin->setName('Delete Contact History by Leuchtfeuer');
        $plugin->setBundle('LeuchtfeuerDeleteContactHistoryBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setIsPublished(true);
        $integration->setName('LeuchtfeuerDeleteContactHistory');
        $this->em->persist($integration);
        $this->em->flush();
    }

    public function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    public function createIpAddress(string $ip = '127.0.0.1'): IpAddress
    {
        $ipAddress = new IpAddress($ip);
        $this->em->persist($ipAddress);
        $this->em->flush();

        return $ipAddress;
    }

    public function createPageHit(Lead $contact, ?IpAddress $ipAddress = null): Hit
    {
        if (null === $ipAddress) {
            $ipAddress = $this->createIpAddress();
        }

        $hit = new Hit();
        $hit->setLead($contact);
        $hit->setIpAddress($ipAddress);
        $hit->setDateHit(new \DateTime());
        $hit->setCode(200);
        $hit->setUrl('https://example.com/test-page');
        $hit->setTrackingId(bin2hex(random_bytes(16)));
        // email IS NULL - this is a regular page hit
        $this->em->persist($hit);
        $this->em->flush();

        return $hit;
    }

    public function createPageEventLog(Lead $contact): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setLead($contact);
        $eventLog->setBundle('page');
        $eventLog->setObject('hit');
        $eventLog->setAction('view');
        $eventLog->setObjectId(1);
        $eventLog->setDateAdded(new \DateTime());
        $this->em->persist($eventLog);
        $this->em->flush();

        return $eventLog;
    }

    public function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('template');
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    public function createEmailStat(Lead $contact, Email $email): EmailStat
    {
        $stat = new EmailStat();
        $stat->setLead($contact);
        $stat->setEmail($email);
        $stat->setEmailAddress($contact->getEmail());
        $stat->setDateSent(new \DateTime());
        $stat->setIsRead(true);
        $stat->setDateRead(new \DateTime());
        $stat->setLastOpened(new \DateTime());
        $stat->setOpenDetails(['detail1' => 'value1']);
        $stat->setTrackingHash(bin2hex(random_bytes(16)));
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    public function createStatDevice(EmailStat $stat, ?IpAddress $ipAddress = null): StatDevice
    {
        if (null === $ipAddress) {
            $ipAddress = $this->createIpAddress();
        }

        $statDevice = new StatDevice();
        $statDevice->setStat($stat);
        $statDevice->setIpAddress($ipAddress);
        $statDevice->setDateOpened(new \DateTime());
        $this->em->persist($statDevice);
        $this->em->flush();

        return $statDevice;
    }

    public function createEmailPageHit(Lead $contact, Email $email, ?IpAddress $ipAddress = null): Hit
    {
        if (null === $ipAddress) {
            $ipAddress = $this->createIpAddress();
        }

        $hit = new Hit();
        $hit->setLead($contact);
        $hit->setIpAddress($ipAddress);
        $hit->setDateHit(new \DateTime());
        $hit->setCode(200);
        $hit->setUrl('https://example.com/email-link');
        $hit->setTrackingId(bin2hex(random_bytes(16)));
        $hit->setEmail($email); // email IS NOT NULL - this is an email link click
        $this->em->persist($hit);
        $this->em->flush();

        return $hit;
    }

    public function createAsset(string $title): Asset
    {
        $asset = new Asset();
        $asset->setTitle($title);
        $asset->setAlias(strtolower(str_replace(' ', '-', $title)));
        $asset->setStorageLocation('local');
        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    public function createAssetDownload(Lead $contact, Asset $asset, ?IpAddress $ipAddress = null): Download
    {
        if (null === $ipAddress) {
            $ipAddress = $this->createIpAddress();
        }

        $download = new Download();
        $download->setLead($contact);
        $download->setAsset($asset);
        $download->setIpAddress($ipAddress);
        $download->setDateDownload(new \DateTime());
        $download->setCode(200);
        $download->setTrackingId(bin2hex(random_bytes(16))); // @phpstan-ignore-line
        $this->em->persist($download);
        $this->em->flush();

        return $download;
    }

    public function createFocus(string $name): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('notice');
        $focus->setStyle('bar');
        $this->em->persist($focus);
        $this->em->flush();

        return $focus;
    }

    public function createFocusStat(Lead $contact, Focus $focus): FocusStat
    {
        $stat = new FocusStat();
        $stat->setLead($contact);
        $stat->setFocus($focus);
        $stat->setType(FocusStat::TYPE_NOTIFICATION);
        $stat->setDateAdded(new \DateTime());
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }
}
