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
            LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION      => ['onClearHistoryEvent', 0],
        ];
    }

    public function chooseAction(CampaignBuilderEvent $event)
    {
        $event->addAction(
            'lead.history',
            [
                'label'             => 'mautic.lead.lead.events.history',
                'description'       => 'mautic.lead.lead.events.history_descr',
                'formType'          => ActionSelectionType::class,
                'eventName'         => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            ]
        );
    }

    public function onClearHistoryEvent(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.history')) {
            return;
        }

        $config = $event->getConfig()['clearHistory'];
        $lead   = $event->getLead();
        $lead_id = $lead->getId();
        $somethingHappened = false;


        foreach ($config as $value)
        {
            if ($value == ActionSelectionType::PAGE_HITS) {
                $this->historyActions->clearPageHits($lead_id);
                $somethingHappened = true;
            }
            elseif ($value == ActionSelectionType::EMAIL_OPEN_LINK_CLICKS) {
                $this->historyActions->clearAllEmailLinkClicks($lead_id);
                $somethingHappened = true;
            }
            elseif ($value == ActionSelectionType::FOCUS_ITEMS_STATS) {
                $this->historyActions->clearFocusItemsStats($lead_id);
                $somethingHappened = true;
            }
            elseif ($value == ActionSelectionType::ASSET_DOWNLOADS) {
                $this->historyActions->clearAssetDownloads($lead_id);
                $somethingHappened = true;
            }
            elseif ($value == ActionSelectionType::ALL) {
                $this->historyActions->clearAll($lead_id);
                $somethingHappened = true;
            }
        }
        return $event->setResult($somethingHappened);
    }
}
