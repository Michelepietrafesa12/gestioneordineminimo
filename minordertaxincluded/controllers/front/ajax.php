<?php
/**
 * AJAX Controller for Min Order Tax Included Module
 *
 * Handles AJAX requests for updating the progress bar
 */

class MinOrderTaxIncludedAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be called
     */
    public $ajax = true;

    /**
     * @var bool Disable SSL requirement for AJAX
     */
    public $ssl = true;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Process AJAX request
     */
    public function postProcess()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'getProgress':
                $this->getProgress();
                break;

            default:
                $this->ajaxRender(json_encode([
                    'success' => false,
                    'error' => 'Invalid action',
                ]));
        }
    }

    /**
     * Get progress data for the progress bar
     */
    protected function getProgress()
    {
        $freeShippingAmount = (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');

        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart)) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'cart_total' => 0,
                'remaining' => $freeShippingAmount,
                'percentage' => 0,
                'free_shipping_reached' => false,
            ]));
            return;
        }

        // Get cart total (products only) with or without tax
        $cartTotal = (float) $cart->getOrderTotal($useTaxIncl, Cart::ONLY_PRODUCTS);
        $remaining = max(0, $freeShippingAmount - $cartTotal);
        $percentage = $freeShippingAmount > 0 ? min(100, ($cartTotal / $freeShippingAmount) * 100) : 0;

        $this->ajaxRender(json_encode([
            'success' => true,
            'cart_total' => round($cartTotal, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'free_shipping_reached' => $remaining <= 0,
            'free_shipping_amount' => $freeShippingAmount,
        ]));
    }
}
