<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\LeuchtfeuerDeleteContactHistoryIntegration;

class ConfigSupport extends LeuchtfeuerDeleteContactHistoryIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
