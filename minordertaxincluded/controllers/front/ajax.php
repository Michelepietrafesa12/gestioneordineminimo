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
        try {
            $action = (string) Tools::getValue('action');

            switch ($action) {
                case 'getProgress':
                    $this->getProgress();
                    break;

                case 'getProgressHtml':
                    $this->getProgressHtml();
                    break;

                default:
                    $this->ajaxRender(json_encode([
                        'success' => false,
                        'error' => 'Invalid action',
                    ]));
            }
        } catch (Exception $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Get progress data for the progress bar
     */
    protected function getProgress()
    {
        $freeShippingAmount = (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT');
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');

        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart)) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'cart_total' => 0,
                'remaining' => $freeShippingAmount,
                'percentage' => 0,
                'free_shipping_reached' => false,
                'min_order_amount' => $minOrderAmount,
                'min_order_remaining' => $minOrderAmount,
                'min_order_reached' => $minOrderAmount <= 0,
            ]));
            return;
        }

        // Get cart total (products only) with or without tax
        $cartTotal = (float) $cart->getOrderTotal($useTaxIncl, Cart::ONLY_PRODUCTS);

        // Free shipping calculations
        $remaining = max(0, $freeShippingAmount - $cartTotal);
        $percentage = $freeShippingAmount > 0 ? min(100, ($cartTotal / $freeShippingAmount) * 100) : 0;

        // Minimum order calculations
        $minOrderRemaining = max(0, $minOrderAmount - $cartTotal);
        $minOrderReached = $minOrderAmount <= 0 || $cartTotal >= $minOrderAmount;

        $this->ajaxRender(json_encode([
            'success' => true,
            'cart_total' => round($cartTotal, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'free_shipping_reached' => $remaining <= 0,
            'free_shipping_amount' => $freeShippingAmount,
            'min_order_amount' => $minOrderAmount,
            'min_order_remaining' => round($minOrderRemaining, 2),
            'min_order_reached' => $minOrderReached,
        ]));
    }

    /**
     * Get rendered HTML for the progress bar section
     * This is used to fully refresh the section including suggested products
     */
    protected function getProgressHtml()
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');
        $showSuggested = (bool) Configuration::get('MINORDER_SHOW_SUGGESTED');

        $cart = $this->context->cart;

        if ($minOrderAmount <= 0) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'html' => '',
                'min_order_reached' => true,
            ]));
            return;
        }

        $cartTotal = 0;
        if (Validate::isLoadedObject($cart)) {
            $cartTotal = (float) $cart->getOrderTotal($useTaxIncl, Cart::ONLY_PRODUCTS);
        }

        $remaining = max(0, $minOrderAmount - $cartTotal);
        $percentage = min(100, ($cartTotal / $minOrderAmount) * 100);
        $minOrderReached = $cartTotal >= $minOrderAmount;

        // Get suggested products if minimum not reached
        $suggestedProducts = [];
        if (!$minOrderReached && $showSuggested && $this->module) {
            try {
                $suggestedProducts = $this->module->getSuggestedProducts($remaining);
                if (!is_array($suggestedProducts)) {
                    $suggestedProducts = [];
                }
            } catch (Exception $e) {
                $suggestedProducts = [];
            }
        }

        // Get currency sign safely
        $currencySign = '€';
        if (isset($this->context->currency) && isset($this->context->currency->sign)) {
            $currencySign = $this->context->currency->sign;
        }

        $this->context->smarty->assign([
            'min_order_amount' => (float) $minOrderAmount,
            'cart_total' => (float) $cartTotal,
            'remaining_amount' => (float) $remaining,
            'progress_percentage' => (float) $percentage,
            'min_order_reached' => (bool) $minOrderReached,
            'currency_sign' => (string) $currencySign,
            'suggested_products' => $suggestedProducts,
            'show_suggested' => (bool) ($showSuggested && !empty($suggestedProducts)),
        ]);

        $html = '';
        if ($this->module) {
            $html = $this->module->display(
                $this->module->getLocalPath() . $this->module->name . '.php',
                'views/templates/hook/min_order_progress.tpl'
            );
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'html' => $html,
            'cart_total' => round($cartTotal, 2),
            'min_order_reached' => $minOrderReached,
            'min_order_remaining' => round($remaining, 2),
        ]));
    }
}
