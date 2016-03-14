<?php
namespace Craft;

/**
 * Product Type.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductTypesService extends BaseApplicationComponent
{
    /**
     * @var bool
     */
    private $_fetchedAllProductTypes = false;

    /**
     * @var
     */
    private $_productTypesById;

    /**
     * Get product types.
     *
     * @param array|\CDbCriteria $criteria
     *
     * @return DigitalProducts_ProductTypeModel[]
     */
    public function getProductTypes($criteria = [])
    {
        $results = DigitalProducts_ProductTypeRecord::model()->findAll($criteria);

        return DigitalProducts_ProductTypeModel::populateModels($results);
    }

    /**
     * Get a product type's locales by it's id.
     *
     * @param      $productTypeId
     * @param null $indexBy
     *
     * @return array
     */
    public function getProductTypeLocales($productTypeId, $indexBy = null)
    {
        $records = DigitalProducts_ProductTypeLocaleRecord::model()->findAllByAttributes([
            'productTypeId' => $productTypeId
        ]);

        return DigitalProducts_ProductTypeLocaleModel::populateModels($records, $indexBy);
    }

    /**
     * Save a product type.
     *
     * @param Commerce_ProductTypeModel $productType
     *
     * @return bool
     * @throws Exception
     * @throws \CDbException
     * @throws \Exception
     */
    public function saveProductType(DigitalProducts_ProductTypeModel $productType)
    {
        if ($productType->id) {
            $productTypeRecord = DigitalProducts_ProductTypeRecord::model()->findById($productType->id);
            if (!$productTypeRecord) {
                throw new Exception(Craft::t('No product type exists with the ID “{id}”',
                    ['id' => $productType->id]));
            }

            /** @var DigitalProducts_ProductTypeModel $oldProductType */
            $oldProductType = DigitalProducts_ProductTypeModel::populateModel($productTypeRecord);
            $isNewProductType = false;
        } else {
            $productTypeRecord = new DigitalProducts_ProductTypeRecord();
            $isNewProductType = true;
        }

        $productTypeRecord->name = $productType->name;
        $productTypeRecord->handle = $productType->handle;
        $productTypeRecord->licenseKeyAlphabet = $productType->licenseKeyAlphabet;
        $productTypeRecord->licenseKeyLength = $productType->licenseKeyLength;
        $productTypeRecord->hasUrls = $productType->hasUrls;
        $productTypeRecord->skuFormat = $productType->skuFormat;
        $productTypeRecord->template = $productType->template;

        // Make sure that all of the URL formats are set properly
        $productTypeLocales = $productType->getLocales();

        foreach ($productTypeLocales as $localeId => $productTypeLocale) {
            if ($productType->hasUrls) {
                $urlFormatAttributes = ['urlFormat'];
                $productTypeLocale->urlFormatIsRequired = true;

                foreach ($urlFormatAttributes as $attribute) {
                    if (!$productTypeLocale->validate([$attribute])) {
                        $productType->addError($attribute.'-'.$localeId, $productTypeLocale->getError($attribute));
                    }
                }
            } else {
                $productTypeLocale->urlFormat = null;
            }
        }

        $productTypeRecord->validate();
        $productType->addErrors($productTypeRecord->getErrors());

        if (!$productType->hasErrors()) {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            try {

                // Drop the old field layout
                craft()->fields->deleteLayoutById($productType->fieldLayoutId);

                // Save the new one
                $fieldLayout = $productType->asa('productFieldLayout')->getFieldLayout();
                craft()->fields->saveLayout($fieldLayout);
                $productType->fieldLayoutId = $fieldLayout->id;
                $productTypeRecord->fieldLayoutId = $fieldLayout->id;

                // Save it!
                $productTypeRecord->save(false);

                // Now that we have a product type ID, save it on the model
                if (!$productType->id) {
                    $productType->id = $productTypeRecord->id;
                }

                $newLocaleData = [];

                if (!$isNewProductType) {
                    // Get the old product type locales
                    $oldLocaleRecords = DigitalProducts_ProductTypeLocaleRecord::model()->findAllByAttributes([
                        'productTypeId' => $productType->id
                    ]);
                    $oldLocales = DigitalProducts_ProductTypeLocaleModel::populateModels($oldLocaleRecords, 'locale');

                    $changedLocaleIds = [];
                }


                foreach ($productTypeLocales as $localeId => $locale) {
                    // Was this already selected?
                    if (!$isNewProductType && isset($oldLocales[$localeId])) {
                        $oldLocale = $oldLocales[$localeId];

                        // Has the URL format changed?
                        if ($locale->urlFormat != $oldLocale->urlFormat) {
                            craft()->db->createCommand()->update('digitalproducts_producttypes_i18n', [
                                'urlFormat' => $locale->urlFormat
                            ], [
                                'id' => $oldLocale->id
                            ]);

                            $changedLocaleIds[] = $localeId;
                        }
                    } else {
                        $newLocaleData[] = [
                            $productType->id,
                            $localeId,
                            $locale->urlFormat
                        ];
                    }
                }

                // Insert the new locales
                craft()->db->createCommand()->insertAll('digitalproducts_producttypes_i18n',
                    ['productTypeId', 'locale', 'urlFormat'],
                    $newLocaleData
                );

                if (!$isNewProductType) {
                    // Drop any locales that are no longer being used, as well as the associated element
                    // locale rows

                    $droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($productTypeLocales));

                    if ($droppedLocaleIds) {
                        craft()->db->createCommand()->delete('digitalproducts_producttypes_i18n', [
                            'in',
                            'locale',
                            $droppedLocaleIds
                        ]);
                    }
                }


                if (!$isNewProductType) {
                    // Get all of the product IDs in this group
                    $criteria = craft()->elements->getCriteria('DigitalProducts_Product');
                    $criteria->typeId = $productType->id;
                    $criteria->status = null;
                    $criteria->limit = null;
                    $productIds = $criteria->ids();

                    // Should we be deleting
                    if ($productIds && $droppedLocaleIds) {
                        craft()->db->createCommand()->delete('elements_i18n', [
                            'and',
                            ['in', 'elementId', $productIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                        craft()->db->createCommand()->delete('content', [
                            'and',
                            ['in', 'elementId', $productIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                    }
                    // Are there any locales left?
                    if ($productTypeLocales) {
                        // Drop the old productType URIs if the product type no longer has URLs
                        if (!$productType->hasUrls && $oldProductType->hasUrls) {
                            craft()->db->createCommand()->update('elements_i18n',
                                ['uri' => null],
                                ['in', 'elementId', $productIds]
                            );
                        } else if ($changedLocaleIds) {
                            foreach ($productIds as $productId) {
                                craft()->config->maxPowerCaptain();

                                // Loop through each of the changed locales and update all of the products’ slugs and
                                // URIs
                                foreach ($changedLocaleIds as $localeId) {
                                    $criteria = craft()->elements->getCriteria('DigitalProduct_Product');
                                    $criteria->id = $productId;
                                    $criteria->locale = $localeId;
                                    $criteria->status = null;
                                    $updateProduct = $criteria->first();

                                    // @todo replace the getContent()->id check with 'strictLocale' param once it's added
                                    if ($updateProduct && $updateProduct->getContent()->id) {
                                        craft()->elements->updateElementSlugAndUri($updateProduct, false, false);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($transaction !== null) {
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                throw $e;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a product type by it's ID.
     *
     * @param int $productTypeId
     *
     * @return DigitalProducts_ProductTypeModel|null
     */
    public function getProductTypeById($productTypeId)
    {
        if(!$this->_fetchedAllProductTypes &&
            (!isset($this->_productTypesById) || !array_key_exists($productTypeId, $this->_productTypesById))
        )
        {
            $result = DigitalProducts_ProductTypeRecord::model()->findById($productTypeId);

            if ($result) {
                $productType = DigitalProducts_ProductTypeModel::populateModel($result);
            }
            else
            {
                $productType = null;
            }

            $this->_productTypesById[$productTypeId] = $productType;
        }

        if (isset($this->_productTypesById[$productTypeId]))
        {
            return $this->_productTypesById[$productTypeId];
        }

        return null;
    }

    /**
     * Get a product type by it's handle.
     *
     * @param int $productTypeId
     *
     * @return DigitalProducts_ProductTypeModel|null
     */
    public function getProductTypeByHandle($handle)
    {
        $result = DigitalProducts_ProductTypeRecord::model()->findByAttributes(['handle' => $handle]);

        if ($result)
        {
            $productType = DigitalProducts_ProductTypeModel::populateModel($result);
            $this->_productTypesById[$productType->id] = $productType;

            return $productType;
        }

        return null;
    }

    /**
     * Delete a product type by it's id.
     *
     * @param $id
     *
     * @return bool
     * @throws \CDbException
     * @throws \Exception
     */
    public function deleteProductTypeById($id)
    {
        try {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            $productType = $this->getProductTypeById($id);

            $criteria = craft()->elements->getCriteria('DigitalProducts_Product');
            $criteria->typeId = $productType->id;
            $criteria->status = null;
            $criteria->limit = null;
            $products = $criteria->find();

            foreach ($products as $product) {
                craft()->digitalProducts_products->deleteProduct($product);
            }

            $fieldLayoutId = $productType->asa('productFieldLayout')->getFieldLayout()->id;
            craft()->fields->deleteLayoutById($fieldLayoutId);

            $productTypeRecord = DigitalProducts_ProductTypeRecord::model()->findById($productType->id);
            $affectedRows = $productTypeRecord->delete();

            if ($transaction !== null) {
                $transaction->commit();
            }

            return (bool) $affectedRows;
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }
            throw $e;
        }
    }
}
