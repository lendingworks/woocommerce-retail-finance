# Lending Works Retail Finance Plugin for WooCommerce

This module will introduce a new payment method to your checkout that allows a user to apply for finance through the Lending Works portal.

## Requirements

- WooCommerce version 2.6 or greater.
- An API key (contact partnerships@lendingworks.co.uk for more information on this)
- A shop that operates in British Pounds

## Installation

You can either install via:
 - WordPress administrator interface. In the sidebar, click on `Plugins > add new` item. Search for `lending works` and click `Install Now` button.
 - [The WordPress plugin repository](https://en-gb.wordpress.org/plugins/lendingworks/). Click on download button, extract the archive and upload the `lendingworks` folder to your `wp-content/plugins` directory where WordPress is hosted.

You can then activate the plugin by clicking the `Activate` link in your plugins list.

## Configuration

The configuration for the payment method in the checkout can be found in the `WooCommerce > Settings > Payments` section. If the Lending Works plugin is installed and activated, it will appear in the available payment methods list.

Click the `Manage` button corresponding to `Lending Works Retail Finance`.

The options are as follow:

- **Enabled** - whether or not to display this option in the checkout for applicable orders
- **Title** - the title which the customer sees during checkout.
- **Api Key** - your API key provided by Lending Works.
- **Test mode ** - this allows to activate the test mode to integrate the payment method to your store. When active, you can send and receive dummy orders to try Lending Works plugin.
- **Allow manual fulfilment?** - if yes, then you will have the option to mark the order as fulfilled from the admin interface. Otherwise, the order will be automatically fulfilled when its status is updated to `completed`.
- **Minimum/Maximum Order Total** - optional parameters to influence whether or not Retail Finance can be offered on an item.

## Checkout Flow

When Lending Works Retail Finance is selected as a payment method, a call is made to the Lending Works API to create an order.

![Checkout](https://raw.githubusercontent.com/lendingworks/woocommerce-retail-finance/master/lendingworks/assets/screenshot-1.png)

When the `Checkout` button is clicked, the Lending Works loan application form will open within a modal dialog.

![iFrame](https://raw.githubusercontent.com/lendingworks/woocommerce-retail-finance/master/lendingworks/assets/screenshot-2.png)

Upon completion of the application, the next step will be determined depending on the result:

- **Approved** - the order will proceed as normal.
- **Declined** - the user will be prompted to choose an alternative payment method, and the ability to try again with Lending Works will be disabled.
- **Referred** - same as Approved. However, you may wish to wait to fulfill this order until the loan request has been fully approved.
- **Cancelled** - this occurs when a user exits the checkout before it is complete. The order will be abandoned but they may try again if they wish.

## Order Management

Four custom fields are added to an order paid with Lending Works:

1. lendingworks_order_token - the unique identifier for the order in the LendingWorks database.
2. lendingworks_order_status - the status of the loan request associated with the order.
3. lendingworks_order_loan_request_reference - the reference of the loan request associated with the order.
4. lendingworks_order_fulfilled - whether or not the order has been completed by the retailer.

Order statuses will be updated via a webhook call from the Lending Works API, this happens when the loan application is:

+ marked as ‘expired’
+ marked as ‘cancelled’
+ marked as ‘fulfilled’
+ you have a ‘referred’ loan application and it is
    * approved
    * declined
    * accepted

## Order fulfilment

Drilling down into the order via the `Orders` list will allow you to perform manual fulfilment actions. You can do this via the large button `Fulfill order` at the bottom of the `Order #123 details` panel.

![Order fulfillment](https://raw.githubusercontent.com/lendingworks/woocommerce-retail-finance/master/lendingworks/assets/screenshot-7.png)
