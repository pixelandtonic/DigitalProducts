# Digital Products plugin for Craft Commerce.

This plugin provides digital product and their license management to the [Craft Commerce](http://craftcommerce.com) plugin.

## Requirements

Digital Products requires Craft CMS 2.6 or later and Craft Commerce 1.1 or later.

## Installation

To install Digital Plugins, copy the digitalproducts/ folder into craft/plugins/, and then go to Settings → Plugins and click the “Install” button next to “Digital Products”.

## Configuration

Digital Products has several configuration options that allow you to customize how the plugin behaves. To change the values, add `digitalproducts.php` into your site's `craft/config/` folder.

* `autoAssignLicensesOnUserRegistration` - If a user should be assigned all licenses that belong to the user's email address when creating the user. Defaults to `true`.
* `autoAssignUserOnPurchase` - Should a user be assigned the license if a purchase is being made by a non-logged in user with the user's email address. Defaults to `false`.
* `licenseKeyAlphabet` - The alphabet used for license keys. Defaults to alphanumeric characters.
* `licenseKeyLength` - Length of the generated license keys. The default value is 128.
* `requireLoggedInUser` - If a user *must* be logged in when completing a commerce order with at least one digital product.

## Hooks

Digital Products offers a few hooks to modify it's behaviour.

* `digitalProducts_modifyLicenseKeyForLicense` - Gives plugins a chance to modify the license key when it's being generated.
* `digitalProducts_getProductTableAttributeHtml` - Gives plugins a chance to customize the HTML of the table cells on the digital product index page.
* `digitalProducts_getLicenseTableAttributeHtml` - Gives plugins a chance to customize the HTML of the table cells on the license index page.

## Events

Digital Products provides several events that alert other plugins.

* `onBeforeSaveDigitalProduct` - Raised right before a digital product is saved. Event handlers can prevent the digital product from being saved by `$event->performAction` to false.
* `onSaveDigitalProduct` - Raised when a digital product is saved.
* `onBeforeDeleteDigitalProduct` - Raised right before a digital product is deleted. Event handlers can prevent the digital product from being saved by `$event->performAction` to false.
* `onDeleteDigitalProduct` - Raised when a digital product is deleted.
* `onBeforeSaveLicense` - Raised when a license is being saved. Event handlers can prevent the license from being saved by `$event->performAction` to false.
* `onSaveLicense` - Raised when a license is saved.


## Changelog

### 1.0.0

* Initial release