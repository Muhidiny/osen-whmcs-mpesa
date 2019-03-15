<?php
/**
 * WHMCS Lipa Na Mpesa
 *
 * This sample file demonstrates how a merchant gateway module supporting
 * 3D Secure Authentication, Captures and Refunds can be structured.
 *
 * If your merchant gateway does not support 3D Secure Authentication, you can
 * simply omit that function and the callback file from your own module.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "mpesa" and therefore all functions
 * begin "mpesa_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Require libraries needed for gateway module functions.
// require_once __DIR__ . '/../../init.php';
// $whmcs->load_function('gateway');
// $whmcs->load_function('invoice');

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function mpesa_MetaData()
{
    return array(
        'DisplayName' => 'Lipa Na Mpesa',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 *
 * @see https://developers.whmcs.com/payment-gateways/lnmiguration/
 *
 * @return array
 */
function mpesa_config()
{

    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName'              => array(
            'Type'                  => 'System',
            'Value'                 => 'Lipa Na MPesa',
        ),

        // Environment
        'lnmEnv'                   => array(
            'FriendlyName'          => 'Environment',
            'Type'                  => 'dropdown',
            'Options'               => array(
                'sandbox'           => 'Sandbox',
                'live'              => 'Live',
            ),
            'Description'           => 'Select Environment',
        ),
        // Business Shortcode
        'lnmHeadOffice'             => array(
            'FriendlyName'          => 'HeadOffice/Store Number',
            'Type'                  => 'text',
            'Size'                  => '25',
            'Default'               => '174379',
            'Description'           => 'Enter your business Shortcode, or store number',
        ),
        // Business Shortcode
        'lnmShortCode'             => array(
            'FriendlyName'          => 'Shortcode',
            'Type'                  => 'text',
            'Size'                  => '25',
            'Default'               => '174379',
            'Description'           => 'Enter your business Shortcode - phone/till/paybill number',
        ),
        //
        'lnmAccount'               => array(
            'FriendlyName'          => 'Account Name',
            'Type'                  => 'text',
            'Size'                  => '25',
            'Default'               => 'OSEN',
            'Description'           => 'Account name to show in STK Push',
        ),
        'lnmType'                  => array(
            'FriendlyName'          => 'Identifier Type',
            'Type'                  => 'dropdown',
            'Options'               => array(
                1                   => 'MSISDN',
                2                   => 'Till Number',
                4                   => 'Paybill',
            ),
            'Description'           => 'Select Type',
        ),
        //
        'lnmAppKey'                => array(
            'FriendlyName'          => 'Daraja App Consumer Key',
            'Type'                  => 'text',
            'Size'                  => '25',
            'Default'               => 'wA833XUmZIc0qSgQjHVl8tinEr9JJvaF',
            'Description'           => 'Enter consumer key here',
        ),
        //
        'lnmAppSecret'             => array(
            'FriendlyName'          => 'Daraja App Consumer Secret',
            'Type'                  => 'text',
            'Size'                  => '25',
            'Default'               => 'nN77Me69VEAh1VmE',
            'Description'           => 'Enter consumer secret here',
        ),
        //
        'lnmOnlinePassKey'         => array(
            'FriendlyName'          => 'Online Passkey',
            'Type'                  => 'textarea',
            'Rows'                  => '3',
            'Cols'                  => '60',
            'Description'           => 'Online Passkey',
            'Default'               => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'
        )
    );
}

/**
 * Capture payment.
 *
 * Called when a payment is to be processed and captured.
 *
 * The card cvv number will only be present for the initial card holder present
 * transactions. Automated recurring capture attempts will not provide it.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/merchant-gateway/
 *
 * @return array Transaction response status
 */
