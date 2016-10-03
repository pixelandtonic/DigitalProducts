<?php
namespace Craft;

use Commerce\Base\Purchasable as BasePurchasable;

/**
 * Product model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductModel extends BasePurchasable
{

    const LIVE = 'live';
    const PENDING = 'pending';
    const EXPIRED = 'expired';

    /**
     * @var string
     */
    protected $elementType = 'DigitalProducts_Product';

    /**
     * @var DigitalProducts_ProductTypeModel
     */
    private $_productType;

    /**
     * @var bool
     */
    private $_isLicensed;

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    function __toString()
    {
        return Craft::t($this->title);
    }

    /**
     * @inheritdoc BaseElementModel::getStatus()
     *
     * @return string|null
     */
    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == static::ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

            if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime)) {
                return static::LIVE;
            } else {
                if ($postDate > $currentTime) {
                    return static::PENDING;
                } else {
                    return static::EXPIRED;
                }
            }
        }

        return $status;
    }

    /**
     * @inheritdoc BaseElementModel::isEditable()
     *
     * @return bool
     */
    public function isEditable()
    {
        if ($this->getProductType()) {
            $id = $this->getProductType()->id;

            return craft()->userSession->checkPermission('digitalProducts-manageProductType:'.$id);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }


    /**
     * @inheritdoc BaseElementModel::getCpEditUrl()
     *
     * @return string
     */
    public function getCpEditUrl()
    {
        $productType = $this->getProductType();

        if ($productType) {
            return UrlHelper::getCpUrl('digitalproducts/products/'.$productType->handle.'/'.$this->id);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc BaseElementModel::getFieldLayout()
     *
     * @return FieldLayoutModel|null
     */
    public function getFieldLayout()
    {
        $productType = $this->getProductType();

        if ($productType) {
            return $productType->asa('productFieldLayout')->getFieldLayout();
        }

        return null;
    }

    /**
     * @inheritdoc BaseElementModel::getUrlFormat()
     *
     * Returns the URL format used to generate this element's URL.
     *
     * @return string
     */
    public function getUrlFormat()
    {
        $productType = $this->getProductType();

        if ($productType && $productType->hasUrls) {
            $productTypeLocales = $productType->getLocales();

            if (isset($productTypeLocales[$this->locale])) {
                return $productTypeLocales[$this->locale]->urlFormat;
            }
        }

        return '';
    }

    /**
     * Returns the product's product type model.
     *
     * @return DigitalProducts_ProductTypeModel
     */
    public function getProductType()
    {
        if ($this->_productType) {
            return $this->_productType;
        }

        return $this->_productType = craft()->digitalProducts_productTypes->getProductTypeById($this->typeId);
    }

    /**
     * Returns the product's product type model. Alias of ::getProductType()
     *
     * @return DigitalProducts_ProductTypeModel
     */
    public function getType()
    {
        return $this->getProductType();
    }

    /**
     * Return true if the current user has a license for this product.
     *
     * @return bool
     */
    public function getIsLicensed()
    {
        if ($this->_isLicensed == null) {
            $this->_isLicensed = false;
            $user = craft()->userSession->getUser();
            if ($user) {
                $criteria = ['owner' => $user, 'product' => $this];
                $license = craft()->elements->getCriteria("DigitalProducts_License", $criteria)->first();

                if ($license) {
                    $this->_isLicensed = true;
                }
            }
        }

        return $this->_isLicensed;
    }

    /**
     * @inheritdoc BaseElementModel::setEagerLoadedElements()
     *
     * @param string             $handle   The handle to load the elements with in the future
     * @param BaseElementModel[] $elements The eager-loaded elements
     */
    public function setEagerLoadedElements($handle, $elements)
    {
        if ($handle == 'isLicensed') {
            $this->_isLicensed = isset($elements[0]) ? true : false;
            return;
        }

        parent::setEagerLoadedElements($handle, $elements);
    }
    // Implement Purchasable
    // =========================================================================
    /**
     * @inheritdoc Purchasable::getPurchasableId()
     *
     * @return int
     */
    public function getPurchasableId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc Purchasable::getSnapshot()
     *
     * @return array
     */
    public function getSnapshot()
    {
        return $this->getAttributes();
    }

    /**
     * @inheritdoc Purchasable::getPrice()
     *
     * @return float decimal(14,4)
     */
    public function getPrice()
    {
        return $this->getAttribute('price');
    }

    /**
     * @inheritdoc Purchasable::getSku()
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getAttribute('sku');
    }

    /**
     * @inheritdoc Purchasable::getDescription()
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc Purchasable::getTaxCategoryId()
     *
     * @return int
     */
    public function getTaxCategoryId()
    {
        return $this->getAttribute('taxCategoryId');
    }

    /**
     * @inheritdoc Purchasable::validateLineItem()
     *
     * @param \Craft\Commerce_LineItemModel $lineItem
     *
     * @return mixed
     */
    public function validateLineItem(\Craft\Commerce_LineItemModel $lineItem)
    {
        return;
    }

    /**
     * @inheritdoc Purchasable::hasFreeShipping()
     *
     * @return bool
     */
    public function hasFreeShipping()
    {
        return true;
    }

    /**
     * @inheritdoc Purchasable::getIsPromotable()
     *
     * @return bool
     */
    public function getIsPromotable()
    {
        return $this->getAttribute('promotable');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc BaseElementModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'postDate' => AttributeType::DateTime,
            'expiryDate' => AttributeType::DateTime,
            'promotable' => [AttributeType::Bool, 'default' => true],
            'typeId' => AttributeType::Number,
            'sku' => AttributeType::String,
            'taxCategoryId' => AttributeType::Number,
            'price' => [AttributeType::Number, 'decimals' => 4],
        ]);
    }
}
