<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class OmnipayMollie
 */
class OmnipayMollie extends Module
{
    /**
     * OmnipayMollie constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'omnipaymollie';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Force version');
        $this->description = $this->l('Force the version number of a module');
    }
}
