<?php
namespace Craft;

/**
 * Product Type record.
 *
 * @property string $name
 * @property string $handle
 * @property string $licenseKeyFormat
 * @property bool $hasUrls
 * @property string $urlFormat
 * @property bool $skuFormat
 * @property string $template
 * @property int $fieldLayout
 * @property string $expirationRules
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductTypeRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'digitalproducts_producttypes';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['handle'], 'unique' => true],
        ];
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return [
            'fieldLayout' => [
                static::BELONGS_TO,
                'FieldLayoutRecord',
                'onDelete' => static::SET_NULL
            ],
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'name' => [AttributeType::Name, 'required' => true],
            'handle' => [AttributeType::Handle, 'required' => true],
            'licenseKeyAlphabet' => [AttributeType::String, 'required' => true],
            'licenseKeyLength' => [AttributeType::Number, 'required' => true, 'unsigned' => true],
            'hasUrls' => AttributeType::Bool,
            'urlFormat' => AttributeType::String,
            'skuFormat' => AttributeType::String,
            'template' => AttributeType::Template
        ];
    }
}
