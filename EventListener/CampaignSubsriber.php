<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Form\ActionSelectionType;
use MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Services\HistoryActions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubsriber implements EventSubscriberInterface
{
    public function __construct(private HistoryActions $historyActions)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD           => ['chooseAction', 0],
            // @phpstan-ignore-next-line
            LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION      => ['onClearHistoryEvent', 0],
        ];
    }

    public function chooseAction(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'lead.history',
            [
                'label'             => 'mautic.lead.lead.events.history',
                'description'       => 'mautic.lead.lead.events.history_descr',
                'formType'          => ActionSelectionType::class,
                // @phpstan-ignore-next-line
                'eventName'         => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            ]
        );
    }

    // @phpstan-ignore-next-line
    public function onClearHistoryEvent(CampaignExecutionEvent $event): void
    {
        if (!$event->checkContext('lead.history')) {
            return;
        }

        $config            = $event->getConfig()['clearHistory'];
        $lead              = $event->getLead();
        $lead_id           = $lead->getId();
        $somethingHappened = false;

        foreach ($config as $value) {
            if (ActionSelectionType::PAGE_HITS == $value) {
                $this->historyActions->clearPageHits($lead_id);
                $somethingHappened = true;
            } elseif (ActionSelectionType::EMAIL_OPEN_LINK_CLICKS == $value) {
                $this->historyActions->clearAllEmailLinkClicks($lead_id);
                $somethingHappened = true;
            } elseif (ActionSelectionType::FOCUS_ITEMS_STATS == $value) {
                $this->historyActions->clearFocusItemsStats($lead_id);
                $somethingHappened = true;
            } elseif (ActionSelectionType::ASSET_DOWNLOADS == $value) {
                $this->historyActions->clearAssetDownloads($lead_id);
                $somethingHappened = true;
            } elseif (ActionSelectionType::ALL == $value) {
                $this->historyActions->clearAll($lead_id);
                $somethingHappened = true;
            }
        }
        $event->setResult($somethingHappened);
    }
}
