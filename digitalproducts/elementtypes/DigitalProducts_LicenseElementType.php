<?php
namespace Craft;

/**
 * Class Commerce_LicenseElementType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @since     1.0
 */
class DigitalProducts_LicenseElementType extends BaseElementType
{

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function getName()
    {

        return Craft::t('License');
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
     * @inheritDoc BaseElementType::getSources()
     *
     * @param null $context
     *
     * @return array|bool|false
     */
    public function getSources($context = null)
    {
        $productTypes = craft()->digitalProducts_productTypes->getProductTypes();

        $productTypeIds = [];

        foreach ($productTypes as $productType) {
            $productTypeIds[] = $productType->id;
        }

        $sources = [
            '*' => [
                'label' => Craft::t('All product types'),
                'criteria' => ['licenseIssueDate' => $productTypeIds],
                'defaultSort' => ['dateCreated', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('Product Types')];

        foreach ($productTypes as $productType) {
            $key = 'productType:'.$productType->id;

            $sources[$key] = [
                'label' => $productType->name,
                'data' => [
                    'handle' => $productType->handle
                ],
                'criteria' => ['typeId' => $productType->id]
            ];
        }

        // Allow plugins to modify the sources
        craft()->plugins->call('digitalProducts_modifyLicenseSources', [
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
            'product' => ['label' => Craft::t('Licensed Product')],
            'productType' => ['label' => Craft::t('Product Type')],
            'dateCreated' => ['label' => Craft::t('License Issue Date')],
            'licensedTo' => ['label' => Craft::t('Licensed To')],
            'orderLink' => ['label' => Craft::t('Associated Order')]
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('digitalProducts_defineAdditionalLicenseTableAttributes', [], true);

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
            $attributes[] = 'productType';
        }

        $attributes[] = 'product';
        $attributes[] = 'dateCreated';
        $attributes[] = 'licensedTo';
        $attributes[] = 'orderLink';


        return $attributes;
    }

    /**
     * @inheritDoc BaseElementType::defineSearchableAttributes()
     *
     * @return array
     */
    public function defineSearchableAttributes()
    {
        return ['licensedTo', 'product'];
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
        $pluginAttributeHtml = craft()->plugins->callFirst('digitalProducts_getLicenseTableAttributeHtml', [
            $element,
            $attribute
        ], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        /**
         * @var DigitalProducts_LicenseModel $element
         */
        switch ($attribute) {
            case 'productType': {
                return $element->getProductType();
            }

            case 'licensedTo': {
                return $element->getLicensedTo();
            }

            case 'orderLink': {
                $url = $element->getOrderEditUrl();

                return $url ? '<a href="'.$url.'">'.Craft::t('View order').'</a>' : '';
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
            'slug' => Craft::t('Product name'),
            'licensedTo' => Craft::t('Owner'),
            'dateCreated' => Craft::t('License issue date'),
        ];

        // Allow plugins to modify the attributes
        craft()->plugins->call('digitalProducts_modifyLicenseSortableAttributes', [&$attributes]);

        return $attributes;
    }

    /**
     * @inheritDoc BaseElement::defineCriteriaAttributes()
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return [
            'email' => AttributeType::String,
            'ownerEmail' => AttributeType::String,
            'userEmail' => AttributeType::String,

            'owner' => AttributeType::Mixed,
            'ownerId' => AttributeType::Number,

            'product' => AttributeType::Mixed,
            'productId' => AttributeType::Number,

            'type' => AttributeType::Mixed,
            'typeId' => AttributeType::Number,

            'licenseDate' => AttributeType::DateTime,
            'before' => AttributeType::Bool,
            'after' => AttributeType::Bool,

            'orderId' => AttributeType::Number,
            'licenseKey' => AttributeType::String,

            'status' => [
                AttributeType::String,
                'default' => BaseElementModel::ENABLED
            ],

            'order' => [AttributeType::String, 'default' => 'dateCreated desc'],
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
        switch ($status) {
            case BaseElementModel::ENABLED: {
                return 'elements.enabled = 1';
            }

            case BaseElementModel::DISABLED: {
                return 'elements.enabled = 0';
            }
        }
    }


    /**
     * @inheritDoc BaseElementType::modifyElementsQuery()
     *
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
     *
     * @return false|null|void
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
            ->addSelect("licenses.id, licenses.productId, licenses.licenseKey, licenses.ownerName, licenses.ownerEmail, licenses.userId, licenses.orderId, products.typeId as productTypeId")
            ->join('digitalproducts_licenses licenses', 'licenses.id = elements.id')
            ->leftJoin('digitalproducts_products products', 'products.id = licenses.productId')
            ->leftJoin('users users', 'users.id = licenses.userId')
            ->leftJoin('digitalproducts_producttypes producttypes', 'producttypes.id = products.typeId');

        if ($criteria->email) {
            $query->andWhere([
                'or',
                ['licenses.ownerEmail = :email', 'users.email = :email'],
                [':email' => $criteria->licensedEmail]
            ]);
        }

        if ($criteria->ownerEmail) {
            $query->andWhere(DbHelper::parseParam('licenses.ownerEmail', $criteria->ownerEmail, $query->params));
        }

        if ($criteria->userEmail) {
            $query->andWhere(DbHelper::parseParam('users.email', $criteria->userEmail, $query->params));
        }

        if ($criteria->owner) {
            if ($criteria->owner instanceof UserModel) {
                $criteria->ownerId = $criteria->owner->id;
                $criteria->owner = null;
            } else {
                $query->andWhere(DbHelper::parseParam('users.username', $criteria->owner, $query->params));
            }
        }

        if ($criteria->ownerId) {
            $query->andWhere(DbHelper::parseParam('licenses.userId', $criteria->ownerId, $query->params));
        }

        if ($criteria->product) {
            if ($criteria->product instanceof DigitalProducts_ProductModel) {
                $criteria->productId = $criteria->product->id;
                $criteria->product = null;
            } else {
                $query->andWhere(DbHelper::parseParam('products.sku', $criteria->type, $query->params));
            }
        }

        if ($criteria->productId) {
            if ($criteria->productId != ':all:')
            {
                $query->andWhere(DbHelper::parseParam('products.id', $criteria->productId, $query->params));
            }
        } else {
            $query->andWhere(DbHelper::parseParam('licenses.productId', ':notempty:', $query->params));
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

        if ($criteria->licenseDate) {
            $query->andWhere(DbHelper::parseDateParam('licenses.dateCreated', $criteria->licenseDate, $query->params));
        } else {
            if ($criteria->after) {
                $query->andWhere(DbHelper::parseDateParam('licenses.dateCreated', '>='.$criteria->after, $query->params));
            }
            if ($criteria->before) {
                $query->andWhere(DbHelper::parseDateParam('licenses.dateCreated', '<'.$criteria->before, $query->params));
            }
        }

        if ($criteria->orderId)
        {
            $query->andWhere(DbHelper::parseParam('licenses.orderId', $criteria->orderId, $query->params));
        }

        if ($criteria->licenseKey) {
            $query->andWhere(DbHelper::parseParam('licenses.licenseKey', $criteria->licenseKey, $query->params));
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
        return DigitalProducts_LicenseModel::populateModel($row);
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
        if ($handle == 'product') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, productId as target')
                ->from('digitalproducts_licenses')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'DigitalProducts_Product',
                'map' => $map
            );
        }

        if ($handle == 'order') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, orderId as target')
                ->from('digitalproducts_licenses')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'Commerce_Order',
                'map' => $map
            );
        }

        if ($handle == 'owner') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, userId as target')
                ->from('digitalproducts_licenses')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'User',
                'map' => $map
            );
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }

    // Protected methods
    // =========================================================================

    /**
     * Preps the element criteria for a given table attribute
     *
     * @param ElementCriteriaModel $criteria
     * @param string               $attribute
     *
     * @return void
     */
    protected function prepElementCriteriaForTableAttribute(ElementCriteriaModel $criteria, $attribute)
    {
        if ($attribute == 'product')
        {
            $with = $criteria->with ?: array();
            $with[] = 'product';
            $criteria->with = $with;
            return;
        }

        if ($attribute == 'licensedTo')
        {
            $with = $criteria->with ?: array();
            $with[] = 'owner';
            $criteria->with = $with;
            return;
        }

        parent::prepElementCriteriaForTableAttribute($criteria, $attribute);
    }

}
