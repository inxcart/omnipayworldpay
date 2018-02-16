{*
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
 *
*}
{extends file="helpers/form/form.tpl"}

{block name="input"}
  {if $input.type === 'payment_methods'}
    <section class="filter_panel">
      <header class="clearfix">
        <span class="badge badge-info">{l s='Total available' mod='OmnipayWorldpay'}: {$input.payment_methods|count}</span>
        <form action="{$moduleUrl|escape:'htmlall':'UTF-8'}"
              method="post"
              id="refresh_payments_{$input.name|escape:'htmlall':'UTF-8'}"
        >
          <input type="hidden" name="refreshPaymentMethods">
        </form>
        <button
                type="submit"
                form="refresh_payments_{$input.name|escape:'htmlall':'UTF-8'}"
                value="{l s='Refresh payment methods' mod='OmnipayWorldpay'}"
                class="btn btn-sm btn-default pull-right"
        >
          {l s='Refresh payment methods' mod='OmnipayWorldpay'} <i class="icon icon-refresh"></i>
        </button>
      </header>
      <section class="filter_list">
        <ul class="list-unstyled sortable ui-sortable">
          {foreach $input.payment_methods as $payment_method}
            <li draggable="true" class="filter_list_item" style="display: table;">
            <span class="switch prestashop-switch col-lg-2 col-md-3 col-sm-4 col-xs-4"
                  style="margin: 0px 5px;
                   pointer-events: all;"
            >
              <input type="radio"
                     id="meta_enabled_name_on"
                     name="meta_enabled_name"
                     value="1"
              >
              <label for="meta_enabled_name_on">
                <p>{l s='On' mod='OmnipayWorldpay'}</p>
              </label>
              <input type="radio" id="meta_enabled_name_off"
                     name="meta_enabled_name"
                     value="0"
              >
              <label for="meta_enabled_name_off">
                <p>{l s='Off' mod='OmnipayWorldpay'}</p>
              </label>
              <a class="slide-button btn"></a>
            </span>
              <div class="translatable-field col-lg-4">
                <div class="col-lg-9">
                  <input type="text" id="name_2" name="name2" required="required" value="{$payment_method['name'][1]}">
                </div>
                {*<div class="col-lg-2">*}
                  {*<button type="button" data-toggle="dropdown" class="btn btn-default dropdown-toggle">*}
                    {*<span>en</span>*}
                    {*<i class="icon-caret-down"></i>*}
                  {*</button>*}
                  {*<ul class="dropdown-menu">*}
                    {*<li><a class="pointer">Nederlands</a></li>*}
                    {*<li><a class="pointer">English</a></li>*}
                  {*</ul>*}
                {*</div>*}
              </div>
            </li>
          {/foreach}
        </ul>
      </section>
    </section>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