function mpesa_capture($params)
{
    // Gateway Configuration Parameters
    $lnmEnv                    = $params['lnmEnv'];
    $lnmShortCode              = $params['lnmShortCode'];
    $lnmAccount                = $params['lnmAccount'];
    $lnmType                   = $params['lnmType'];
    $lnmPortalUsername         = $params['lnmPortalUsername'];
    $lnmPortalUserpass         = $params['lnmPortalUserpass'];
    $lnmAppKey                 = $params['lnmAppKey'];
    $lnmAppSecret              = $params['lnmAppSecret'];
    $lnmOnlinePassKey          = $params['lnmOnlinePassKey'];

    $endpoint = ( $lnmEnv == 'live' ) ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $credentials = base64_encode( $lnmAppKey.':'.$lnmAppSecret );
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $endpoint );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Basic '.$credentials ) );
    curl_setopt( $curl, CURLOPT_HEADER, false );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    $curl_response = curl_exec( $curl );

    $data = json_decode( $curl_response );

    $token = isset( $data->access_token ) ? $data->access_token : '';

    // Invoice Parameters
    $amount = $params['amount'];

    // Client Parameters
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to capture payment and interpret result
    $phone      = str_replace( "+", "", $phone );
    $phone      = preg_replace( '/^0/', '254', $phone );

    $endpoint   = ( $lnmEnv == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    $timestamp  = date( 'YmdHis' );
    $password   = base64_encode( $lnmHeadOffice.$lnmOnlinePassKey.$timestamp );
    $curl       = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $endpoint );
    curl_setopt( 
        $curl, 
        CURLOPT_HTTPHEADER, 
        array( 
          'Content-Type:application/json',
          'Authorization:Bearer '.$token 
        ) 
    );

    $curl_post_data = array( 
        'BusinessShortCode'   => $lnmHeadOffice,
        'Password'            => $password,
        'Timestamp'           => $timestamp,
        'TransactionType'     => ( $lnmType == 4 ) ? 'CustomerPayBillOnline' : 'BuyGoodsOnline',
        'Amount'              => round( $amount ),
        'PartyA'              => $phone,
        'PartyB'              => $lnmShortCode,
        'PhoneNumber'         => $phone,
        'CallBackURL'         => $systemUrl.'/modules/gateways/callback/' . $moduleName . '.php?lnm_action=reconcile',
        'AccountReference'    => $invoiceid,
        'TransactionDesc'     => 'Payment For '.$invoiceid,
        'Remark'              => 'Payment Via MPesa'
    );

    $data_string = json_encode( $curl_post_data );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
    curl_setopt( $curl, CURLOPT_HEADER, false );

    $response = curl_exec( $curl );
    $payment = curl_exec( $curl ) ? json_decode( $response, true ) : array( 'errorCode' => 1, 'errorMessage' => 'Could not connect to Daraja' );
    $transactionId = $payment['MerchantRequestID'];

    if ( isset( $payment['errorCode'] ) ) {
        return array(
            // 'success' if successful, otherwise 'declined', 'error' for failure
            'status' => 'error',
            // Data to be recorded in the gateway log - can be a string or array
            'rawdata' => $payment,
            // Unique Transaction ID for the capture transaction
            'transid' => $transactionId,
            // Optional fee amount for the fee value refunded
            'fee' => $amount,
        );
    } else {
        return array(
            // 'success' if successful, otherwise 'declined', 'error' for failure
            'status' => 'success',
            // Data to be recorded in the gateway log - can be a string or array
            'rawdata' => $payment,
            // Unique Transaction ID for the capture transaction
            'transid' => $transactionId,
            // Optional fee amount for the fee value refunded
            'fee' => $amount,
        );
    }
}


/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
// function mpesa_refund($params)
// {
//     // Gateway Configuration Parameters
//     $accountId = $params['accountID'];
//     $secretKey = $params['secretKey'];
//     $testMode = $params['testMode'];
//     $dropdownField = $params['dropdownField'];
//     $radioField = $params['radioField'];
//     $textareaField = $params['textareaField'];

//     // Transaction Parameters
//     $transactionIdToRefund = $params['transid'];
//     $refundAmount = $params['amount'];
//     $currencyCode = $params['currency'];

//     // Client Parameters
//     $firstname = $params['clientdetails']['firstname'];
//     $lastname = $params['clientdetails']['lastname'];
//     $email = $params['clientdetails']['email'];
//     $address1 = $params['clientdetails']['address1'];
//     $address2 = $params['clientdetails']['address2'];
//     $city = $params['clientdetails']['city'];
//     $state = $params['clientdetails']['state'];
//     $postcode = $params['clientdetails']['postcode'];
//     $country = $params['clientdetails']['country'];
//     $phone = $params['clientdetails']['phonenumber'];

//     // System Parameters
//     $companyName = $params['companyname'];
//     $systemUrl = $params['systemurl'];
//     $langPayNow = $params['langpaynow'];
//     $moduleDisplayName = $params['name'];
//     $moduleName = $params['paymentmethod'];
//     $whmcsVersion = $params['whmcsVersion'];

//     // perform API call to initiate refund and interpret result

//     return array(
//         // 'success' if successful, otherwise 'declined', 'error' for failure
//         'status' => 'success',
//         // Data to be recorded in the gateway log - can be a string or array
//         'rawdata' => $responseData,
//         // Unique Transaction ID for the refund transaction
//         'transid' => $refundTransactionId,
//         // Optional fee amount for the fee value refunded
//         'fee' => $feeAmount,
//     );
// }