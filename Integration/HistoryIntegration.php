<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class HistoryIntegration extends AbstractIntegration
{
    public const PLUGIN_NAME = 'Action';
    public const DISPLAY_NAME = 'Delete Contact History by Leuchtfeuer';
    public const AUTHENTICATION_TYPE = 'none';

    public function getName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getAuthenticationType(): string
    {
        return self::AUTHENTICATION_TYPE;
    }
}