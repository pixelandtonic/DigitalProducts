<?php
namespace Craft;

/**
 * Product record.
 *
 * @property string $slug
 * @property string $sku
 * @property float $price
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'digitalproducts_products';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['sku'], 'unique' => true],
        ];
    }

    /**
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

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'postDate' => AttributeType::DateTime,
            'expiryDate' => AttributeType::DateTime,
            'promotable' => AttributeType::Bool,

            'sku' => [AttributeType::String, 'required' => true],
            'price' => [AttributeType::Number, 'decimals' => 4, 'required' => true],
        ];
    }
}
