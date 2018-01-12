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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use ThirtyBeesMollie\Omnipay\Omnipay;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class OmnipayMollieValidationModuleFrontController
 */
class OmnipayMollieValidationModuleFrontController extends ModuleFrontController
{
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;
    /** @var OmnipayMollie $module */
    public $module;

    /**
     * OmnipayMollieValidationModuleFrontController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * Post process
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        $cookie = new Cookie($this->module->name);
        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
            || strtoupper($this->context->currency->iso_code) !== 'EUR'
        ) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        $orderProcess = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $this->context->smarty->assign([
            'orderLink' => $this->context->link->getPageLink($orderProcess, true),
        ]);

        if ((float) $cart->getOrderTotal() !== (float) $cookie->amount) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        /** @var Omnipay $gateway */
        $gateway = Omnipay::create(OmnipayMollie::GATEWAY_NAME);
        foreach (array_keys($gateway->getDefaultParameters()) as $key) {
            $gateway->{'set'.ucfirst($key)}(Configuration::get(OmnipayMollie::CREDENTIAL.$key));
        }

        $params = Tools::getAllValues();
        $params['transactionReference'] = $cookie->transaction_reference;
        try {
            /** @var \ThirtyBeesMollie\Omnipay\Mollie\Message\CompletePurchaseResponse $response */
            $response = $gateway->completePurchase($params)->send();
            if (!$response->isSuccessful()) {
                $error = $this->module->l('An error occurred. Please contact us for more information.', 'validation');
                $this->errors[] = $error;
                $this->setTemplate('error.tpl');

                return;
            }
        } catch (Exception $e) {
            if (!empty(_PS_MODE_DEV_)) {
                $error = sprintf($this->module->l('An error occurred: %s', 'validation'), $e->getMessage());
            } else {
                $error = $this->module->l('An unknown error occurred. Please contact us for more information.', 'validation');
            }
            $this->errors[] = $error;
            $this->setTemplate('error.tpl');

            return;
        }

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
        } else {
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
