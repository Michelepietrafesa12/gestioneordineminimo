{*
 * Progress bar template for free shipping
 *
 * Variables available:
 * - $free_shipping_amount: Amount needed for free shipping
 * - $cart_total: Current cart total
 * - $remaining_amount: Amount remaining for free shipping
 * - $progress_percentage: Progress percentage (0-100)
 * - $free_shipping_reached: Boolean if free shipping is reached
 * - $currency_sign: Currency symbol
 *}

<div class="minorder-progress-container" id="minorder-progress-container">
    {if $free_shipping_reached}
        <div class="minorder-success">
            <i class="material-icons">&#xE86C;</i>
            <span>{l s='Congratulazioni! Hai raggiunto la spedizione gratuita!' mod='minordertaxincluded'}</span>
        </div>
    {else}
        <div class="minorder-info">
            <p class="minorder-message">
                <i class="material-icons">&#xE558;</i>
                <span>
                    {l s='Ti mancano solo' mod='minordertaxincluded'}
                    <strong class="minorder-remaining-amount">{$remaining_amount|number_format:2:",":"."}{$currency_sign}</strong>
                    {l s='per ottenere la spedizione gratuita!' mod='minordertaxincluded'}
                </span>
            </p>
        </div>

        <div class="minorder-progress-bar-wrapper">
            <div class="minorder-progress-bar">
                <div class="minorder-progress-fill" style="width: {$progress_percentage|floatval}%;">
                    <span class="minorder-progress-text">{$progress_percentage|number_format:0}%</span>
                </div>
            </div>
            <div class="minorder-progress-labels">
                <span class="minorder-current">{$cart_total|number_format:2:",":"."}{$currency_sign}</span>
                <span class="minorder-target">{$free_shipping_amount|number_format:2:",":"."}{$currency_sign}</span>
            </div>
        </div>
    {/if}
</div>
