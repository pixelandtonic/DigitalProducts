# Digital Products plugin for Craft Commerce.

This plugin makes it possible to sell licenses for digital products with [Craft Commerce](http://craftcommerce.com).

## Requirements

Digital Products requires Craft CMS 2.6 or later and Craft Commerce 1.1 or later.

## Installation

To install Digital Products, copy the digitalproducts/ folder into craft/plugins/, and then go to Settings → Plugins and click the “Install” button next to “Digital Products”.

## Configuration

Digital Products gets its own configuration file, located at `craft/config/digitalproducts.php`. It can have the following config settings:

- **autoAssignLicensesOnUserRegistration** _(boolean)_ – Whether licenses should be automatically assigned to newly-registered users if the emails match. (Default is `true`.)
- **autoAssignUserOnPurchase** _(boolean)_ – Whether license should automatically be assigned to existing users if the emails match. (Default is `false`.)
- **licenseKeyCharacters** (string) – The available characters that can be used in license key generation. (Default is all alphanumeric ASCII characters.)
- **licenseKeyLength** _(integer)_ – The length of generated license keys. (Default is `24`.)
- **requireLoggedInUser** _(boolean)_ – Whether a user *must* be logged in when completing an order with at least one digital product in the cart. (Default is `false`.)

## Plugin Hooks

Digital Products offers a few hooks that enable other plugins to modify its behavior:

- **digitalProducts_modifyLicenseKey** – Gives plugins a chance to modify a license key when it’s getting generated.

- **digitalProducts_modifyProductSources** – Gives plugins a chance to modify
the sources on digital product indexes.
- **digitalProducts_defineAdditionalProductTableAttributes** – Gives plugins a chance to add additional available table attributes to digital product indexes.
- **digitalProducts_getProductTableAttributeHtml** – Gives plugins a chance to override the HTML of the table cells on digital product indexes.
- **digitalProducts_modifyProductSortableAttributes** – Gives plugins a chance to modify the array of sortable attributes on digital product indexes.

- **digitalProducts_modifyLicenseSources** – Gives plugins a chance to modify
the sources on license indexes.
- **digitalProducts_defineAdditionalLicenseTableAttributes** – Gives plugins a chance to add additional available table attributes to license indexes.
- **digitalProducts_getLicenseTableAttributeHtml** – Gives plugins a chance to override the HTML of the table cells on license indexes.
- **digitalProducts_modifyLicenseSortableAttributes** – Gives plugins a chance to modify the array of sortable attributes on license indexes.

## Events

Digital Products offers a few events that other plugins can listen to:

- **digitalProducts_products.onBeforeSaveDigitalProduct** – Raised right before a digital product is saved. Passed with params `product` (the digital product) and `isNewProduct` (whether it’s new). Event handlers can prevent the digital product from being saved by setting `$event->performAction = false`.
- **digitalProducts_products.onSaveDigitalProduct** – Raised after a digital product is saved. Passed with the param `product` (the digital product).
- **digitalProducts_products.onBeforeDeleteDigitalProduct** – Raised right before a digital product is deleted. Passed with the param `product` (the digital product). Event handlers can prevent the digital product from being saved by setting `$event->performAction = false`.
- **digitalProducts_products.onDeleteDigitalProduct** – Raised after a digital product is deleted. Passed with the param `product` (the digital product).
- **digitalProducts_licenses.onBeforeSaveLicense** – Raised right before a license is being saved. Passed with params `license` (the license) and `isNewLicense` (whether it’s new). Event handlers can prevent the license from being saved by setting `$event->performAction = false`.
- **onSaveLicense** – Raised after a license is saved. Passed with the param `license` (the license).
