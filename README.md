# WooCommerce module

## Requirements

This repository requires the following:

*  [Dinghy](https://github.com/codekitchen/dinghy) or Docker.
*  An API key (contact partnerships@lendingworks.co.uk for more information on this)

## Setup

Clone this repository and change directory to it.
```bash
git clone git@bitbucket.org:lendingworks/rf-wordpress-woocommerce.git
cd rf-wordpress-woocommerce
```

At this stage, you may change if needed the PHP version that will run Wordpress by editing the docker-compose.yml file.
In the `wordpress` service, under `build` > `args` key, find the argument PHPVERSION and set the version you wish to use.
You can find all available build tags on [PHP dockerhub page](https://hub.docker.com/_/php?tab=tags).
Note that `fpm` variant must be used as Nginx is used in this module. The current PHP default tag is 5.6-fpm. This plugin has
been tested with PHP version 5.6.
```yaml
  wordpress:
    build:
      context: .
      args:
        - PHPVERSION=5.6-fpm
```

Finally, run docker compose to start the container followed by composer task `post-build`.
```bash
docker-compose up

composer build-and-test
```

You can now browse http://woocommerce.docker:8080/ to complete the wordpress installation.

Alternatively, you can preset WordPress and seed the database with dummy data with the following commands:
```bash
composer seed

composer post-build
```

This will populate the database with an admin user `lendingworks`. You can find the password
in `LW - Technology - Infrastructure - Tier 2` vault in 1password.

## Tests

There are two test suites:
- unit tests
- functional tests

You will need to first create a test environment in order to and run the test suites against it:
```bash
composer setup-tests
```

To run the unit tests suite:
```bash
composer unit-test
```

To run the functional tests suite
```bash
composer functional-test
```

To run all tests suites (make sure composer setup-tests was run prior to running this command):
```bash
composer test
```

__*After running the functional tests, you will need to restore the development environment to be able
to use the shop again.*__
```bash
composer post-build
composer seed
```

## Developping

### Download Wordpress and WooCommerce

Download your desired [Wordpress version](https://en-gb.wordpress.org/download/releases/) and extract the archive content in the root of this repository.

```bash
tar -C . -xzf /path/to/your/downloaded/wordpress-version.tar.gz
```

Download your desired [WooCommerce version](https://github.com/woocommerce/woocommerce/releases) and extract the archive content in the following directory, relative to this repository.
```bash
unzip -qq /path/to/your/downloaded/woocommerce-version.zip -d ./wordpress/wp-content/plugins
```

At this stage, you can either run the command below to complete the setup, or follow the rest of this documentation.
```bash
composer build-and-test
```

### Configure Wordpress

Update wordpress configuration if required in `wp-config.php` at the root of this directory.

If you need to update the authentication unique keys and salts, use the link below that will generate
[https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/)

### Complete Wordpress installation

You can now access Wordpress at [http://woocommerce.docker:8080/](http://woocommerce.docker:8080/):

*  Follow the installation wizard to set the language and admin user.
*  Login to the admin backend, click on `plugins` menu item in the side bar on the left, and click `activate` under WooCommerce related plugins. You'll need to enable at least one payment method to be able to add products to the cart.
*  Click on `Run the setup wizard`  button showing in the Wordpress dashboard, setup the store details and activate the plugin. You don't need the recommended extensions.
*  Then click on `Products` and `Add New` to create a first product in the store catalogue.
    *  Fill the form, and set a price to be able to add the product to cart
    *  In `Inventory` submenu, set and SKU, check the checkbox 'Enable stock management at product level' and set the stock quantity.
*  Click on `Publish`
*  You can browse the WooCommerce shop at [http://woocommerce.docker:8080/shop/](http://woocommerce.docker:8080/shop/)
*  Theme `Storefront` is recommended.
