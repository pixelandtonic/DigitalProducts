<?php
namespace Craft;

/**
 * Variable class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 */
class DigitalProductsVariable
{
    /**
     * Return all Product Types.
     *
     * @param array
     *
     * @return array|null
     */
    public function getAllProductTypes()
    {
        return craft()->digitalProducts_productTypes->getProductTypes();
    }

    /**
     * Get Licenses.
     *
     * @param array|null $criteria
     *
     * @return array|null
     */
    public function licenses($criteria = null)
    {
        return craft()->elements->getCriteria("DigitalProducts_License", $criteria);
    }

    /**
     * Get Digital Products.
     *
     * @param array|null $criteria
     *
     * @return ElementCriteriaModel
     */
    public function products($criteria = null)
    {
        return craft()->elements->getCriteria("DigitalProducts_Product", $criteria);
    }
}
