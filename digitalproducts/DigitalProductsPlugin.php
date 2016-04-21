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
    public $handle = 'digitalProducts';

    /**
     * Initialize the plugin.
     */
    public function init()
    {
        if (craft()->request->isCpRequest()) {
            craft()->templates->hook('digitalProducts.prepCpTemplate', array($this, 'prepCpTemplate'));
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
     * Commerce Developer URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://craftcommerce.com';
    }

    /**
     * Commerce Documentation URL.
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        return;
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
     * Commerce Version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '0.1';
    }

    /**
     * Commerce Schema Version.
     *
     * @return string|null
     */
    public function getSchemaVersion()
    {
        return '0.0.1';
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
        $context['subnav'] = array();
        $productTypes = craft()->digitalProducts_productTypes->getProductTypes();

        if (craft()->userSession->checkPermission('digitalProducts-manageProductTypes')) {
            $context['subnav']['productTypes'] = [
                'label' => Craft::t('Product types'),
                'url' => 'digitalproducts/producttypes'
            ];
        }

        if (craft()->userSession->checkPermission('digitalProducts-manageProducts')) {
            if (!empty($productTypes)) {
                $context['subnav']['products'] = [
                    'label' => Craft::t('Products'),
                    'url' => 'digitalproducts/products'
                ];
            }
        }

        if (!empty($productTypes)) {
            if (craft()->userSession->checkPermission('digitalProducts-manageLicenses')) {
                $context['subnav']['licenses'] = [
                    'label' => Craft::t('Licenses'),
                    'url' => 'digitalproducts/licenses'
                ];
            }
        }
    }

    /**
     * @return array
     */
    public function registerUserPermissions()
    {
        $productTypes = craft()->digitalProducts_productTypes->getAllProductTypes('id');

        $productTypePermissions = array();
        foreach ($productTypes as $id => $productType) {
            $suffix = ':' . $id;
            $productTypePermissions["digitalProducts-manageProductType" . $suffix] = array(
                'label' => Craft::t('“{type}” products', ['type' => $productType->name])
            );
        }

        return array(
            'digitalProducts-manageProductTypes' => array('label' => Craft::t('Manage product types')),
            'digitalProducts-manageProducts' => array('label' => Craft::t('Manage products'), 'nested' => $productTypePermissions),
            'digitalProducts-manageLicenses' => array('label' => Craft::t('Manage licenses'))
        );
    }

    /**
     * Get Settings URL
     */
    public function getSettingsUrl()
    {
        return false;
    }

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
        craft()->on('commerce_orders.onOrderComplete', ['\Craft\DigitalProducts_LicensesService', 'handleCompletedOrder']);
        craft()->on('users.onActivateUser', ['\Craft\DigitalProducts_LicensesService', 'handleUserActivation']);
        craft()->on('commerce_payments.onBeforeGatewayRequestSend', ['\Craft\DigitalProducts_LicensesService', 'maybePreventPayment']);
    }
}
