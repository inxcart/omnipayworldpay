{if !empty($credit_card)}
  <form action="{$link->getModuleLink('omnipayworldpay', 'validation', [], true)|escape:'html'}"
        id="worldpayForm"
        method="post"
  >
    <div id="worldpaySection"></div>
    <div>
      <button type="submit"
              class="btn btn-default btn-lg"
              value="{l s='Place Order' mod='omnipayworldpay'}"
              onclick="Worldpay.submitTemplateForm()"
      >
        {l s='Place Order' mod='omnipayworldpay'} <i class="icon icon-chevron-right"></i>
      </button>
    </div>
  </form>
  <script src="https://cdn.worldpay.com/v1/worldpay.js"></script>
  <script type='text/javascript'>
    (function() {
      Worldpay.useTemplateForm({
        clientKey:'{$clientKey|escape:'javascript'}',
        form: 'worldpayForm',
        paymentSection: 'worldpaySection',
        display: 'inline',
        reusable: true,
        callback: function(obj) {
          if (obj && obj.token) {
            var _el = document.createElement('input');
            _el.value = obj.token;
            _el.type = 'hidden';
            _el.name = 'token';
            document.getElementById('worldpayForm').appendChild(_el);
            document.getElementById('worldpayForm').submit();
          }
        }
      });
    }());
  </script>
{/if}
