<?php

declare(strict_types=1);

return [
    'name'          => 'Delete Contact History by Leuchtfeuer',
    'description'   => 'Campaign action to selectively remove history data from a contact (e.g. for GDPR)',
    'version'       => '1.0',
    'author'        => 'Leuchtfeuer Digital Marketing GmbH',

    'routes'        => [],
    'menu'          => [],

    'services'      => [
        'integrations'  => [
            'mautic.integration.leuchtfeuerdeletecontacthistory' => [
                'class'     => \MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Integration\LeuchtfeuerDeleteContactHistoryIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
