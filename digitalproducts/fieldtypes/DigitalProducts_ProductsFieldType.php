<?php
namespace Craft;

/**
 * Class DigitalProducts_ProductsFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductsFieldType extends BaseElementFieldType
{
    // Properties
    // =========================================================================

    /**
     * The element type this field deals with.
     *
     * @var string $elementType
     */
    protected $elementType = 'DigitalProducts_Product';

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseElementFieldType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Digital Products');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseElementFieldType::getAddButtonLabel()
     *
     * @return string
     */
    protected function getAddButtonLabel()
    {
        return Craft::t('Add a product');
    }
}
