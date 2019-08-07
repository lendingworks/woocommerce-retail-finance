<?php
/**
 * Webhook
 *
 * Class handling webhooks concerns of the payment gateway. When a customer completes a Lending Works loan application,
 * the details and status of this applications are reflected on the WooCommerce order to inform the store owner of the
 * payment status.
 *
 * @package WordPress
 * @subpackage WooCommerce
 * @version 1.0.0
 * @author  Lending Works Ltd
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
 * @link https://www.lendingworks.co.uk/
 * @since  1.0.0
 */

namespace WC_Lending_Works\Lib\Webhook;

use WC_Lending_Works\Lib\LW_Framework;
use WC_Lending_Works\Lib\Pay\Pay;
use WC_Lending_Works\Lib\Payment_Gateway;

/**
 * Webhook
 *
 * Class handling webhooks concerns of the payment gateway. When a customer completes a Lending Works loan application,
 * the details and status of this applications are reflected on the WooCommerce order to inform the store owner of the
 * payment status.
 */
class Webhook {
	/**
	 * The WooCommerce adapter providing access to framework static methods.
	 *
	 * @var LW_Framework
	 */
	private $woocommerce;

	/**
	 * Webhook constructor.
	 *
	 * @param LW_Framework $woocommerce The woocommerce adapter.
	 */
	public function __construct( LW_Framework $woocommerce ) {
		$this->woocommerce = $woocommerce;
	}

	/**
	 * Processes the inbound Lending Works http request to update the payment and order when loan application is completed.
	 *
	 * @return array
	 */
	public function process() {
        // phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_POST['order_id'], $_POST['reference'], $_POST['status'], $_POST['nonce'] ) ) {
			$input = $this->woocommerce->unslash( $_POST );
            // phpcs:enable WordPress.Security.NonceVerification
			$order_id               = $this->woocommerce->sanitize( $input['order_id'] );
			$loan_request_reference = $this->woocommerce->sanitize( $input['reference'] );
			$status                 = $this->woocommerce->sanitize( $input['status'] );
			$nonce                  = $this->woocommerce->sanitize( $input['nonce'] );
		} else {
			$this->woocommerce->notify( 'There was a problem with your loan application. Please try again.', 'error' );
			$this->woocommerce->redirect( $this->woocommerce->checkout_url() );
			return [];
		}

		$order = $this->woocommerce->get_order( $order_id );

		$order_token = $this->woocommerce->get_order_meta( $order, Payment_Gateway::ORDER_TOKEN_METADATA_KEY );

		if ( ! $this->woocommerce->authenticate( $nonce, $order_token ) ) {
			return [
				'result'   => 'failure',
				'redirect' => $this->woocommerce->checkout_url(),
			];
		}

		// Update lendingworks_status of the order to what was reported by RF module.
		if ( $this->is_valid( $status ) ) {
			$this->woocommerce->update_order_meta( $order, Payment_Gateway::ORDER_STATUS_METADATA_KEY, $status );

			if ( ! empty( $loan_request_reference ) ) {
				$this->woocommerce->update_order_meta( $order, Payment_Gateway::ORDER_REFERENCE_METADATA_KEY, $loan_request_reference );
			}

			switch ( true ) {
				case $this->is_accepted( $status ):
					$order->payment_complete();
					return $this->woocommerce->redirect( $order->get_checkout_order_received_url() );
				case $this->is_cancelled( $status ):
					$order->update_status( 'pending', __( 'Loan cancelled or expired', 'lendingworks' ) );
					$this->woocommerce->notify( __( 'Your Loan quote was cancelled or expired.' ), 'error' );
					break;
				case $this->is_declined( $status ):
					$order->update_status( 'failed', __( 'Loan declined', 'lendingworks' ) );
					$this->woocommerce->notify( __( 'Please use an alternative payment method.' ), 'error' );
					break;
			}
		} else {
			$this->woocommerce->notify( __( 'Status invalid' ), 'error' );
		}

		$this->woocommerce->redirect( $this->woocommerce->checkout_url() );
	}

	/**
	 * Checks whether the loan application is successful and in status approved, accepted or referred.
	 *
	 * @param string $status The loan application status.
	 *
	 * @return bool
	 */
	private function is_accepted( $status ) {
		return in_array( $status, [ Pay::STATUS_ACCEPTED, Pay::STATUS_APPROVED, Pay::STATUS_REFERRED ], true );
	}

	/**
	 * Checks whether the loan application is cancelled and in status cancelled or expired.
	 *
	 * @param string $status The loan application status.
	 *
	 * @return bool
	 */
	private function is_cancelled( $status ) {
		return in_array( $status, [ Pay::STATUS_CANCELLED, Pay::STATUS_EXPIRED ], true );
	}

	/**
	 * Checks whether the loan application is in status declined.
	 *
	 * @param string $status The loan application status.
	 *
	 * @return bool
	 */
	private function is_declined( $status ) {
		return Pay::STATUS_DECLINED === $status;
	}

	/**
	 * Checks whether the loan application is in a valid status.
	 *
	 * @param string $status The loan application status.
	 *
	 * @return bool
	 */
	private function is_valid( $status ) {
		return $this->is_accepted( $status ) || $this->is_cancelled( $status ) || $this->is_declined( $status );
	}
}
