<?php
namespace Craft;

/**
 * Class DigitalProducts_LicensesController
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2016, Pixel & Tonic, Inc.
 */

class DigitalProducts_LicensesController extends BaseController
{

    /**
     * @inheritDoc BaseController::init()
     */
    public function init()
    {
        if (!craft()->userSession->checkPermission('digitalProducts-manageLicenses')) {
            throw new HttpException(403, Craft::t('You don\'t have permissions to do that.'));
        }
        parent::init();
    }

    // Public Methods
    // =========================================================================

    /**
     * Create or edit a License
     *
     * @param array $variables
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['license'])) {
            if (empty($variables['licenseId'])) {
                $license = new DigitalProducts_LicenseModel();
            } else {
                $license = craft()->digitalProducts_licenses->getLicenseById($variables['licenseId']);

                if (!$license) {
                    $license = new DigitalProducts_LicenseModel();;
                }
            }

            $variables['license'] = $license;
        }

        $variables['title'] = empty($variables['license']->id) ? Craft::t("Create a new License") : (string)$variables['license'];

        $variables['userElementType'] = craft()->elements->getElementType(ElementType::User);
        $variables['productElementType'] = craft()->elements->getElementType("DigitalProducts_Product");

        $this->renderTemplate('digitalproducts/licenses/_edit', $variables);
    }

    /**
     * Save a License.
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $licenseId = craft()->request->getPost('licenseId');

        if ($licenseId) {
            $license = craft()->digitalProducts_licenses->getLicenseById($licenseId);

            if (!$license) {
                throw new Exception(Craft::t('No license with the ID “{id}”',
                    ['id' => $licenseId]));
            }
        } else {
            $license = new DigitalProducts_LicenseModel();
        }

        $productIds = craft()->request->getPost('product');
        $userIds = craft()->request->getPost('owner');

        if (is_array($productIds) && !empty($productIds)) {
            $license->productId = reset($productIds);
        }

        if (is_array($userIds) && !empty($userIds)) {
            $license->userId = reset($userIds);
        }

        $license->id = craft()->request->getPost('licenseId');
        $license->enabled = (bool)craft()->request->getPost('enabled');
        $license->ownerName = craft()->request->getPost('ownerName');
        $license->ownerEmail = craft()->request->getPost('ownerEmail');

        // Save it
        if (craft()->digitalProducts_licenses->saveLicense($license)) {
            craft()->userSession->setNotice(Craft::t('License saved.'));
            $this->redirectToPostedUrl($license);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save license.'));
        }

        // Send the license back to the template
        craft()->urlManager->setRouteVariables([
            'license' => $license
        ]);
    }

    /**
     * Delete a License.
     */
    public function actionDelete()
    {
        $this->requirePostRequest();

        $id = craft()->request->getRequiredPost('licenseId');
        $license = craft()->digitalProducts_licenses->getLicenseById($id);
        if(!$license){
            throw new HttpException(404);
        }

        if (craft()->digitalProducts_licenses->deleteLicense($license)) {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => true]);
            } else {
                craft()->userSession->setNotice(Craft::t('License deleted.'));
                $this->redirectToPostedUrl($license);
            }
        } else {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => false]);
            } else {
                craft()->userSession->setError(Craft::t('Couldn’t delete license.'));
            }
        }
    }

}