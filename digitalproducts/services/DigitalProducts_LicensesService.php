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
    // Public Methods
    // =========================================================================

    /**
     * Get a License by it's ID.
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
     * Get Licenses by criteria.
     *
     * @param array|ElementCriteriaModel $criteria
     *
     * @return DigitalProducts_LicenseModel[]
     */
    public function getLicenses($criteria = [])
    {
        if (!$criteria instanceof ElementCriteriaModel) {
            $criteria = craft()->elements->getCriteria('DigitalProducts_License', $criteria);
        }

        return $criteria->find();
    }

    /**
     * Save a License.
     *
     * @param DigitalProducts_LicenseModel $license
     *
     * @return bool
     * @throws Exception in case of invalid data.
     * @throws \Exception if saving of the Element failed causing a failed transaction
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

        $record->ownerName = $license->ownerName;
        $record->ownerEmail = $license->ownerEmail;

        if (empty($license->productId)) {
            $license->addError('productId', Craft::t('{attribute} cannot be blank.', ['attribute' => 'Product']));
        }

        if (empty($license->userId) && empty($license->ownerEmail)) {
            $license->addError('userId', Craft::t('A license must have either an email or an owner assigned to it.'));
            $license->addError('ownerEmail', Craft::t('A license must have either an email or an owner assigned to it.'));
        }

        // Assign license to a User if the email matches the User and User field left empty.
        if (
            (craft()->config->get('autoAssignUserOnPurchase', 'digitalProducts'))
            && empty($license->userId) && !empty($license->ownerEmail) && $user = craft()->users->getUserByEmail($license->ownerEmail)
        ) {
            $license->userId = $user->id;
        }

        // See if we already have issues with provided data.
        if ($license->hasErrors()) {
            return false;
        }

        // If a owner is set, void the name and email fields.
        if ($license->userId) {
            $record->ownerName = null;
            $record->ownerEmail = null;
        }

        $record->userId = $license->userId;

        if (!$record->id) {
            do {
                $licenseKey = $this->generateLicenseKey();
                $conflict = DigitalProducts_LicenseRecord::model()->findAllByAttributes(['licenseKey' => $licenseKey]);
            } while ($conflict);

            $modifiedLicenseKey = craft()->plugins->callFirst('digitalProducts_modifyLicenseKey', [
                $licenseKey,
                $license
            ], true);

            // Use the plugin-modified name, if anyone was up to the task.
            $licenseKey = $modifiedLicenseKey ?: $licenseKey;

            $record->licenseKey = $licenseKey;

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

            $record->productId = $license->productId;
        } else if ($record->productId != $license->productId) {
            $license->addError('productId', Craft::t('The licensed product cannot be changed once a license has been created.'));
        }

        $record->validate();
        $license->addErrors($record->getErrors());

        if ($license->hasErrors()) {
            return false;
        }

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $event = new Event($this, [
                'license' => $license,
                'isNewLicense' => !$license->id
            ]);
            $this->onBeforeSaveLicense($event);

            $success = false;

            if ($event->performAction) {
                $success = craft()->elements->saveElement($license, false);
            }

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

            $event = new Event($this, ['license' => $license]);
            $this->onSaveLicense($event);
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return true;
    }

    /**
     * Sort trough the ordered items and generate Licenses for Digital Products.
     *
     * @param Event $event
     */
    public static function handleCompletedOrder(Event $event)
    {
        if (empty($event->params['order'])) {
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
        foreach ($lineItems as $lineItem) {
            $itemId = $lineItem->purchasableId;
            $element = craft()->elements->getElementById($itemId);
            $quantity = $lineItem->qty;

            if ($element->getElementType() == "DigitalProducts_Product") {
                /**
                 * @var DigitalProducts_ProductModel $element
                 */
                for ($i = 0; $i < $quantity; $i++) {
                    craft()->digitalProducts_licenses->licenseProductByOrder($element, $order);
                }
            }
        }
    }

    /**
     * Prevent the Order from taking place if the user is not logged in but the
     * config requires it and the Order contains Digital Products.
     *
     * @param Event $event
     */
    public static function maybePreventPayment(Event $event)
    {
        if (!(craft()->config->get('requireLoggedInUser', 'digitalProducts') && craft()->userSession->isGuest())) {
            return;
        }

        if (empty($event->params['transaction'])) {
            return;
        }

        /**
         * @var Commerce_OrderModel $order
         * @var Commerce_TransactionModel $transaction
         */
        $transaction = $event->params['transaction'];
        $order = $transaction->order;

        if (!$order) {
            return;
        }

        $lineItems = $order->getLineItems();

        /**
         * @var Commerce_LineItemModel $lineItem
         */
        foreach ($lineItems as $lineItem) {
            $itemId = $lineItem->purchasableId;
            $element = craft()->elements->getElementById($itemId);

            if ($element->getElementType() == "DigitalProducts_Product") {
                $transaction->message = Craft::t("You must be logged in to complete this transaction!");
                $event->performAction = false;

                return;
            }
        }
    }

    /**
     * If a user is activated and a license is assigned to the user's email,
     * assign it to the user if the config settings do not prevent that.
     *
     * @param Event $event
     */
    public static function handleUserActivation(Event $event)
    {
        if (empty($event->params['user'])) {
            return;
        }

        if (!craft()->config->get('autoAssignLicensesOnUserRegistration', 'digitalProducts')) {
            return;
        }

        /**
         * @var UserModel $user
         */
        $user = $event->params['user'];
        $email = $user->email;
        $licenses = craft()->digitalProducts_licenses->getLicenses(['ownerEmail' => $email]);

        /**
         * @var DigitalProducts_LicenseModel $license
         */
        foreach ($licenses as $license) {
            // Only licenses with unassigned users
            if (!$license->userId) {
                $license->userId = $user->id;
                craft()->digitalProducts_licenses->saveLicense($license);
            }
        }
    }

    /**
     * If a user is deleted, transfer the licenses.
     *
     * @param Event $event
     */
    public static function handleUserDeletion(Event $event)
    {
        if (empty($event->params['user'])) {
            return;
        }

        /**
         * @var UserModel $user
         */
        $user = $event->params['user'];
        $licenses = craft()->digitalProducts_licenses->getLicenses(['ownerId' => $user->id, 'productId' => ':all:']);

        /**
         * @var DigitalProducts_LicenseModel $license
         */
        foreach ($licenses as $license) {
            // Transfer the user's licenses to the user's email.
            $license->ownerEmail = $user->email;
            $license->userId = null;
            craft()->digitalProducts_licenses->saveLicense($license);
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

        if ($customer && $user = $customer->getUser()) {
            $license->ownerEmail = $user->email;
            $license->ownerName = $user->getName();
            $license->userId = $user->id;
        } else {
            $license->ownerEmail = $customer->email;
        }

        $success = $this->saveLicense($license);

        if ($success) {
            return (bool)craft()->db->createCommand()->update('digitalproducts_licenses', ['orderId' => $order->id], ['id' => $license->id]);
        }

        return false;
    }

    /**
     * Generate a license key.
     *
     * @return string
     */
    public function generateLicenseKey()
    {
        $codeAlphabet = craft()->config->get('licenseKeyCharacters', 'digitalProducts');
        $keyLength = craft()->config->get('licenseKeyLength', 'digitalProducts');

        $licenseKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $licenseKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $licenseKey;
    }

    /**
     * Delete a License.
     *
     * @param DigitalProducts_LicenseModel $license
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteLicense($license)
    {
        $event = new Event($this, ['license' => $license]);
        $this->onBeforeDeleteLicense($event);

        if ($event->performAction && craft()->elements->deleteElementById($license->id)) {
            $event = new Event($this, ['license' => $license]);
            $this->onDeleteLicense($event);
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
    public function onBeforeSaveLicense(Event $event)
    {
        $this->raiseEvent('onBeforeSaveLicense', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onSaveLicense(Event $event)
    {
        $this->raiseEvent('onSaveLicense', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onBeforeDeleteLicense(Event $event)
    {
        $this->raiseEvent('onBeforeDeleteLicense', $event);
    }

    /**
     * Event method
     *
     * @param Event $event
     *
     * @throws \CException
     */
    public function onDeleteLicense(Event $event)
    {
        $this->raiseEvent('onDeleteLicense', $event);
    }
}
