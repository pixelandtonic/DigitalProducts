<?php
namespace Craft;

/**
 * Class Commerce_ProductElementType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 * @see       https://craftcommerce.com
 * @package   craft.plugins.commerce.elementtypes
 * @since     1.0
 */
class DigitalProducts_ProductElementType extends BaseElementType
{

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function getName()
    {
        return Craft::t('Digital Products');
    }

    /**
     * @inheritDoc BaseElementType::hasContent()
     *
     * @return bool
     */
    public function hasContent()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::hasTitles()
     *
     * @return bool
     */
    public function hasTitles()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::hasStatuses()
     *
     * @return bool
     */
    public function hasStatuses()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::isLocalized()
     *
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::getSources()
     *
     * @param null $context
     *
     * @return array|bool|false
     */
    public function getSources($context = null)
    {
        if ($context == 'index') {
            $productTypes = craft()->digitalProducts_productTypes->getEditableProductTypes();
            $editable = true;
        } else {
            $productTypes = craft()->digitalProducts_productTypes->getAllProductTypes();
            $editable = false;
        }

        $productTypeIds = [];

        foreach ($productTypes as $productType) {
            $productTypeIds[] = $productType->id;
        }

        $sources = [
            '*' => [
                'label' => Craft::t('All products'),
                'criteria' => [
                    'typeId' => $productTypeIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['postDate', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('Product Types')];

        foreach ($productTypes as $productType) {
            $key = 'productType:'.$productType->id;
            $canEditProducts = craft()->userSession->checkPermission('digitalProducts-manageProductType:'.$productType->id);

            $sources[$key] = [
                'label' => $productType->name,
                'data' => [
                    'handle' => $productType->handle,
                    'editable' => $canEditProducts
                ],
                'criteria' => [
                    'typeId' => $productType->id,
                    'editable' => $editable
                ]
            ];
        }

        // Allow plugins to modify the sources
        craft()->plugins->call('digitalProducts_modifyProductSources', [
            &$sources,
            $context
        ]);

        return $sources;
    }

    /**
     * @inheritDoc BaseElementType::defineAvailableTableAttributes()
     *
     * @return array
     */
    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'title' => ['label' => Craft::t('Title')],
            'type' => ['label' => Craft::t('Type')],
            'slug' => ['label' => Craft::t('Slug')],
            'sku' => ['label' => Craft::t('SKU')],
            'price' => ['label' => Craft::t('Price')],
            'postDate' => ['label' => Craft::t('Post Date')],
            'expiryDate' => ['label' => Craft::t('Expiry Date')],
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('digitalProducts_defineAdditionalProductTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritDoc BaseElementType::getDefaultTableAttributes()
     *
     * @param string|null $source
     *
     * @return array
     */
    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'type';
        }

        $attributes[] = 'postDate';
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    /**
     * @inheritDoc BaseElementType::defineSearchableAttributes()
     *
     * @return array
     */
    public function defineSearchableAttributes()
    {
        return ['title'];
    }


    /**
     * @inheritDoc BaseElementType::getTableAttributeHtml()
     *
     * @param BaseElementModel $element
     * @param string           $attribute
     *
     * @return mixed|string
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        // First give plugins a chance to set this
        $pluginAttributeHtml = craft()->plugins->callFirst('digitalProducts_getProductTableAttributeHtml', [
            $element,
            $attribute
        ], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        /* @var $productType DigitalProducts_ProductTypeModel */
        $productType = $element->getProductType();

        switch ($attribute) {
            case 'type': {
                return ($productType ? Craft::t($productType->name) : '');
            }

            case 'taxCategory': {
                $taxCategory = $element->getTaxCategory();

                return ($taxCategory ? Craft::t($taxCategory->name) : '');
            }
            case 'defaultPrice': {
                $code = craft()->commerce_paymentCurrencies->getPrimaryPaymentCurrencyIso();

                return craft()->numberFormatter->formatCurrency($element->$attribute, strtoupper($code));
            }

            case 'promotable': {
                return ($element->$attribute ? '<span data-icon="check" title="'.Craft::t('Yes').'"></span>' : '');
            }
            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    /**
     * @inheritDoc BaseElementType::defineSortableAttributes()
     *
     * @return array
     */
    public function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('Title'),
            'postDate' => Craft::t('Post Date'),
            'expiryDate' => Craft::t('Expiry Date'),
            'price' => Craft::t('Price'),
        ];

        // Allow plugins to modify the attributes
        craft()->plugins->call('digitalProducts_modifyProductSortableAttributes', [&$attributes]);

        return $attributes;
    }


    /**
     * @inheritDoc BaseElementType::getStatuses()
     *
     * @return array|null
     */
    public function getStatuses()
    {
        return [
            DigitalProducts_ProductModel::LIVE => Craft::t('Live'),
            DigitalProducts_ProductModel::PENDING => Craft::t('Pending'),
            DigitalProducts_ProductModel::EXPIRED => Craft::t('Expired'),
            BaseElementModel::DISABLED => Craft::t('Disabled')
        ];
    }


    /**
     * @inheritDoc BaseElement::defineCriteriaAttributes()
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return [
            'typeId' => AttributeType::Mixed,
            'type' => AttributeType::Mixed,
            'postDate' => AttributeType::Mixed,
            'expiryDate' => AttributeType::Mixed,
            'after' => AttributeType::Mixed,
            'order' => [AttributeType::String, 'default' => 'postDate desc'],
            'before' => AttributeType::Mixed,
            'status' => [
                AttributeType::String,
                'default' => DigitalProducts_ProductModel::LIVE
            ],
            'editable' => AttributeType::Bool,
        ];
    }

    /**
     * @inheritDoc BaseElementType::getElementQueryStatusCondition()
     *
     * @param DbCommand $query
     * @param string    $status
     *
     * @return array|false|string|void
     */
    public function getElementQueryStatusCondition(DbCommand $query, $status)
    {
        $currentTimeDb = DateTimeHelper::currentTimeForDb();

        switch ($status) {
            case Commerce_ProductModel::LIVE: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    "products.postDate <= '{$currentTimeDb}'",
                    [
                        'or',
                        'products.expiryDate is null',
                        "products.expiryDate > '{$currentTimeDb}'"
                    ]
                ];
            }

            case Commerce_ProductModel::PENDING: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    "products.postDate > '{$currentTimeDb}'"
                ];
            }

