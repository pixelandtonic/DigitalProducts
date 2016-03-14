<?php
namespace Craft;

/**
 * Product type locale model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */

class DigitalProducts_ProductTypeLocaleModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $urlFormatIsRequired = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseModel::rules()
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();

        if ($this->urlFormatIsRequired) {
            $rules[] = ['urlFormat', 'required'];
        }

        return $rules;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'id' => AttributeType::Number,
            'productTypeId' => AttributeType::Number,
            'locale' => AttributeType::Locale,
            'urlFormat' => [AttributeType::UrlFormat, 'label' => 'URL Format']
        ];
    }
}
