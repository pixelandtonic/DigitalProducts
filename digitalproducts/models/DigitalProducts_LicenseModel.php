<?php
namespace Craft;

/**
 * License model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_LicenseModel extends BaseElementModel
{
    /**
     * @var string
     */
    private $_licensedTo = null;

    /**
     * @var DigitalProducts_ProductModel
     */
    private $_product = null;

    /**
     * @var string
     */
    protected $elementType = 'DigitalProducts_License';

    /**
     * @return null|string
     */
    public function __toString()
    {
        return Craft::t('License for “{product}”', array('product' => $this->getProductName()));
    }

    /**
     * @return string
     */
    public function getLicensedTo()
    {
        if (is_null($this->_licensedTo))
        {
            $this->_licensedTo = "";

            if (!empty($this->userId) && $user = craft()->users->getUserById($this->userId))
            {
                $this->_licensedTo = $user->email;
            }
            else
            {
                $this->_licensedTo = $this->licenseeEmail;
            }
        }

        return $this->_licensedTo;
    }

    /**
     * @return bool|DigitalProducts_ProductModel
     */
    public function getProduct()
    {
        if ($this->_product)
        {
            return $this->_product;
        }

        return $this->_product = craft()->digitalProducts_products->getProductById($this->productId);
    }

    /**
     * @return DigitalProducts_ProductTypeModel|null
     */
    public function getProductType()
    {
        $product = $this->getProduct();

        if ($product)
        {
            return $product->getProductType();
        }

        return null;
    }
    
    /**
     * @return string
     */
    public function getProductName()
    {
        return (string) $this->getProduct();
    }

    /**
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('digitalproducts/licenses/' . $this->id);
    }

    /**
     * @return string
     */
    public function getOrderEditUrl()
    {
        if ($this->orderId)
        {
            return UrlHelper::getCpUrl('commerce/orders/' . $this->orderId);
        }

        return "";
    }


    /**
     * Sets some eager loaded elements on a given handle.
     *
     * @param string             $handle   The handle to load the elements with in the future
     * @param BaseElementModel[] $elements The eager-loaded elements
     */
    public function setEagerLoadedElements($handle, $elements)
    {
        if ($handle == 'product') {
            $this->_product = reset($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }
    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id' => AttributeType::Number,
            'productId' => AttributeType::Number,
            'licenseKey' => AttributeType::String,
            'licenseeName' => AttributeType::String,
            'licenseeEmail' => AttributeType::String,
            'userId' => AttributeType::Number,
            'orderId' => AttributeType::Number,
            'dateCreated'   => AttributeType::DateTime,
            'dateUpdated'   => AttributeType::DateTime,
        ]);
    }
}