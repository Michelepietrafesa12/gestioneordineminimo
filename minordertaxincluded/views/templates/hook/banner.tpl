{*
 * Banner template for free shipping notification
 *
 * Variables available:
 * - $remaining_for_free_shipping: Amount remaining for free shipping
 * - $currency_sign: Currency symbol
 *}

<div class="minorder-banner" id="minorder-banner">
    <div class="minorder-banner-content">
        <i class="material-icons">&#xE558;</i>
        <span>
            {l s='Aggiungi' mod='minordertaxincluded'}
            <strong>{$remaining_for_free_shipping|number_format:2:",":"."}{$currency_sign}</strong>
            {l s='al carrello per la spedizione gratuita!' mod='minordertaxincluded'}
        </span>
    </div>
</div>
