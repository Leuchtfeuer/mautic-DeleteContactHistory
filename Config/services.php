<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [];

    $services->load('MauticPlugin\\LeuchtfeuerDeleteContactHistoryBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->get(MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\LeuchtfeuerDeleteContactHistoryIntegration::class)
        ->tag('mautic.integration')
        ->tag('mautic.basic_integration');
    $services->get(MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\Support\ConfigSupport::class)
        ->tag('mautic.config_integration');

    $services->alias('mautic.integration.leuchtfeuerdeletecontacthistory', MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\LeuchtfeuerDeleteContactHistoryIntegration::class);
};
