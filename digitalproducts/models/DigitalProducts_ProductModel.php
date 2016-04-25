<?php
namespace Craft;

use Commerce\Interfaces\Purchasable;

/**
 * Product model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductModel extends BaseElementModel implements Purchasable
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
     * @return null|string
     */
    function __toString()
    {
        return Craft::t($this->title);
    }

    /**
     * @return null|string
     */
    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == static::ENABLED && $this->postDate)
        {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

            if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
            {
                return static::LIVE;
            }
            else
            {
                if ($postDate > $currentTime)
                {
                    return static::PENDING;
                }
                else
                {
                    return static::EXPIRED;
                }
            }
        }

        return $status;
    }
    
    /**
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }


    /**
     * @return string
     */
    public function getCpEditUrl()
    {
        $productType = $this->getProductType();

        if ($productType)
        {
            return UrlHelper::getCpUrl('digitalproducts/products/' . $productType->handle . '/' . $this->id);
        }
        else
        {
            return null;
        }
    }

    /**
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

    /*
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
        if ($this->_productType)
        {
            return $this->_productType;
        }

        return $this->_productType = craft()->digitalProducts_productTypes->getProductTypeById($this->typeId);
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'postDate' => AttributeType::DateTime,
            'expiryDate' => AttributeType::DateTime,
            'promotable' => [AttributeType::Bool,'default'=>true],
            'typeId' => AttributeType::Number,
            'sku' => AttributeType::String,
            'taxCategoryId' => AttributeType::Number,
            'price' => [AttributeType::Number, 'decimals' => 4],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPurchasableId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getSnapshot()
    {
        return $this->getAttributes();
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return $this->getAttribute('price');
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->getAttribute('sku');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getTaxCategoryId()
    {
        return $this->getAttribute('taxCategoryId');
    }

    /**
     * @inheritdoc
     */
    public function validateLineItem(\Craft\Commerce_LineItemModel $lineItem)
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function hasFreeShipping()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getIsPromotable()
    {
        return $this->getAttribute('promotable');
    }


}
