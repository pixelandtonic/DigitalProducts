<?php

namespace Craft;


/**
 * Digital Products Plugin for Craft Commerce.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProductsPlugin extends BasePlugin
{

    // Public Methods
    // =========================================================================

    /**
     * Initialize the plugin.
     */
    public function init()
    {
        if (craft()->request->isCpRequest()) {
            craft()->templates->hook('digitalProducts.prepCpTemplate', [
                $this,
                'prepCpTemplate'
            ]);
            $this->_includeCpResources();
        }

        $this->_registerEventHandlers();
    }

    /**
     * The plugin name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Digital Products';
    }

    /**
     * The plugin description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return 'Add digital products to Craft Commerce.';
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getDeveloper()
    {
        return 'Pixel & Tonic';
    }

    /**
     * Digital Products developer URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://pixelandtonic.com.com';
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return 'https://github.com/pixelandtonic/DigitalProducts';
    }

    /**
     * Digital Products documentation URL.
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        return $this->getPluginUrl().'/blob/master/README.md';;
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/pixelandtonic/DigitalProducts/master/releases.json';
    }

    /**
     * Digital Products has a control panel section.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * Make sure requirements are met before installation.
     *
     * @return bool
     * @throws Exception
     */
    public function onBeforeInstall()
    {
    }

    /**
     * Digital Products version.
     * @return string
     */
    public function getVersion()
    {
        return '1.0.4';
    }

    /**
     * Digital Products schema version.
     *
     * @return string|null
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * Control Panel routes.
     *
     * @return mixed
     */
    public function registerCpRoutes()
    {
        return [
            'digitalproducts/producttypes/new' => ['action' => 'digitalProducts/productTypes/edit'],
            'digitalproducts/producttypes/(?P<productTypeId>\d+)' => ['action' => 'digitalProducts/productTypes/edit'],
            'digitalproducts/products/(?P<productTypeHandle>{handle})' => ['action' => 'digitalProducts/products/index'],
            'digitalproducts/products/(?P<productTypeHandle>{handle})/new' => ['action' => 'digitalProducts/products/edit'],
            'digitalproducts/products/(?P<productTypeHandle>{handle})/new/(?P<localeId>\w+)' => ['action' => 'digitalProducts/products/edit'],
            'digitalproducts/products/(?P<productTypeHandle>{handle})/(?P<productId>\d+)' => ['action' => 'digitalProducts/products/edit'],
            'digitalproducts/products/(?P<productTypeHandle>{handle})/(?P<productId>\d+)/(?P<localeId>\w+)' => ['action' => 'digitalProducts/products/edit'],

            'digitalproducts/licenses/new' => ['action' => 'digitalProducts/licenses/edit'],
            'digitalproducts/licenses/(?P<licenseId>\d+)' => ['action' => 'digitalProducts/licenses/edit'],
        ];
    }

    /**
     * Prepares a CP template.
     *
     * @param array &$context The current template context
     */
    public function prepCpTemplate(&$context)
    {
        $context['subnav'] = [];

        if (craft()->userSession->checkPermission('digitalProducts-manageProducts')) {
            $context['subnav']['products'] = [
                'label' => Craft::t('Products'),
                'url' => 'digitalproducts/products'
            ];
        }

        if (craft()->userSession->checkPermission('digitalProducts-manageProductTypes')) {
            $context['subnav']['productTypes'] = [
                'label' => Craft::t('Product Types'),
                'url' => 'digitalproducts/producttypes'
            ];
        }

        if (craft()->userSession->checkPermission('digitalProducts-manageLicenses')) {
            $context['subnav']['licenses'] = [
                'label' => Craft::t('Licenses'),
                'url' => 'digitalproducts/licenses'
            ];
        }
    }

    /**
     * @return array
     */
    public function registerUserPermissions()
    {
        $productTypes = craft()->digitalProducts_productTypes->getAllProductTypes('id');

        $productTypePermissions = [];
        foreach ($productTypes as $id => $productType) {
            $suffix = ':'.$id;
            $productTypePermissions["digitalProducts-manageProductType".$suffix] = [
                'label' => Craft::t('“{type}” products', ['type' => $productType->name])
            ];
        }

        return [
            'digitalProducts-manageProductTypes' => ['label' => Craft::t('Manage product types')],
            'digitalProducts-manageProducts' => [
                'label' => Craft::t('Manage products'),
                'nested' => $productTypePermissions
            ],
            'digitalProducts-manageLicenses' => ['label' => Craft::t('Manage licenses')]
        ];
    }

    /**
     * Get Settings URL
     */
    public function getSettingsUrl()
    {
        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Includes front end resources for Control Panel requests.
     */
    private function _includeCpResources()
    {
        craft()->templates->includeJsResource('digitalproducts/js/DigitalProducts.js');
        craft()->templates->includeJsResource('digitalproducts/js/DigitalProductsLicenseIndex.js');
        craft()->templates->includeJsResource('digitalproducts/js/DigitalProductsProductIndex.js');
    }

    /**
     * Register the event handlers.
     */
    private function _registerEventHandlers()
    {
        // Craft User related event handlers
        craft()->on('users.onActivateUser', [
            '\Craft\DigitalProducts_LicensesService',
            'handleUserActivation'
        ]);
        craft()->on('users.onDeleteUser', [
            '\Craft\DigitalProducts_LicensesService',
            'handleUserDeletion'
        ]);

        // Craft Commerce related event handlers
        craft()->on('commerce_orders.onOrderComplete', [
            '\Craft\DigitalProducts_LicensesService',
            'handleCompletedOrder'
        ]);
        craft()->on('commerce_payments.onBeforeGatewayRequestSend', [
            '\Craft\DigitalProducts_LicensesService',
            'maybePreventPayment'
        ]);
    }
}
