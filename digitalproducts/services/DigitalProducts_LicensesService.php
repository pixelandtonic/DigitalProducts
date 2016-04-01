<?php
namespace Craft;

/**
 * Licenses service.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */
class DigitalProducts_LicensesService extends BaseApplicationComponent
{
    /**
     * Get a digital product by it's ID.
     *
     * @param int $id
     *
     * @return DigitalProducts_ProductModel
     */
    public function getLicenseById($id)
    {
        return craft()->elements->getElementById($id);
    }

    /**
     * Get licenses by criteria
     *
     * @param array|ElementCriteriaModel $criteria
     *
     * @return DigitalProducts_LicenseModel[]
     */
    public function getLicenses($criteria = [])
    {

        if (!$criteria instanceof ElementCriteriaModel)
        {
            $criteria = craft()->elements->getCriteria('DigitalProducts_License', $criteria);
        }

        return $criteria->find();
    }
    
    /**
     * @param DigitalProducts_LicenseModel $license
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function saveLicense(DigitalProducts_LicenseModel $license)
    {
        if (!$license->id) {
            $record = new DigitalProducts_LicenseRecord();
        } else {
            $record = DigitalProducts_LicenseRecord::model()->findById($license->id);

            if (!$record) {
                throw new Exception(Craft::t('No license exists with the ID “{id}”',
                    ['id' => $license->id]));
            }
        }

        $record->enabled = $license->enabled;
        $record->licenseeName = $license->licenseeName;
        $record->licenseeEmail = $license->licenseeEmail;
        

        if (empty($license->productId))
        {
            $license->addError('productId', Craft::t('{attribute} cannot be blank.', array('attribute' => 'Product')));
        }

        if (empty($license->userId) && empty($license->licenseeEmail))
        {
            $license->addError('userId', Craft::t('A license must have either an email or a licensee assigned to it.'));
            $license->addError('licenseeEmail', Craft::t('A license must have either an email or a licensee assigned to it.'));
        }

        // See if we already have issues with provided data.
        if ($license->hasErrors()) {
            return false;
        }

        $record->userId = $license->userId;
        $record->productId = $license->productId;
        $record->orderId = $license->orderId;

        /**
         * @var $product DigitalProducts_ProductModel
         */
        $product = craft()->digitalProducts_products->getProductById($license->productId);

        if (!$product) {
            throw new Exception(Craft::t('No product exists with the ID “{id}”',
                ['id' => $license->productId]));
        }

        $productType = $product->getProductType();

        if (!$productType) {
            throw new Exception(Craft::t('No product type exists with the ID “{id}”',
                ['id' => $product->typeId]));
        }

        if (!$record->id)
        {
            // TODO should we check if this will not clash with the index?
            $record->licenseKey = DigitalProductsHelper::generateLicenseKey($productType->licenseKeyAlphabet, $productType->licenseKeyLength);
        }


        $record->validate();
        $license->addErrors($record->getErrors());

        if ($license->hasErrors()) {
            return false;
        }

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $success = craft()->elements->saveElement($license, false);

            if (!$success) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                return false;
            }

            $record->id = $license->id;
            $record->save(false);

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
    }

    /**
     * Sort trough the ordered items and generate licenses for digital products.
     *
     * @param Event $event
     */
    public static function handleCompletedOrder(Event $event)
    {

        if (empty($event->params['order']))
        {
            return;
        }

        /**
         * @var Commerce_OrderModel $order
         */
        $order = $event->params['order'];
        $lineItems = $order->getLineItems();

        /**
         * @var Commerce_LineItemModel $lineItem
         */
        foreach ($lineItems as $lineItem)
        {
            $itemId = $lineItem->purchasableId;
            $element = craft()->elements->getElementById($itemId);
            $quantity = $lineItem->qty;

            if ($element->getElementType() == "DigitalProducts_Product")
            {
                /**
                 * @var DigitalProducts_ProductModel $element
                 */
                for ($i = 0; $i < $quantity; $i++)
                {
                    craft()->digitalProducts_licenses->licenseProductByOrder($element, $order);
                }
            }
        }
    }

    public static function handleUserActivation(Event $event)
    {
        if (empty($event->params['user']))
        {
            return;
        }

        /**
         * @var UserModel $user
         */
        $user = $event->params['user'];
        $email = $user->email;

        $licenses = craft()->digitalProducts_licenses->getLicenses(array('licenseeEmail' => $email));

        /**
         * @var DigitalProducts_LicenseModel $license
         */
        foreach ($licenses as $license)
        {
            // Only licenses with unassigned users
            if (!$license->userId)
            {
                $license->userId = $user->id;
                craft()->digitalProducts_licenses->saveLicense($license);
            }
        }
    }

    /**
     * Generate a license for a Product per Order.
     *
     * @param DigitalProducts_ProductModel $product
     * @param Commerce_OrderModel          $order
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function licenseProductByOrder(DigitalProducts_ProductModel $product, Commerce_OrderModel $order)
    {
        $license = new DigitalProducts_LicenseModel();
        $license->productId = $product->id;
        $customer = $order->getCustomer();

        if ($customer && $user = $customer->getUser())
        {
            $license->licenseeEmail = $user->email;
            $license->licenseeName = $user->getName();
            $license->userId = $user->id;
        }
        else
        {
            $license->licenseeEmail = $customer->email;
        }
        
        $license->enabled = 1;
        $license->orderId = $order->id;

        return $this->saveLicense($license);
    }
}
