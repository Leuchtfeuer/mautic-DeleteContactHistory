<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class LeuchtfeuerDeleteContactHistoryIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const INTEGRATION_NAME = 'LeuchtfeuerDeleteContactHistory';
    public const DISPLAY_NAME     = 'Delete Contact History by Leuchtfeuer';

    public function getName(): string
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerDeleteContactHistoryBundle/Assets/img/leuchtfeuerdeletecontacthistory.png';
    }

    /**
     * Override the trait method to fix PHPStan error.
     */
    public function hasIntegrationConfiguration(): bool
    {
        return null !== $this->integration;
    }
}
