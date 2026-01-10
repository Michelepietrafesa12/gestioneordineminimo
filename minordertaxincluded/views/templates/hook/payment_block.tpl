{*
 * Payment block template - shown when minimum order not reached
 *
 * Variables available:
 * - $min_order_amount: Minimum order amount required
 * - $cart_total: Current cart total
 * - $remaining_amount: Amount remaining to reach minimum
 * - $currency_sign: Currency symbol
 * - $cart_url: URL to cart page
 *}

<div class="alert alert-danger minorder-payment-block">
    <h4>
        <span class="minorder-icon minorder-icon-warning">&#9888;</span>
        {l s='Impossibile procedere con il pagamento' mod='minordertaxincluded'}
    </h4>
    <p>
        {l s='L\'importo minimo per completare l\'ordine è di' mod='minordertaxincluded'}
        <strong>{$min_order_amount|number_format:2:",":"."}{$currency_sign}</strong>.
    </p>
    <p>
        {l s='Il tuo carrello attuale è di' mod='minordertaxincluded'}
        <strong>{$cart_total|number_format:2:",":"."}{$currency_sign}</strong>.
        {l s='Ti mancano' mod='minordertaxincluded'}
        <strong>{$remaining_amount|number_format:2:",":"."}{$currency_sign}</strong>
        {l s='per poter procedere.' mod='minordertaxincluded'}
    </p>
    <p class="mt-3">
        <a href="{$cart_url}" class="btn btn-primary">
            {l s='Torna al carrello' mod='minordertaxincluded'}
        </a>
    </p>
</div>

<style>
.minorder-payment-block {
    margin-bottom: 20px;
    padding: 20px;
}
.minorder-payment-block h4 {
    margin-top: 0;
    color: #721c24;
}
.minorder-payment-block .minorder-icon {
    margin-right: 10px;
}
</style>

<script>
// Hide all payment methods when minimum order not reached
document.addEventListener('DOMContentLoaded', function() {
    var paymentOptions = document.querySelectorAll('.payment-options, #payment-confirmation, .js-payment-option-form');
    paymentOptions.forEach(function(el) {
        el.style.display = 'none';
    });
});
</script>
