<?php
namespace Craft;

/**
 * Class DigitalProducts_ProductsController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */

class DigitalProducts_ProductTypesController extends BaseController
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseController::init()
     */
    public function init()
    {
        if (!craft()->userSession->checkPermission('digitalProducts-manageProductTypes')) {
            throw new HttpException(403, Craft::t('You don\'t have permissions to do that.'));
        }

        parent::init();
    }

    /**
     * Create or edit a Product Type
     *
     * @param array $variables
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['productType'])) {
            if (empty($variables['productTypeId'])) {
                $productType = new DigitalProducts_ProductTypeModel();
            } else {
                $productType = craft()->digitalProducts_productTypes->getProductTypeById($variables['productTypeId']);
            }
            if (!$productType) {
                $productType = new DigitalProducts_ProductTypeModel();;
            }
            $variables['productType'] = $productType;
        }
        
        $variables['title'] = empty($variables['productType']->id) ? Craft::t("Create a new Digital Product Type") : $variables['productType']->name;

        $this->renderTemplate('digitalproducts/producttypes/_edit', $variables);
    }

    /**
     * Save a Product Type
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $productType = new DigitalProducts_ProductTypeModel();

        $productType->id = craft()->request->getPost('productTypeId');
        $productType->name = craft()->request->getPost('name');
        $productType->handle = craft()->request->getPost('handle');
        $productType->hasUrls = craft()->request->getPost('hasUrls');
        $productType->skuFormat = craft()->request->getPost('skuFormat');
        $productType->template = craft()->request->getPost('template');

        $locales = [];

        foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
            $locales[$localeId] = new DigitalProducts_ProductTypeLocaleModel([
                'locale' => $localeId,
                'urlFormat' => craft()->request->getPost('urlFormat.'.$localeId)
            ]);
        }

        $productType->setLocales($locales);

        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = 'DigitalProduct_Product';
        $productType->asa('productFieldLayout')->setFieldLayout($fieldLayout);

        // Save it
        if (craft()->digitalProducts_productTypes->saveProductType($productType)) {
            craft()->userSession->setNotice(Craft::t('Product type saved.'));
            $this->redirectToPostedUrl($productType);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save product type.'));
        }

        // Send the productType back to the template
        craft()->urlManager->setRouteVariables([
            'productType' => $productType
        ]);
    }

    /**
     * Delete a Product Type.
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        try {
            craft()->digitalProducts_productTypes->deleteProductTypeById($id);
            $this->returnJson(['success' => true]);
        } catch (\Exception $e) {
            $this->returnErrorJson($e->getMessage());
        }
    }

}
