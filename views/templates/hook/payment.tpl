{if !empty($credit_card)}

{/if}
{foreach $payment_methods as $payment_method}
  <p class="payment_module omnipay_{$payment_method['id']|escape:'htmlall':'UTF-8'}_payment_button">
    <a id="omnipay_{$payment_method['id']|escape:'htmlall':'UTF-8'}_payment_link"
       href="{$link->getModuleLink('omnipaymollie', 'payment', ['method' => $payment_method['id']], true)|escape:'htmlall':'UTF-8'}"
       title="{l s='Pay with' mod='stripe'} {$payment_method['name'][$lang_id]|escape:'htmlall':'UTF-8'}"
    >
      {if !empty($payment_method['image'])}
        <img src="{$payment_method['image']|escape:'htmlall':'UTF-8'}"
             alt="{l s='Pay with' mod='omnipaymollie'} {$payment_method['name'][$lang_id]|escape:'htmlall':'UTF-8'}"
             width="64"
             height="64"
        />
      {/if}
      {l s='Pay with' mod='omnipaymollie'} {$payment_method['name'][$lang_id]|escape:'htmlall':'UTF-8'}
    </a>
  </p>
{/foreach}