            case Commerce_ProductModel::EXPIRED: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    'products.expiryDate is not null',
                    "products.expiryDate <= '{$currentTimeDb}'"
                ];
            }
        }
    }


    /**
     * @inheritDoc BaseElementType::modifyElementsQuery()
     *
     * @param DbCommand $query
     * @param ElementCriteriaModel $criteria
     *
     * @return false|null|void
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
            ->addSelect("products.id, products.typeId, products.promotable, products.postDate, products.expiryDate, products.price, products.sku, products.taxCategoryId")
            ->join('digitalproducts_products products', 'products.id = elements.id')
            ->join('digitalproducts_producttypes producttypes', 'producttypes.id = products.typeId');

        if ($criteria->postDate) {
            $query->andWhere(DbHelper::parseDateParam('products.postDate', $criteria->postDate, $query->params));
        } else {
            if ($criteria->after) {
                $query->andWhere(DbHelper::parseDateParam('products.postDate', '>='.$criteria->after, $query->params));
            }

            if ($criteria->before) {
                $query->andWhere(DbHelper::parseDateParam('products.postDate', '<'.$criteria->before, $query->params));
            }
        }

        if ($criteria->expiryDate) {
            $query->andWhere(DbHelper::parseDateParam('products.expiryDate', $criteria->expiryDate, $query->params));
        }

        if ($criteria->type) {
            if ($criteria->type instanceof DigitalProducts_ProductTypeModel) {
                $criteria->typeId = $criteria->type->id;
                $criteria->type = null;
            } else {
                $query->andWhere(DbHelper::parseParam('producttypes.handle', $criteria->type, $query->params));
            }
        }

        if ($criteria->typeId) {
            $query->andWhere(DbHelper::parseParam('products.typeId', $criteria->typeId, $query->params));
        }

        if ($criteria->editable) {
            $user = craft()->userSession->getUser();

            if (!$user) {
                return false;
            }

            // Limit the query to only the sections the user has permission to edit
            $editableProductTypeIds = craft()->digitalProducts_productTypes->getEditableProductTypeIds();

            if (!$editableProductTypeIds) {
                return false;
            }

            $query->andWhere([
                'in',
                'products.typeId',
                $editableProductTypeIds
            ]);
        }

        return true;
    }


    /**
     * @inheritDoc BaseElementType::populateElementModel()
     *
     * @param array $row
     *
     * @return BaseElementModel|void
     */
    public function populateElementModel($row)
    {
        return DigitalProducts_ProductModel::populateModel($row);
    }

    /**
     * @inheritDoc BaseElementType::getEditorHtml()
     *
     * @param BaseElementModel $element
     *
     * @return string
     */
    public function getEditorHtml(BaseElementModel $element)
    {
        /** @ var Commerce_ProductModel $element */
        $templatesService = craft()->templates;
        $html = $templatesService->renderMacro('digitalProducts/products/_fields', 'titleField', [$element]);
        $html .= parent::getEditorHtml($element);
        $html .= $templatesService->renderMacro('digitalProducts/products/_fields', 'generalFields', [$element]);
        $html .= $templatesService->renderMacro('digitalProducts/products/_fields', 'pricingFields', [$element]);
        $html .= $templatesService->renderMacro('digitalProducts/products/_fields', 'behavioralMetaFields', [$element]);
        $html .= $templatesService->renderMacro('digitalProducts/products/_fields', 'generalMetaFields', [$element]);

        return $html;
    }

    /**
     * @inheritDoc BaseElementType::routeRequestForMatchedElement()
     *
     * @param BaseElementModel $element
     *
     * @return bool|mixed
     */
    public function routeRequestForMatchedElement(BaseElementModel $element)
    {
        /** @var DigitalProducts_ProductModel $element */
        if ($element->getStatus() == DigitalProducts_ProductModel::LIVE) {
            $productType = $element->getProductType();

            if ($productType->hasUrls) {
                return [
                    'action' => 'templates/render',
                    'params' => [
                        'template' => $productType->template,
                        'variables' => [
                            'product' => $element
                        ]
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * @inheritDoc IElementType::getEagerLoadingMap()
     *
     * @param BaseElementModel[]  $sourceElements
     * @param string $handle
     *
     * @return array|false
     */
    public function getEagerLoadingMap($sourceElements, $handle)
    {
        if ($handle == 'isLicensed') {
            $user = craft()->userSession->getUser();
            if ($user)
            {
                // Get the source element IDs
                $sourceElementIds = array();

                foreach ($sourceElements as $sourceElement) {
                    $sourceElementIds[] = $sourceElement->id;
                }

                $map = craft()->db->createCommand()
                    ->select('productId as source, id as target')
                    ->from('digitalproducts_licenses')
                    ->where(array('in', 'productId', $sourceElementIds))
                    ->andWhere('userId = :currentUser', array(':currentUser' => $user->id))
                    ->queryAll();

                return array(
                    'elementType' => 'DigitalProducts_License',
                    'map' => $map
                );
            }
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }
}
