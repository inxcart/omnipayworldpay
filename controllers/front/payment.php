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

use ThirtyBeesMollie\Omnipay\Omnipay;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class OmnipayMolliePaymentModuleFrontController
 */
class OmnipayMolliePaymentModuleFrontController extends ModuleFrontController
{
    /** @var OmnipayMollie $module */
    public $module;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!$this->module->active
            || strtoupper($this->context->currency->iso_code) !== 'EUR'
        ) {
            Tools::redirect('index.php?controller=order&step=3');
        }
        $cart = $this->context->cart;
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        parent::initContent();

        /** @var \ThirtyBeesMollie\Omnipay\Mollie\Gateway $gateway */
        $gateway = Omnipay::create(OmnipayMollie::GATEWAY_NAME);
        foreach (array_keys($gateway->getDefaultParameters()) as $key) {
            $gateway->{'set'.ucfirst($key)}(Configuration::get(OmnipayMollie::CREDENTIAL.$key));
        }
        /** @var \ThirtyBeesMollie\Omnipay\Mollie\Message\PurchaseResponse $response */
        $response = $gateway->purchase(
            [
                'amount'      => number_format($this->context->cart->getOrderTotal(), 2),
                'currency'    => strtoupper($this->context->currency->iso_code),
                'description' => $this->context->cart->id,
                'returnUrl'   => $this->context->link->getModuleLink($this->module->name, 'validation', [], true),
                'cancelUrl'   => $this->context->link->getPageLink('order', true, null, ['step' => 3]),
                'method'      => Tools::getValue('method') ?: null
            ]
        )->send();

        if ($response->isRedirect()) {
            $cookie = new Cookie($this->module->name);
            $cookie->amount = (float) $cart->getOrderTotal();
            $cookie->transaction_id = $response->getTransactionId();
            $cookie->transaction_reference = $response->getTransactionReference();
            $cookie->write();

            $response->redirect();
            exit;
        } else {
            if (_PS_MODE_DEV_) {
                $this->context->controller->errors[] = $response->getMessage();
            } else {
                $this->context->controller->errors[] = $this->l('An unknown error occurred.');
            }
        }
    }
}
