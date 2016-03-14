<?php
namespace Craft;

/**
 * Product record.
 *
 * @property string $licenseKey
 * @property string $status
 * @property string $licenseeName
 * @property string $licenseeEmail
 * @property int $userId
 * @property int $orderId
 * @property int $productId
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_LicenseRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'digitalproducts_licenses';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['licenseKey', 'productId'], 'unique' => true],
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
            'product' => [
                static::BELONGS_TO,
                'DigitalProducts_ProductRecord',
                'onDelete' => static::CASCADE
            ]
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'licenseKey' => [AttributeType::String, 'required' => true],
            'licenseeName' => AttributeType::String,
            'licenseeEmail' => [AttributeType::String, 'required' => true],
            'enabled' => AttributeType::Bool,
            'userId' => AttributeType::Number,
            'orderId' => AttributeType::Number,
        ];
    }
}
