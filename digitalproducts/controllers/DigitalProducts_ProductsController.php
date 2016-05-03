<?php
namespace Craft;

/**
 * Class DigitalProducts_ProductsController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductsController extends BaseController
{

    /**
     * @var bool
     */
    protected $allowAnonymous = ['actionViewSharedProduct'];

    // Public Methods
    // =========================================================================

    /**
     * Index of digital products
     */
    public function actionIndex(array $variables = [])
    {
        $this->renderTemplate('digitalproducts/products/index', $variables);
    }

    /**
     * Prepare screen to edit a product.
     *
     * @param array $variables
     *
     * @throws HttpException in case of lacking permissions or missing/corrupt data
     */
    public function actionEdit(array $variables = [])
    {
        // Make sure a correct product type handle was passed so we can check permissions
        if (!empty($variables['productTypeHandle'])) {
            $variables['productType'] = craft()->digitalProducts_productTypes->getProductTypeByHandle($variables['productTypeHandle']);
        }

        if (empty($variables['productType'])) {
            throw new HttpException(404);
        }

        $this->_enforceProductPermissionsForProductType($variables['productType']->id);

        $this->_prepareVariableArray($variables);
        $this->_maybeEnableLivePreview($variables);

        $this->renderTemplate('digitalproducts/products/_edit', $variables);
    }

    /**
     * Deletes a product.
     *
     * @throws HttpException if no product found
     */
    public function actionDeleteProduct()
    {
        $this->requirePostRequest();

        $productId = craft()->request->getRequiredPost('productId');
        $product = craft()->digitalProducts_products->getProductById($productId);

        if (!$product) {
            throw new HttpException(404);
        }

        $this->_enforceProductPermissionsForProductType($product->typeId);

        if (craft()->digitalProducts_products->deleteProduct($product)) {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => true]);
            } else {
                craft()->userSession->setNotice(Craft::t('Product deleted.'));
                $this->redirectToPostedUrl($product);
            }
        } else {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => false]);
            } else {
                craft()->userSession->setError(Craft::t('Couldn’t delete product.'));

                craft()->urlManager->setRouteVariables([
                    'product' => $product
                ]);
            }
        }
    }

    /**
     * Save a new or existing product.
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $product = $this->_buildProductFromPost();

        $this->_enforceProductPermissionsForProductType($product->typeId);

        $existingProduct = (bool)$product->id;

        if (craft()->digitalProducts_products->saveProduct($product)) {
            craft()->userSession->setNotice(Craft::t('Product saved.'));
            $this->redirectToPostedUrl($product);
        }

        if (!$existingProduct) {
            $product->id = null;
        }

        craft()->userSession->setError(Craft::t('Couldn’t save product.'));
        craft()->urlManager->setRouteVariables([
            'product' => $product
        ]);
    }

    /**
     * Previews a product.
     */
    public function actionPreviewProduct()
    {

        $this->requirePostRequest();

        $product = $this->_buildProductFromPost();
        $this->_enforceProductPermissionsForProductType($product->typeId);

        $this->_showProduct($product);
    }

    /**
     * Redirects the client to a URL for viewing a disabled product on the front end.
     *
     * @param mixed $productId
     * @param mixed $locale
     *
     * @throws HttpException
     */
    public function actionShareProduct($productId, $locale = null)
    {
        /**
         * @var $product DigitalProducts_ProductModel
         */
        $product = craft()->digitalProducts_products->getProductById($productId, $locale);

        if (!$product || !craft()->digitalProducts_productTypes->isProductTypeTemplateValid($product->getProductType())) {
            throw new HttpException(404);
        }

        $this->_enforceProductPermissionsForProductType($product->typeId);

        // Create the token and redirect to the product URL with the token in place
        $token = craft()->tokens->createToken([
            'action' => 'digitalProducts/products/viewSharedProduct',
            'params' => [
                'productId' => $productId,
                'locale' => $product->locale
            ]
        ]);

        $url = UrlHelper::getUrlWithToken($product->getUrl(), $token);
        craft()->request->redirect($url);
    }

    /**
     * Shows an product/draft/version based on a token.
     *
     * @param mixed $productId
     * @param mixed $locale
     *
     * @throws HttpException
     * @return null
     */
    public function actionViewSharedProduct($productId, $locale = null)
    {
        $this->requireToken();

        $product = craft()->digitalProducts_products->getProductById($productId, $locale);

        if (!$product) {
            throw new HttpException(404);
        }

        $this->_showProduct($product);
    }

    // Private Methods
    // =========================================================================

    /**
     * Displays a product.
     *
     * @param DigitalProducts_ProductModel $product
     *
     * @throws HttpException
     * @return null
     */
    private function _showProduct(DigitalProducts_ProductModel $product)
    {
        $productType = $product->getProductType();

        if (!$productType) {
            throw new HttpException(404);
        }

        craft()->setLanguage($product->locale);

        // Have this product override any freshly queried products with the same ID/locale
        craft()->elements->setPlaceholderElement($product);

        craft()->templates->getTwig()->disableStrictVariables();

        $this->renderTemplate($productType->template, [
            'product' => $product
        ]);
    }

    /**
     * Enforce product permissions for a product type id
     *
     * @param $productTypeId
     *
     * @throws HttpException
     */
    private function _enforceProductPermissionsForProductType($productTypeId)
    {
        // Check for general digital product commerce access
        if (!craft()->userSession->checkPermission('digitalProducts-manageProducts')) {
            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
        }

        // Check if the user can edit the products in the product type
        if (!craft()->userSession->getUser()->can('digitalProducts-manageProductType:'.$productTypeId)) {
            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
        }
    }

    /**
     * Prepare $variable array for editing a Product
     *
     * @param array $variables by reference
     *
     * @throws HttpException in case of missing/corrupt data or lacking permissions.
     */
    private function _prepareVariableArray(&$variables)
    {
        // Locale related checks
        $variables['localeIds'] = craft()->i18n->getEditableLocaleIds();

        if (!$variables['localeIds']) {
            throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this site’s locales.'));
        }

        if (empty($variables['localeId'])) {
            $variables['localeId'] = craft()->language;

            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                $variables['localeId'] = $variables['localeIds'][0];
            }
        } else {
            // Make sure they were requesting a valid locale
            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                throw new HttpException(404);
            }
        }

        // Product related checks
        if (empty($variables['product'])) {
            if (!empty($variables['productId'])) {
                $variables['product'] = craft()->digitalProducts_products->getProductById($variables['productId'], $variables['localeId']);

                if (!$variables['product']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['product'] = new DigitalProducts_ProductModel();
                $variables['product']->typeId = $variables['productType']->id;

                if (!empty($variables['localeId'])) {
                    $variables['product']->locale = $variables['localeId'];
                }
            }
        }

        // Enable locales
        if (!empty($variables['product']->id)) {
            $variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['product']->id);
        } else {
            $variables['enabledLocales'] = [];

            foreach (craft()->i18n->getEditableLocaleIds() as $locale) {
                $variables['enabledLocales'][] = $locale;
            }
        }

        // Set up tabs
        $variables['tabs'] = [];

        foreach ($variables['productType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            if ($variables['product']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($variables['product']->getErrors($field->getField()->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t($tab->name),
                'url' => '#tab'.($index + 1),
                'class' => ($hasErrors ? 'error' : null)
            ];
        }

        // Set up title and the URL for continuing editing the product
        if (!empty($variables['product']->id)) {
            $variables['title'] = $variables['product']->title;
        } else {
            $variables['title'] = Craft::t('Create a new product');
        }

        $variables['continueEditingUrl'] = "digitalproducts/products/".$variables['productType']->handle."/{id}".
            (craft()->isLocalized() && !empty($variables['localeId']) && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');
    }

    /**
     * Enable live preview for products with valid templates on desktop browsers.
     *
     * @param array $variables
     */
    private function _maybeEnableLivePreview(array &$variables)
    {
        if (!craft()->request->isMobileBrowser(true)
            && !empty($variables['productType'])
            && craft()->digitalProducts_productTypes->isProductTypeTemplateValid($variables['productType'])
        ) {
            craft()->templates->includeJs('Craft.LivePreview.init('.JsonHelper::encode([
                    'fields' => '#title-field, #fields > div > div > .field, #sku-field, #price-field',
                    'extraFields' => '#meta-pane .field',
                    'previewUrl' => $variables['product']->getUrl(),
                    'previewAction' => 'digitalProducts/products/previewProduct',
                    'previewParams' => [
                        'typeId' => $variables['productType']->id,
                        'productId' => $variables['product']->id,
                        'locale' => $variables['product']->locale,
                    ]
                ]).');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($variables['product']->id) {
                // If the product is enabled, use its main URL as its share URL.
                if ($variables['product']->getStatus() == DigitalProducts_ProductModel::LIVE) {
                    $variables['shareUrl'] = $variables['product']->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::getActionUrl('digitalProducts/products/shareProduct', [
                        'productId' => $variables['product']->id,
                        'locale' => $variables['product']->locale
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }
    }

    /**
     * @return DigitalProducts_ProductModel
     * @throws Exception
     */
    private function _buildProductFromPost()
    {
        $productId = craft()->request->getPost('productId');
        $locale = craft()->request->getPost('locale');

        if ($productId) {
            $product = craft()->digitalProducts_products->getProductById($productId, $locale);

            if (!$product) {
                throw new Exception(Craft::t('No product with the ID “{id}”',
                    ['id' => $productId]));
            }
        } else {
            $product = new DigitalProducts_ProductModel();
        }

        $data = craft()->request->getPost();

        if (isset($data['typeId'])) {
            $product->typeId = $data['typeId'];
        }

        if (isset($data['enabled'])) {
            $product->enabled = $data['enabled'];
        }

        $product->price = (float)$data['price'];
        $product->sku = $data['sku'];

        $product->postDate = $data['postDate'] ? \Craft\DateTime::createFromString($data['postDate'], \Craft\craft()->timezone) : $product->postDate;
        if (!$product->postDate) {
            $product->postDate = new \Craft\DateTime();
        }
        $product->expiryDate = $data['expiryDate'] ? \Craft\DateTime::createFromString($data['expiryDate'], \Craft\craft()->timezone) : null;

        $product->promotable = $data['promotable'];
        $product->taxCategoryId = $data['taxCategoryId'] ? $data['taxCategoryId'] : $product->taxCategoryId;
        $product->slug = $data['slug'] ? $data['slug'] : $product->slug;

        $product->localeEnabled = (bool)craft()->request->getPost('localeEnabled', $product->localeEnabled);
        $product->getContent()->title = craft()->request->getPost('title', $product->title);
        $product->setContentFromPost('fields');

        return $product;
    }
}
