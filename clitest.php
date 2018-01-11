<?php

require_once __DIR__.'/vendor/autoload.php';

use ThirtyBeesMollie\Omnipay\Omnipay;

// Setup payment gateway
$gateway = Omnipay::create('Mollie');
$gateway->setApiKey('test_4RHxUvezu238WHrTVzaxMTH8vV35xq');


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
