<?php
namespace Craft;

/**
 * Product service.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_ProductsService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    /**
     * Get a Digital Product by it's ID.
     *
     * @param int $id
     * @param int $localeId
     *
     * @return DigitalProducts_ProductModel
     */
    public function getProductById($id, $localeId = null)
    {
        return craft()->elements->getElementById($id, 'DigitalProducts_Product', $localeId);
    }

    /**
     * Get Products by criteria.
     *
     * @param array|ElementCriteriaModel $criteria
     *
     * @return DigitalProducts_ProductModel[]
     */
    public function getProducts($criteria = [])
    {
        if (!$criteria instanceof ElementCriteriaModel) {
            $criteria = craft()->elements->getCriteria('DigitalProducts_Product', $criteria);
        }

        return $criteria->find();
    }


    /**
     * Save a Product.
     *
     * @param DigitalProducts_ProductModel $product
     *
     * @return bool
     * @throws Exception in case of invalid data
     * @throws \Exception if saving of the Element failed causing a failed transaction
     */
    public function saveProduct(DigitalProducts_ProductModel $product)
    {
        if (!$product->id) {
            $record = new DigitalProducts_ProductRecord();
        } else {
            $record = DigitalProducts_ProductRecord::model()->findById($product->id);

            if (!$record) {
                throw new Exception(Craft::t('No product exists with the ID “{id}”',
                    ['id' => $product->id]));
            }
        }

        $record->postDate = $product->postDate;
        $record->expiryDate = $product->expiryDate;
        $record->typeId = $product->typeId;
        $record->promotable = $product->promotable;
        $record->taxCategoryId = $product->taxCategoryId;
        $record->price = $product->price;

        $productType = craft()->digitalProducts_productTypes->getProductTypeById($product->typeId);

        if (!$productType) {
            throw new Exception(Craft::t('No product type exists with the ID “{id}”',
                ['id' => $product->typeId]));
        }

        if (empty($product->sku)) {
            try {
                $product->sku = craft()->templates->renderObjectTemplate($productType->skuFormat, $product);
            } catch (\Exception $e) {
                $product->sku = "";
            }
        }

        $record->sku = $product->sku;

        $record->validate();
        $product->addErrors($record->getErrors());

        if ($product->hasErrors()) {
            return false;
        }

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $event = new Event($this, ['product' => $product, 'isNewProduct' => !$product->id]);
            $this->onBeforeSaveDigitalProduct($event);

            $success = false;
            
            if ($event->performAction) {
                $success = craft()->commerce_purchasables->saveElement($product);
            }

            if (!$success) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                return false;
            }

            $record->id = $product->id;
            $record->save(false);


            if ($transaction !== null) {
                $transaction->commit();
            }

            $event = new Event($this, ['product' => $product]);
            $this->onSaveDigitalProduct($event);
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return true;
    }

    /**
     * Delete a Product.
     *
     * @param DigitalProducts_ProductModel $product
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct($product)
    {
        $event = new Event($this, ['product' => $product]);
        $this->onBeforeDeleteDigitalProduct($event);

        if ($event->performAction && craft()->elements->deleteElementById($product->id)) {
            $event = new Event($this, ['product' => $product]);
            $this->onDeleteDigitalProduct($event);
            return true;
        }
        
        return false;
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onBeforeDeleteDigitalProduct(Event $event)
    {
        $this->raiseEvent('onBeforeDeleteDigitalProduct', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onDeleteDigitalProduct(Event $event)
    {
        $this->raiseEvent('onDeleteDigitalProduct', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onBeforeSaveDigitalProduct(Event $event)
    {
        $this->raiseEvent('onBeforeSaveDigitalProduct', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onSaveDigitalProduct(Event $event)
    {
        $this->raiseEvent('onSaveDigitalProduct', $event);
    }
}
