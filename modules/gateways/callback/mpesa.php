<?php

/**
 * WHMCS Merchant Gateway 3D Secure Callback File
 *
 * The purpose of this file is to demonstrate how to handle the return post
 * from a 3D Secure Authentication process.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * Users are expected to be redirected to this file as part of the 3D checkout
 * flow so it also demonstrates redirection to the invoice upon completion.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
$whmcs->load_function('gateway');
$whmcs->load_function('invoice');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
	die("Module Not Activated");
}

// Die if not accessed properly.
if (!isset($_GET['lnm_action'])) {
	return;
}

$response = json_decode(file_get_contents('php://input'), true);
if (!isset($response['Body'])) {
	return;
}

header("Access-Control-Allow-Origin: *");
header('Content-Type:Application/json');

switch ($_GET['lnm_action']) {
	case 'confirm':
		echo json_encode(
			array(
				'ResponseCode'      => 0,
				'ResponseDesc'      => 'Success',
				'ThirdPartyTransID' => 0,
			)
		);
		break;
	case 'validate':
		echo json_encode(
			array(
				'ResponseCode'      => 0,
				'ResponseDesc'      => 'Success',
				'ThirdPartyTransID' => 0,
			)
		);
		break;

	default:
		$resultCode        = $response['Body']['stkCallback']['ResultCode'];
		$resultDesc        = $response['Body']['stkCallback']['ResultDesc'];
		$merchantRequestID = $response['Body']['stkCallback']['MerchantRequestID'];
		$transactionId     = $response['Body']['stkCallback']['CheckoutRequestID'];

		if (isset($response['Body']['stkCallback']['CallbackMetadata'])) {
			$paymentAmount      = $response['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
			$mpesaReceiptNumber = $response['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
			$balance            = $response['Body']['stkCallback']['CallbackMetadata']['Item'][2]['Value'];
			$transactionDate    = $response['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
			$phone              = $response['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];
			$success            = true;
		} else {
			$success = false;
		}

		$invoiceId         = $merchantRequestID;
		$transactionStatus = $success ? 'Success' : 'Failure';

		// Retrieve data returned in payment gateway callback
		// Varies per payment gateway
		// $success = $_POST["x_status"];
		// $invoiceId = $_POST["x_invoice_id"];
		// $transactionId = $_POST["x_trans_id"];
		// $paymentAmount = $_POST["x_amount"];
		// $paymentFee = $_POST["x_fee"];
		// $hash = $_POST["x_hash"];

		// $transactionStatus = $success ? 'Success' : 'Failure';

		/**
		 * Validate callback authenticity.
		 *
		 * Most payment gateways provide a method of verifying that a callback
		 * originated from them. In the case of our example here, this is achieved by
		 * way of a shared secret which is used to build and compare a hash.
		 */
		// $secretKey = $gatewayParams['secretKey'];
		// if ($hash != md5($secretKey . $invoiceId . $transactionId . $paymentAmount)) {
		// 	$transactionStatus = 'Hash Verification Failure';
		// 	$success           = false;
		// }

		/**
		 * Validate Callback Invoice ID.
		 *
		 * Checks invoice ID is a valid invoice number. Note it will count an
		 * invoice in any status as valid.
		 *
		 * Performs a die upon encountering an invalid Invoice ID.
		 *
		 * Returns a normalised invoice ID.
		 *
		 * @param int $invoiceId Invoice ID
		 * @param string $gatewayName Gateway Name
		 */
		$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

		/**
		 * Check Callback Transaction ID.
		 *
		 * Performs a check for any existing transactions with the same given
		 * transaction number.
		 *
		 * Performs a die upon encountering a duplicate.
		 *
		 * @param string $transactionId Unique Transaction ID
		 */
		checkCbTransID($transactionId);

		/**
		 * Log Transaction.
		 *
		 * Add an entry to the Gateway Log for debugging purposes.
		 *
		 * The debug data can be a string or an array. In the case of an
		 * array it will be
		 *
		 * @param string $gatewayName        Display label
		 * @param string|array $debugData    Data to log
		 * @param string $transactionStatus  Status
		 */
		logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

		$paymentSuccess = false;

		if ($success) {

			/**
			 * Add Invoice Payment.
			 *
			 * Applies a payment transaction entry to the given invoice ID.
			 *
			 * @param int $invoiceId         Invoice ID
			 * @param string $transactionId  Transaction ID
			 * @param float $paymentAmount   Amount paid (defaults to full balance)
			 * @param float $paymentFee      Payment fee (optional)
			 * @param string $gatewayModule  Gateway module name
			 */
			addInvoicePayment(
				$invoiceId,
				$transactionId,
				$paymentAmount,
				$paymentFee,
				$gatewayModuleName
			);

			$paymentSuccess = true;
		}
		break;
}
