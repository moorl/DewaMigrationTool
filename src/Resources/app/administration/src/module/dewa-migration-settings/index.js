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
            name: 'dewa-migration-settings-migration',
            to: 'dewa.migration.settings.migration',
            group: 'dewa',
            icon: 'default-object-lab-flask',
            label: 'dewa-shop.navigation.migration'
        }
    ]
});
