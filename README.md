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
- **digitalProducts_licenses.onSaveLicense** – Raised after a license is saved. Passed with the param `license` (the license).
- **digitalProducts_licenses.onBeforeDeleteLicense** - Raised when a license is being deleted. Passed with the param `license` (the license). Event handlers can prevent the license from being saved by setting `$event->performAction = false`.
- **digitalProducts_licenses.onDeleteLicense** - Raised when a license is deleted. Passed with the param `license` (the license).

## Eager loading

Both licenses and products have several eager-loadable properties

### Licenses

* `product` - Allows you to eager-load the product associated with the license.
* `order` - Allows you to eager-load the order associated with the license, if any.
* `owner` - Allows you to eager-load the Craft user that owns the license, if any.

### Products
* `isLicensed` - Eager-loads whether the product is licensed for the currently logged in Craft User.

## Examples

### Displaying the licensed product for the currently logged in Craft User.

```
    {% if currentUser %}
        {% set licenses = craft.digitalProducts.licenses.owner(currentUser).with(['products', 'order']) %}

        <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">Licenses</h3></div>
        {% if licenses %}
            <table class="table">
                <thead>
                    <tr>
                        <th>Licensed product</th>
                        <th>License date</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                {% for license in licenses %}
                    <tr>
                        <td><a href="{{ license.product.getUrl() }}">{{ license.product.title }}</a></td>
                        <td>{{ license.dateCreated|date('Y-m-d H:i:s') }}</td>
                        <td>
                            {% if license.orderId %}
                                <a href="/store/order?number={{ license.order.number }}">Order no. {{ license.orderId }}</a>
                            {% endif %}
                        </td>
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        {% endif %}
    {% else %}
        <p>Please log in first</p>
    {% endif %}
```

### Checking if currently logged in user is licensed to access a product.

```
    {% set products = craft.digitalProducts.products({type: 'onlineCourses'}).with(['isLicensed']) %}
    {% if products %}
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>License status</th>
                </tr>
            </thead>
            <tbody>
                {% for product in products %}
                    <tr>
                        <td>{{ product.title }}</td>
                        <td>
                            {% if product.isLicensed() %}
                                You already own this product.
                            {% else %}
                                <a href="{{ product.getUrl() }}">Get it now!</a>
                            {% endif %}
                        </td>
                    </tr>
                {% endfor %}
            </tbody>
        </table>
    {% endif %}
```

## Changelog

### 1.0.4

* Fixed a bug where digital product prices did not display correctly.

### 1.0.3

* Added support for a plugin release feed.

### 1.0.2

* Fixed bugs.

### 1.0.1

* Fixed bugs.

### 1.0.0

* Initial release
