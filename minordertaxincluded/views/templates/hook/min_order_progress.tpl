{*
 * Progress bar template for minimum order
 *
 * Variables available:
 * - $min_order_amount: Minimum order amount required
 * - $cart_total: Current cart total
 * - $remaining_amount: Amount remaining to reach minimum
 * - $progress_percentage: Progress percentage (0-100)
 * - $min_order_reached: Boolean if minimum order is reached
 * - $currency_sign: Currency symbol
 * - $suggested_products: Array of suggested products
 * - $show_suggested: Boolean to show suggested products section
 *}

<div class="minorder-progress-container" id="minorder-progress-container"
     data-min-order="{$min_order_amount|floatval}"
     data-cart-total="{$cart_total|floatval}"
     data-remaining="{$remaining_amount|floatval}">
    {if $min_order_reached}
        <div class="minorder-success">
            <span class="minorder-icon">&#10003;</span>
            <span>{l s='Ordine minimo raggiunto! Puoi procedere al checkout.' mod='minordertaxincluded'}</span>
        </div>
    {else}
        <div class="minorder-info">
            <p class="minorder-message">
                <span class="minorder-icon minorder-icon-warning">&#9888;</span>
                <span>
                    {l s='Ordine minimo:' mod='minordertaxincluded'}
                    <strong>{$min_order_amount|number_format:2:",":"."}{$currency_sign}</strong>
                    &mdash;
                    {l s='Ti mancano' mod='minordertaxincluded'}
                    <strong class="minorder-remaining-amount">{$remaining_amount|number_format:2:",":"."}{$currency_sign}</strong>
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
                <span class="minorder-target">{$min_order_amount|number_format:2:",":"."}{$currency_sign}</span>
            </div>
        </div>

        {* Suggested products section *}
        {if $show_suggested && $suggested_products|count > 0}
            <div class="minorder-suggested-products">
                <h4 class="minorder-suggested-title">
                    <span class="minorder-icon">&#128722;</span>
                    {l s='Aggiungi questi prodotti per raggiungere l\'ordine minimo:' mod='minordertaxincluded'}
                </h4>
                <div class="minorder-suggested-grid">
                    {foreach from=$suggested_products item=product}
                        <div class="minorder-suggested-item{if $product.reaches_minimum} minorder-reaches-min{/if}">
                            <a href="{$product.link}" class="minorder-suggested-link">
                                {if $product.image_url}
                                    <div class="minorder-suggested-image">
                                        <img src="{$product.image_url}" alt="{$product.name|escape:'html':'UTF-8'}" loading="lazy">
                                        {if $product.reaches_minimum}
                                            <span class="minorder-badge-reaches">{l s='Raggiungi la soglia!' mod='minordertaxincluded'}</span>
                                        {/if}
                                    </div>
                                {/if}
                                <div class="minorder-suggested-info">
                                    <span class="minorder-suggested-name">{$product.name|truncate:40:'...'|escape:'html':'UTF-8'}</span>
                                    <span class="minorder-suggested-price">{$product.price_formatted}</span>
                                </div>
                            </a>
                            <button type="button"
                                    class="minorder-add-btn"
                                    data-id-product="{$product.id_product}"
                                    data-id-product-attribute="0"
                                    data-minimal-quantity="1"
                                    title="{l s='Aggiungi al carrello' mod='minordertaxincluded'}">
                                <span class="minorder-icon">&#128722;</span>
                                <span class="minorder-add-text">{l s='Aggiungi' mod='minordertaxincluded'}</span>
                            </button>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    {/if}
</div>
