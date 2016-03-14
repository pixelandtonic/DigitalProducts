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
     * @param array
     *
     * @return array|null
     */
    public function getAllProductTypes()
    {
        return craft()->digitalProducts_productTypes->getProductTypes();
    }

    /**
     * @param array
     *
     * @return array|null
     */
    public function getAllLicenses()
    {
        return craft()->digitalProducts_licenses->getLicenses();
    }

    /**
     * @param array|null $criteria
     *
     * @return ElementCriteriaModel
     */
    public function products($criteria = null)
    {
        return craft()->elements->getCriteria("DigitalProducts_Product", $criteria);
    }
}
