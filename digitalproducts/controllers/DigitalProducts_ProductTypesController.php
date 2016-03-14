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
    public function init()
    {
        if (!craft()->userSession->checkPermission('commerce-manageProducts')) {
            throw new HttpException(403, Craft::t('You don\'t have permissions to do that.'));
        }
        parent::init();
    }

    /**
     * Create/Edit Product Type
     *
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['productTypeId'])) {
            $productType = new DigitalProducts_ProductTypeModel();
        } else {
            $productType = craft()->digitalProducts_productTypes->getProductTypeById($variables['productTypeId']);
            if (!$productType)
            {
                $productType = new DigitalProducts_ProductTypeModel();;
            }
        }

        $variables['title'] = empty($productType->id) ? Craft::t("Create a new Digital Product Type") : $productType->name;
        $variables['productType'] = $productType;

        $this->renderTemplate('digitalproducts/producttypes/_edit', $variables);
    }

    /**
     * @throws HttpException
     */
    public function actionSave()
    {
        if (!craft()->userSession->getUser()->can('commerce-manageProducts')) {
            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
        }

        $this->requirePostRequest();

        $productType = new DigitalProducts_ProductTypeModel();

        $productType->id = craft()->request->getPost('productTypeId');
        $productType->name = craft()->request->getPost('name');
        $productType->handle = craft()->request->getPost('handle');
        $productType->licenseKeyAlphabet = craft()->request->getPost('licenseKeyAlphabet');
        $productType->licenseKeyLength = craft()->request->getPost('licenseKeyLength');
        $productType->hasUrls = craft()->request->getPost('hasUrls');
        $productType->skuFormat = craft()->request->getPost('skuFormat');
        $productType->template = craft()->request->getPost('template');

        $locales = [];

        foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
            $locales[$localeId] = new DigitalProducts_ProductTypeLocaleModel([
                'locale' => $localeId,
                'urlFormat' => craft()->request->getPost('urlFormat.' . $localeId)
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
     * @throws HttpException
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
