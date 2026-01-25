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

                case 'getProgressData':
                    $this->getProgressData();
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
     * Get all progress data including suggested products as JSON
     * This is for full JS-based rendering
     */
    protected function getProgressData()
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
        $currencySign = '€';
        if (isset($this->context->currency) && isset($this->context->currency->sign)) {
            $currencySign = $this->context->currency->sign;
        }

        // Get suggested products
        $suggestedProducts = [];
        if (!$minOrderReached && $showSuggested) {
            if ($this->module && method_exists($this->module, 'getSuggestedProducts')) {
                try {
                    $suggestedProducts = $this->module->getSuggestedProducts($remaining);
                    if (!is_array($suggestedProducts)) {
                        $suggestedProducts = [];
                    }
                } catch (Exception $e) {
                    $suggestedProducts = [];
                }
            }

            if (empty($suggestedProducts)) {
                $suggestedProducts = $this->getSuggestedProductsFallback($remaining, $cart, $useTaxIncl);
            }
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'min_order_amount' => round($minOrderAmount, 2),
            'cart_total' => round($cartTotal, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'min_order_reached' => $minOrderReached,
            'currency_sign' => $currencySign,
            'show_suggested' => $showSuggested && !empty($suggestedProducts),
            'suggested_products' => $suggestedProducts,
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
        if (!$minOrderReached && $showSuggested) {
            // Try to get from module
            if ($this->module && method_exists($this->module, 'getSuggestedProducts')) {
                try {
                    $suggestedProducts = $this->module->getSuggestedProducts($remaining);
                    if (!is_array($suggestedProducts)) {
                        $suggestedProducts = [];
                    }
                } catch (Exception $e) {
                    $suggestedProducts = [];
                }
            }

            // Fallback: get suggested products directly
            if (empty($suggestedProducts)) {
                $suggestedProducts = $this->getSuggestedProductsFallback($remaining, $cart, $useTaxIncl);
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

    /**
     * Fallback method to get suggested products directly in AJAX controller
     */
    protected function getSuggestedProductsFallback($remaining, $cart, $useTaxIncl)
    {
        try {
            $idLang = (int) $this->context->language->id;
            $idShop = (int) $this->context->shop->id;
            $count = (int) Configuration::get('MINORDER_SUGGESTED_COUNT');
            $count = max(1, min(8, $count ?: 4));
            $limit = (int) ($count * 2);

            // Get cart product IDs to exclude
            $cartProductIds = [0]; // Start with 0 to avoid empty IN clause
            if (Validate::isLoadedObject($cart)) {
                $cartProducts = $cart->getProducts();
                if (is_array($cartProducts)) {
                    foreach ($cartProducts as $product) {
                        $cartProductIds[] = (int) $product['id_product'];
                    }
                }
            }
            $excludeIds = implode(',', $cartProductIds);

            // Get bestseller product IDs using raw SQL
            $productIds = [];

            try {
                $sql = 'SELECT DISTINCT od.product_id as id_product
                        FROM `' . _DB_PREFIX_ . 'order_detail` od
                        INNER JOIN `' . _DB_PREFIX_ . 'orders` o ON o.id_order = od.id_order
                        INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = od.product_id AND ps.id_shop = ' . $idShop . '
                        WHERE o.valid = 1
                        AND ps.active = 1
                        AND od.product_id NOT IN (' . $excludeIds . ')
                        GROUP BY od.product_id
                        ORDER BY SUM(od.product_quantity) DESC
                        LIMIT ' . $limit;

                $results = Db::getInstance()->executeS($sql);
                if ($results && is_array($results)) {
                    foreach ($results as $row) {
                        $productIds[] = (int) $row['id_product'];
                    }
                }
            } catch (Exception $e) {
                // Ignore bestseller query error
            }

            // If no bestsellers, get newest products
            if (empty($productIds)) {
                try {
                    $sql = 'SELECT p.id_product
                            FROM `' . _DB_PREFIX_ . 'product` p
                            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = ' . $idShop . '
                            WHERE ps.active = 1
                            AND p.id_product NOT IN (' . $excludeIds . ')
                            ORDER BY p.date_add DESC
                            LIMIT ' . $count;

                    $results = Db::getInstance()->executeS($sql);
                    if ($results && is_array($results)) {
                        foreach ($results as $row) {
                            $productIds[] = (int) $row['id_product'];
                        }
                    }
                } catch (Exception $e) {
                    // Ignore newest products query error
                }
            }

            // Build product data
            $suggestedProducts = [];
            foreach ($productIds as $productId) {
                if (count($suggestedProducts) >= $count) {
                    break;
                }

                try {
                    $product = new Product((int) $productId, true, $idLang);
                    if (!Validate::isLoadedObject($product)) {
                        continue;
                    }

                    $price = $useTaxIncl ? $product->getPrice(true) : $product->getPrice(false);
                    if ($price <= 0) {
                        continue;
                    }

                    $regularPrice = $useTaxIncl
                        ? $product->getPrice(true, null, 6, null, false, false)
                        : $product->getPrice(false, null, 6, null, false, false);

                    $hasDiscount = ($regularPrice > $price && ($regularPrice - $price) > 0.01);

                    // Get image URL without SQL query - use default type
                    $imageUrl = '';
                    $cover = Product::getCover($product->id);
                    if ($cover && isset($cover['id_image'])) {
                        $imageUrl = $this->context->link->getImageLink(
                            (string) $product->link_rewrite,
                            $cover['id_image'],
                            'home_default'
                        );
                    }

                    $suggestedProducts[] = [
                        'id_product' => $product->id,
                        'name' => $product->name,
                        'price' => $price,
                        'price_formatted' => Tools::displayPrice($price),
                        'regular_price' => $regularPrice,
                        'regular_price_formatted' => Tools::displayPrice($regularPrice),
                        'has_discount' => $hasDiscount,
                        'link' => $this->context->link->getProductLink($product),
                        'image_url' => $imageUrl,
                        'reaches_minimum' => ($price >= $remaining),
                        'is_bestseller' => true,
                    ];
                } catch (Exception $e) {
                    // Skip this product if any error
                    continue;
                }
            }

            return $suggestedProducts;

        } catch (Exception $e) {
            // If anything fails, return empty array
            return [];
        }
    }

    /**
     * Get image type name
     * Returns hardcoded default to avoid any SQL issues
     */
    protected function getImageTypeFallback()
    {
        // Simply return home_default - it's the standard PrestaShop image type
        // This avoids any SQL queries that could cause issues
        return 'home_default';
    }
}
