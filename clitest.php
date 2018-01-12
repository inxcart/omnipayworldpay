<?php

require_once __DIR__.'/vendor/autoload.php';

use ThirtyBeesMollie\Omnipay\Omnipay;

// Setup payment gateway
/** @var \ThirtyBeesMollie\Omnipay\Mollie\Gateway $gateway */
$gateway = Omnipay::create('Mollie');
$gateway->setApiKey('test_4RHxUvezu238WHrTVzaxMTH8vV35xq');

try {
    $response = $gateway->fetchPaymentMethods(['locale' => 'nl_NL'])->send();
    var_dump($response->getPaymentMethods());
    exit;
} catch (Exception $e) {
    exit;
}

// Send purchase request
try {
    $response = $gateway->purchase(
        [
            'amount'      => '10.00',
            'currency'    => 'EUR',
            'description' => 'test',
            'returnUrl'   => 'https://test.mijnpresta.nl/',
        ]
    )->send();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Process response
if ($response->isSuccessful()) {

    // Payment was successful
    print_r($response);

} elseif ($response->isRedirect()) {

    // Redirect to offsite payment gateway
    $response->redirect();

} else {

    // Payment failed
    echo $response->getMessage();
}
