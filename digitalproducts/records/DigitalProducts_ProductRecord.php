<?php
namespace Craft;

/**
 * Product record.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc BaseRecord::getTableName()
     *
     * @return string
     */
    public function getTableName()
    {
        return 'digitalproducts_products';
    }

    /**
     * @inheritdoc BaseRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['sku'], 'unique' => true],
        ];
    }

    /**
     * @inheritdoc BaseRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'element' => [
                static::BELONGS_TO,
                'ElementRecord',
                'id',
                'required' => true,
                'onDelete' => static::CASCADE
            ],
            'type' => [
                static::BELONGS_TO,
                'DigitalProducts_ProductTypeRecord',
                'onDelete' => static::CASCADE
            ],
            'taxCategory' => [
                static::BELONGS_TO,
                'Commerce_TaxCategoryRecord',
                'required' => true
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc BaseRecord::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'postDate' => AttributeType::DateTime,
            'expiryDate' => AttributeType::DateTime,
            'promotable' => AttributeType::Bool,

            'sku' => [AttributeType::String, 'required' => true],
            'price' => [AttributeType::Number, 'decimals' => 4, 'required' => false],
        ];
    }
}
