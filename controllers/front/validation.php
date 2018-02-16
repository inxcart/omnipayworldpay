<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use ThirtyBeesWorldpay\Omnipay\Omnipay;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class OmnipayWorldpayValidationModuleFrontController
 */
class OmnipayWorldpayValidationModuleFrontController extends ModuleFrontController
{
    /** @var OmnipayWorldpay $module */
    public $module;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!$this->module->active || strtoupper($this->context->currency->iso_code) !== 'EUR') {
            Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }
        $cart = $this->context->cart;
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink('order', null, null, ['step' => 3]));
        }

        parent::initContent();

        /** @var \ThirtyBeesWorldpay\Omnipay\Worldpay\Gateway $gateway */
        $gateway = Omnipay::create(OmnipayWorldpay::GATEWAY_NAME);
        foreach (array_keys($gateway->getDefaultParameters()) as $key) {
            $gateway->{'set'.ucfirst($key)}(Configuration::get(OmnipayWorldpay::CREDENTIAL.$key));
        }

        $billingAddress = new Address($cart->id_address_invoice);
        $shippingAddress = new Address($cart->id_address_delivery);

        $card = new \ThirtyBeesWorldpay\Omnipay\Common\CreditCard([
            'name'             => "{$customer->firstname} {$customer->lastname}",
            'email'            => $customer->email,
            'billingAddress1'  => $billingAddress->address1,
            'billingAddress2'  => $billingAddress->address2,
            'billingPostcode'  => $billingAddress->postcode,
            'billingCity'      => $billingAddress->city,
            'billingState'     => $billingAddress->id_state ? State::getNameById($billingAddress->id_state) : '',
            'billingCountry'   => Country::getIsoById($billingAddress->id_country),
            'billingPhone'     => $billingAddress->phone ?: $billingAddress->phone_mobile,
            'shippingAddress1' => $shippingAddress->address1,
            'shippingAddress2' => $shippingAddress->address2,
            'shippingPostcode' => $shippingAddress->postcode,
            'shippingCity'     => $shippingAddress->city,
            'shippingState'    => $shippingAddress->id_state ? State::getNameById($shippingAddress->id_state) : '',
            'shippingCountry'  => Country::getIsoById($shippingAddress->id_country),
            'shippingPhone'    => $shippingAddress->phone ?: $shippingAddress->phone_mobile,
        ]);

        /** @var \ThirtyBeesWorldpay\Omnipay\Worldpay\Message\PurchaseResponse $response */
        $response = $gateway->purchase(
            [
                'token'         => Tools::getValue('token'),
                'amount'        => number_format($this->context->cart->getOrderTotal(), 2),
                'currency'      => strtoupper($this->context->currency->iso_code),
                'description'   => $this->context->cart->id,
                'transactionId' => $this->context->cart->id,
                'name'          => 'test',
                'card'          => $card,

            ]
        )->send();

        if ($response->isSuccessful()) {
            $this->module->validateOrder(
                (int) $cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                (float) $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                [],
                (int) $cart->id_currency,
                false,
                $cart->secure_key
            );

            /**
             * If the order has been validated we try to retrieve it
             */
            $idOrder = Order::getOrderByCartId((int) $cart->id);

            if ($idOrder) {
                /**
                 * The order has been placed so we redirect the customer on the confirmation page.
                 */
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id
                    .'&id_module='.$this->module->id.'&id_order='.$idOrder.'&key='.$customer->secure_key);
            }
        } else {
            if (_PS_MODE_DEV_) {
                $this->context->controller->errors[] = $response->getMessage();
            } else {
                $this->context->controller->errors[] = $this->module->l('An unknown error occurred.', 'payment');
            }

            $this->context->smarty->assign([
                'orderLink' => $this->context->link->getPageLink('order', null, null, ['step' => 3]),
            ]);

            /**
             * An error occurred and is shown on a new page.
             */
            $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return;
        }

        return;
    }
}
