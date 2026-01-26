<?php
/**
 * AJAX Controller for Min Order Tax Included Module
 * PrestaShop 8.x compatible - uses proper HTTP responses
 *
 * @author Developer
 * @version 2.0.0
 */

class MinOrderTaxIncludedAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool AJAX mode
     */
    public $ajax = true;

    /**
     * @var bool SSL requirement
     */
    public $ssl = true;

    /**
     * Allowed AJAX actions (whitelist for security)
     */
    private const ALLOWED_ACTIONS = ['getProgress', 'getProgressHtml', 'getProgressData'];

    /**
     * Initialize controller - set JSON headers
     */
    public function init(): void
    {
        parent::init();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Process AJAX request with CSRF validation
     */
    public function postProcess(): void
    {
        try {
            // CSRF Token validation for POST requests
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = Tools::getValue('token');
                $staticToken = Tools::getToken(false);

                // Validate token (skip for GET-like read operations if needed)
                if (!$this->isValidToken($token, $staticToken)) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Invalid security token',
                    ], 403);
                    return;
                }
            }

            $action = (string) Tools::getValue('action');

            // Security: Validate action against whitelist
            if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid action',
                ], 400);
                return;
            }

            // Route to appropriate handler
            match ($action) {
                'getProgress' => $this->getProgress(),
                'getProgressHtml' => $this->getProgressHtml(),
                'getProgressData' => $this->getProgressData(),
            };

        } catch (Exception $e) {
            // Log error for debugging (don't expose details to client)
            PrestaShopLogger::addLog(
                'MinOrderTaxIncluded AJAX error: ' . $e->getMessage(),
                3,
                null,
                'Module',
                null
            );
            $this->jsonResponse([
                'success' => false,
                'error' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Validate CSRF token
     * Allows requests without token for backward compatibility (read-only operations)
     */
    private function isValidToken(?string $token, string $staticToken): bool
    {
        // If no token provided, allow for backward compatibility
        // These are read-only operations that don't modify data
        if (empty($token)) {
            return true;
        }

        // Validate provided token
        return hash_equals($staticToken, $token);
    }

    /**
     * Send JSON response and exit (PS 8.x compatible)
     * Replaces deprecated ajaxRender()
     */
    private function jsonResponse(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        die(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Get progress data for the progress bar
     */
    protected function getProgress(): void
    {
        $freeShippingAmount = (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT');
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');

        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart)) {
            $this->jsonResponse([
                'success' => true,
                'cart_total' => 0,
                'remaining' => $freeShippingAmount,
                'percentage' => 0,
                'free_shipping_reached' => false,
                'min_order_amount' => $minOrderAmount,
                'min_order_remaining' => $minOrderAmount,
                'min_order_reached' => $minOrderAmount <= 0,
            ]);
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

        $this->jsonResponse([
            'success' => true,
            'cart_total' => round($cartTotal, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'free_shipping_reached' => $remaining <= 0,
            'free_shipping_amount' => $freeShippingAmount,
            'min_order_amount' => $minOrderAmount,
            'min_order_remaining' => round($minOrderRemaining, 2),
            'min_order_reached' => $minOrderReached,
        ]);
    }

    /**
     * Get all progress data including suggested products as JSON
     * This is for full JS-based rendering
     */
    protected function getProgressData(): void
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');
        $showSuggested = (bool) Configuration::get('MINORDER_SHOW_SUGGESTED');

        $cart = $this->context->cart;

        $cartTotal = 0;
        if (Validate::isLoadedObject($cart)) {
            $cartTotal = (float) $cart->getOrderTotal($useTaxIncl, Cart::ONLY_PRODUCTS);
        }

        $remaining = max(0, $minOrderAmount - $cartTotal);
        $percentage = $minOrderAmount > 0 ? min(100, ($cartTotal / $minOrderAmount) * 100) : 0;
        $minOrderReached = $minOrderAmount <= 0 || $cartTotal >= $minOrderAmount;

        // Get currency sign
        $currencySign = $this->context->currency?->sign ?? '€';

        // Get suggested products from module
        $suggestedProducts = [];
        if (!$minOrderReached && $showSuggested && $this->module) {
            try {
                $suggestedProducts = $this->module->getSuggestedProducts($remaining);
                if (!is_array($suggestedProducts)) {
                    $suggestedProducts = [];
                }
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    'MinOrderTaxIncluded getSuggestedProducts error: ' . $e->getMessage(),
                    2,
                    null,
                    'Module',
                    null
                );
                $suggestedProducts = [];
            }
        }

        $this->jsonResponse([
            'success' => true,
            'min_order_amount' => round($minOrderAmount, 2),
            'cart_total' => round($cartTotal, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'min_order_reached' => $minOrderReached,
            'currency_sign' => $currencySign,
            'show_suggested' => $showSuggested && !empty($suggestedProducts),
            'suggested_products' => $suggestedProducts,
        ]);
    }

    /**
     * Get rendered HTML for the progress bar section
     */
    protected function getProgressHtml(): void
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');
        $showSuggested = (bool) Configuration::get('MINORDER_SHOW_SUGGESTED');

        $cart = $this->context->cart;

        if ($minOrderAmount <= 0) {
            $this->jsonResponse([
                'success' => true,
                'html' => '',
                'min_order_reached' => true,
            ]);
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
        $currencySign = $this->context->currency?->sign ?? '€';

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

        $this->jsonResponse([
            'success' => true,
            'html' => $html,
            'cart_total' => round($cartTotal, 2),
            'min_order_reached' => $minOrderReached,
            'min_order_remaining' => round($remaining, 2),
        ]);
    }
}
