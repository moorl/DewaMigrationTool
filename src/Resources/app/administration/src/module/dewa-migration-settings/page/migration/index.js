const {Component, Mixin} = Shopware;

import template from './index.html.twig';

Component.register('dewa-migration-settings-migration', {
    template,

    inject: [
        'repositoryFactory',
        'dewaShopApiService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            item: {
                salesChannelId: null,
                restaurantId: 'ORN50N11'
            },
            options: null,
            isLoading: false,
            processSuccess: false
        };
    },

    created() {},

    methods: {
        install() {
            this.isLoading = true;

            this.dewaShopApiService.post(`/dewa/settings/migration/install`, this.item).then(response => {
                this.createNotificationSuccess({
                    message: this.$tc('dewa-shop.notification.demoDataInstalled')
                });

                this.isLoading = false;
            }).catch((exception) => {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: exception,
                });

                this.isLoading = false;
            });
        },

        remove() {
            this.isLoading = true;

            this.dewaShopApiService.post(`/dewa/settings/migration/remove`, this.item).then(response => {
                this.createNotificationSuccess({
                    message: this.$tc('dewa-shop.notification.demoDataRemoved')
                });

                this.isLoading = false;
            }).catch((exception) => {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: exception,
                });

                this.isLoading = false;
            });
        }
    }
});
