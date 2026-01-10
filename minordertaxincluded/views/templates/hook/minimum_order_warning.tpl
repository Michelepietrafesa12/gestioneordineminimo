{*
 * Minimum order warning template
 *
 * Variables available:
 * - $min_order_amount: Minimum order amount required
 * - $cart_total: Current cart total
 * - $remaining_amount: Amount remaining to reach minimum
 * - $currency_sign: Currency symbol
 *}

<div class="minorder-warning-container alert alert-warning" id="minorder-warning-container"
     data-min-order="{$min_order_amount|floatval}"
     data-cart-total="{$cart_total|floatval}"
     data-remaining="{$remaining_amount|floatval}">
    <p class="minorder-warning-message">
        <i class="material-icons">&#xE002;</i>
        <strong>{l s='Attenzione: Ordine minimo non raggiunto!' mod='minordertaxincluded'}</strong>
    </p>
    <p>
        {l s='L\'importo minimo per completare l\'ordine è di' mod='minordertaxincluded'}
        <strong>{$min_order_amount|number_format:2:",":"."}{$currency_sign}</strong>.
        <br>
        {l s='Il tuo carrello attuale è di' mod='minordertaxincluded'}
        <strong class="minorder-cart-total">{$cart_total|number_format:2:",":"."}{$currency_sign}</strong>.
        <br>
        {l s='Ti mancano' mod='minordertaxincluded'}
        <strong class="minorder-remaining">{$remaining_amount|number_format:2:",":"."}{$currency_sign}</strong>
        {l s='per poter procedere con l\'ordine.' mod='minordertaxincluded'}
    </p>
</div>
