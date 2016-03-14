<?php
namespace Craft;

/**
 * Product type model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductTypeModel extends BaseModel
{

    /**
     * @var LocaleModel[]
     */
    private $_locales;

    /**
     * @return null|string
     */
    function __toString()
    {
        return Craft::t($this->handle);
    }

    /**
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('digitalproducts/producttypes/' . $this->id);
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        if (!isset($this->_locales)) {
            if ($this->id) {
                $this->_locales = craft()->digitalProducts_productTypes->getProductTypeLocales($this->id, 'locale');
            } else {
                $this->_locales = [];
            }
        }

        return $this->_locales;
    }

    /**
     * Sets the locales on the product type
     *
     * @param $locales
     */
    public function setLocales($locales)
    {
        $this->_locales = $locales;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'productFieldLayout' => new FieldLayoutBehavior('DigitalProduct_Product',
                'fieldLayoutId'),
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'id' => AttributeType::Number,
            'name' => AttributeType::Name,
            'handle' => AttributeType::Handle,
            'licenseKeyAlphabet' => AttributeType::String,
            'licenseKeyLength' => AttributeType::Number,
            'hasUrls' => AttributeType::Bool,
            'urlFormat' => AttributeType::String,
            'skuFormat' => AttributeType::String,
            'template' => AttributeType::Template,
            'fieldLayoutId' => AttributeType::Number
        ];
    }
}
