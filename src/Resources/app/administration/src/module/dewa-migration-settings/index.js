const {Module} = Shopware;

import './page/migration';

Module.register('dewa-migration-settings', {
    type: 'plugin',
    name: 'dewa-migration-settings',
    title: 'dewa-shop.navigation.migration',

    routes: {
        migration: {
            component: 'dewa-migration-settings-migration',
            path: 'migration',
            meta: {
                parentPath: 'sw.settings.index'
            },
        },
    },

    settingsItem: [
        {
            privilege: 'system.system_config',
            name: 'dewa-migration-settings-migration',
            to: 'dewa.migration.settings.migration',
            group: 'plugins',
            id: 'appflix-setting-migration',
            icon: 'regular-activity',
            label: 'dewa-shop.navigation.migration'
        }
    ]
});
