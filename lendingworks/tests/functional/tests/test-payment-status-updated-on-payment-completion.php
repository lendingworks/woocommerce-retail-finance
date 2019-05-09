<?php

namespace WC_Lending_Works\Tests;

use WC_Lending_Works\Lib\Webhook\Webhook;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter;
use WC_Lending_Works\Lib\Framework\Woocommerce_Adapter_Legacy;
use WP_UnitTestCase;

class PaymentStatusUpdatedOnPaymentCompletion extends WP_UnitTestCase
{
    private $order = null;

    public function setUp()
    {
        global $woocommerce;
        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $product = new \WC_Product();

            $product->set_name('foo');
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_description('Product Description');
            $product->set_sku('product-sku' . time() . rand(100, 999));
            $product->set_price(9.99);
            $product->set_regular_price(9.99);
            $product->set_manage_stock(true);
            $product->set_stock_quantity(10);
            $product->set_stock_status('instock');
            $product->save();

            $this->order = \wc_create_order();
            $this->order->add_product($product);
            $this->order->set_address(['first_name' => 'John', 'last_name' => 'Doe'], 'billing' );
            $this->order->calculate_totals();
            $this->order->update_status("Completed", 'Imported order', true);
            $this->order->save();

            $this->woocommerce = new Woocommerce_Adapter();
        } else {
            $post = $this->factory->post->create([
                'post_content' => '<p>Product Description</p>',
                'post_title' => 'Bar',
                'post_status' => 'publish',
                'post_type' => 'product',
            ]);

            $product = new \WC_Product($post);

            $this->order = wc_create_order();
            $this->order->add_product($product);

            $this->woocommerce = new Woocommerce_Adapter_Legacy();

            // Disable emails.
            remove_action('woocommerce_order_status_pending_to_processing', [\WC_Emails::class, 'send_transactional_email']);
            remove_action('woocommerce_order_status_pending_to_failed', [\WC_Emails::class, 'send_transactional_email']);
        }

        // Prevent actual redirection
        add_filter('wp_redirect', function() {
            return false;
        });
    }

    public function tearDown()
    {
        $order_id = method_exists($this->order, 'get_id') ? $this->order->get_id() : $this->order->id;

        if ($this->order !== null) {
            wp_delete_post($order_id, true);
        }
    }

    public function test_updating_order_after_approved_loan_application()
    {
        $webhook = new Webhook($this->woocommerce);

        $order_id = method_exists($this->order, 'get_id') ? $this->order->get_id() : $this->order->id;

        $_POST = [
            'order_id' => $order_id,
            'reference' => 'SMPL123456789',
            'status' => 'approved',
            'nonce' => $this->woocommerce->encrypt($this->woocommerce->get_order_meta($this->order, 'lendingworks_order_token'))
        ];

        $webhook->process();

        $this->order = wc_get_order($order_id);

        $this->assertEquals('approved', $this->woocommerce->get_order_meta($this->order, 'lendingworks_order_status'));
        $this->assertEquals('processing', $this->order->get_status());

        global $woocommerce;
        if (version_compare($woocommerce->version, '3.0', '>=')) {
            foreach ($this->order->get_items() as $item) {
                $this->assertEquals($item->get_meta('_reduced_stock'), 1);
            }
        }
    }

    public function test_updating_order_after_cancelled_or_expired_loan_application()
    {
        $webhook = new Webhook($this->woocommerce);

        $order_id = method_exists($this->order, 'get_id') ? $this->order->get_id() : $this->order->id;

        $_POST = [
            'order_id' => $order_id,
            'reference' => 'SMPL123456789',
            'status' => 'cancelled',
            'nonce' => $this->woocommerce->encrypt($this->woocommerce->get_order_meta($this->order, 'lendingworks_order_token'))
        ];

        $webhook->process();

        $this->order = wc_get_order($order_id);

        $this->assertEquals('cancelled', $this->woocommerce->get_order_meta($this->order, 'lendingworks_order_status'));
        $this->assertEquals('pending', $this->order->get_status());

        $notices = WC()->session->get('wc_notices')['error'];
        $this->assertContains('Your Loan quote was cancelled or expired.', $notices);
    }

    public function test_updating_order_after_declined_loan_application()
    {
        $webhook = new Webhook($this->woocommerce);

        $order_id = method_exists($this->order, 'get_id') ? $this->order->get_id() : $this->order->id;

        $_POST = [
            'order_id' => $order_id,
            'reference' => 'SMPL123456789',
            'status' => 'declined',
            'nonce' => $this->woocommerce->encrypt($this->woocommerce->get_order_meta($this->order, 'lendingworks_order_token'))
        ];

        $webhook->process();

        $this->order = wc_get_order($order_id);

        $this->assertEquals('declined', $this->woocommerce->get_order_meta($this->order, 'lendingworks_order_status'));
        $this->assertEquals('failed', $this->order->get_status());

        $notices = WC()->session->get('wc_notices')['error'];
        $this->assertContains('Please use an alternative payment method.', $notices);
    }
}
