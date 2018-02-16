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
 * to contact@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */

use ThirtyBeesWorldpay\Omnipay\Omnipay;

require_once __DIR__.'/vendor/autoload.php';

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class OmnipayWorldpay
 */
class OmnipayWorldpay extends PaymentModule
{
    const CREDENTIAL = 'OMNIPAY_WORLDPAY_CRED';
    const PAYMENT_METHODS = 'OMNIPAY_PAYMENT_METHODS';
    const GATEWAY_NAME = 'WorldPay_Json';

    /** @var string $moduleUrl */
    public $moduleUrl;
    /** @var array $hooks */
    public $hooks = [
        'displayPayment',
        'displayPaymentReturn',
    ];

    /**
     * OmnipayWorldpay constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'omnipayworldpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 1;
        $this->bootstrap = true;

        $this->is_eu_compatible = false;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->controllers = ['payment', 'validation'];

        parent::__construct();

        if (!empty($this->context->employee->id)) {
            $this->moduleUrl = $this->context->link->getAdminLink('AdminModules', true)
                .'&'.http_build_query([
                    'configure'   => $this->name,
                    'tab_module'  => $this->tab,
                    'module_name' => $this->name,
                ]);
        }

        $this->displayName = $this->l('Worldpay');
        $this->description = $this->l('Worldpay payment gateway');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        foreach ($this->hooks as $hook) {
            $this->registerHook($hook);
        }


        return true;
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $this->context->smarty->assign([
            'moduleUrl'   =>  $this->moduleUrl,
        ]);

        $this->postProcess();

        return $this->renderMainForm();
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPayment()
    {
        if (!$this->active || strtoupper($this->context->currency->iso_code) !== 'EUR') {
            return '';
        }

        $this->context->smarty->assign([
            'payment_methods' => $this->getPaymentMethods(),
            'credit_card'     => array_sum(array_map(function ($key) {
                    return Configuration::get(static::CREDENTIAL.$key) ? 1 : 0;
                }, array_keys(Omnipay::create(static::GATEWAY_NAME)->getDefaultParameters()))) === 3,
            'clientKey'       => Configuration::get(static::CREDENTIAL.'clientKey'),
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active
            || strtoupper($this->context->currency->iso_code) !== 'EUR'
        ) {
            return '';
        }

        /** @var Order $order */
        $order = $params['objOrder'];
        $currency = new Currency($order->id_currency);

        if (isset($order->reference) && $order->reference) {
            $totalToPay = $order->total_paid_tax_incl;
            $reference = $order->reference;
        } else {
            $totalToPay = $order->total_paid_tax_incl;
            $reference = $this->l('Unknown');
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->context->smarty->assign('status', 'ok');
        }

        $this->context->smarty->assign(
            [
                'id_order'  => $order->id,
                'reference' => $reference,
                'params'    => $params,
                'total'     => Tools::displayPrice($totalToPay, $currency, false),
            ]
        );

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     *
     * @since 1.0.0
     */
    protected function renderMainForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        $forms = [$this->getApiForm()];
        if ($this->getPaymentMethods()) {
            $forms = array_merge($forms, [$this->getPaymentMethodsForm()]);
        }

        return $helper->generateForm($forms);
    }

    /**
     * @return array
     */
    protected function getMainForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Force a module version'),
                    'icon'  => 'icon-puzzle',
                ],
                'input'  => [
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
    }

    /**
     * The API form is generated dynamically depending on the API credentials the payment gateway needs
     *
     * @return array
     */
    protected function getApiForm()
    {
        $input = [];
        foreach (Omnipay::create(static::GATEWAY_NAME)->getDefaultParameters() as $key => $default) {
            switch (gettype($default)) {
                case 'boolean':
                    $input [] = [
                        'type'   => 'switch',
                        'label'  => implode(' ', preg_split('/(?=[A-Z])/', ucfirst($key))),
                        'name'   => static::CREDENTIAL.$key,
                        'values' => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ];

                    break;
                default:
                    $input[] = [
                        'type'        => 'text',
                        'label'       => implode(' ', preg_split('/(?=[A-Z])/', ucfirst($key))),
                        'name'        => static::CREDENTIAL.$key,
                        'class'       => 'fixed-width-xxl',
                        'placeholder' => $default,
                    ];
                    break;
            }
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Gateway credentials'),
                    'icon'  => 'icon-key',
                ],
                'input'  => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getPaymentMethodsForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Payment methods'),
                    'icon'  => 'icon-credit-card',
                ],
                'description' => $this->l('This section shows other payment methods this payment gateway').' '.
                    $this->l('might support.').' '.
                    $this->l('Click "Refresh payment methods" to retrieve the list.'),
                'input'  => [
                    [
                        'type'            => 'payment_methods',
                        'label'           => $this->l('Payment methods'),
                        'name'            => static::PAYMENT_METHODS,
                        'payment_methods' => $this->getPaymentMethods(),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws PrestaShopException
     *
     * @since 1.0.0
     */
    protected function getConfigFieldsValues()
    {
        $fixedValues = [
        ];

        $dynamicValues = [];
        foreach (Omnipay::create(static::GATEWAY_NAME)->getDefaultParameters() as $key => $default) {
            $dynamicValues[static::CREDENTIAL.$key] = Configuration::get(static::CREDENTIAL.$key);
        }

        return array_merge($fixedValues, $dynamicValues);
    }

    /**
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        $confirm = false;

        if (Tools::isSubmit("submit{$this->name}")) {
            foreach (array_keys($this->getConfigFieldsValues()) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }

            $confirm = true;
        }

        if ($confirm) {
            $this->context->controller->confirmations[] = $this->l('The settings have been updated');
        }
    }

    /**
     * @return array|false
     */
    protected function getPaymentMethods()
    {
        return false;
    }
}
