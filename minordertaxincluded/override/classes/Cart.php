<?php
/**
 * Cart Override for Min Order Tax Included Module
 *
 * This override modifies the minimum order amount check to use
 * tax-included prices instead of tax-excluded prices.
 */

class Cart extends CartCore
{
    /**
     * Check if minimum order amount is reached
     * Modified to use tax-included prices when the module setting is enabled
     *
     * @return bool
     */
    public function isMinimalPurchaseReached()
    {
        // Check if our module is active and configured to use tax included
        if (Module::isEnabled('minordertaxincluded') && Configuration::get('MINORDER_USE_TAX_INCL')) {
            $minimalPurchase = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');

            if ($minimalPurchase <= 0) {
                // If module min order is 0 or disabled, fall back to PrestaShop default
                return parent::isMinimalPurchaseReached();
            }

            // Get cart total with tax included (products only)
            $cartTotal = (float) $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);

            return $cartTotal >= $minimalPurchase;
        }

        // Fall back to default PrestaShop behavior
        return parent::isMinimalPurchaseReached();
    }

    /**
     * Get the minimum purchase required
     * Modified to return module's configured value when enabled
     *
     * @return float
     */
    public function getMinimalPurchaseRequired()
    {
        // Check if our module is active and has a custom min order
        if (Module::isEnabled('minordertaxincluded')) {
            $moduleMinOrder = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');

            if ($moduleMinOrder > 0) {
                return $moduleMinOrder;
            }
        }

        // Fall back to PrestaShop's default minimal purchase
        return (float) Configuration::get('PS_PURCHASE_MINIMUM');
    }

    /**
     * Get remaining amount for minimum purchase
     *
     * @return float
     */
    public function getRemainingForMinimalPurchase()
    {
        $minimalPurchase = $this->getMinimalPurchaseRequired();

        if ($minimalPurchase <= 0) {
            return 0;
        }

        // Use tax included if module is configured for it
        $useTaxIncl = Module::isEnabled('minordertaxincluded') && Configuration::get('MINORDER_USE_TAX_INCL');
        $cartTotal = (float) $this->getOrderTotal($useTaxIncl, Cart::ONLY_PRODUCTS);

        return max(0, $minimalPurchase - $cartTotal);
    }
}
