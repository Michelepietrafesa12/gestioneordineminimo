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
        $idLang = $this->context->language->id;
        $count = (int) Configuration::get('MINORDER_SUGGESTED_COUNT');
        $count = max(1, min(8, $count ?: 4));

        // Get cart product IDs to exclude
        $cartProductIds = [];
        if (Validate::isLoadedObject($cart)) {
            $cartProducts = $cart->getProducts();
            foreach ($cartProducts as $product) {
                $cartProductIds[] = (int) $product['id_product'];
            }
        }

        // Get bestseller product IDs
        $excludeIds = implode(',', array_map('intval', array_merge([0], $cartProductIds)));

        $sql = new DbQuery();
        $sql->select('DISTINCT od.product_id as id_product');
        $sql->from('order_detail', 'od');
        $sql->innerJoin('orders', 'o', 'o.id_order = od.id_order');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = od.product_id AND ps.id_shop = ' . (int) $this->context->shop->id);
        $sql->where('o.valid = 1');
        $sql->where('ps.active = 1');
        $sql->where('od.product_id NOT IN (' . $excludeIds . ')');
        $sql->groupBy('od.product_id');
        $sql->orderBy('SUM(od.product_quantity) DESC');
        $sql->limit($count * 2);

        $results = Db::getInstance()->executeS($sql);
        $productIds = [];
        if ($results) {
            foreach ($results as $row) {
                $productIds[] = (int) $row['id_product'];
            }
        }

        // If no bestsellers, get newest products
        if (empty($productIds)) {
            $sql = new DbQuery();
            $sql->select('p.id_product');
            $sql->from('product', 'p');
            $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $this->context->shop->id);
            $sql->where('ps.active = 1');
            $sql->where('p.id_product NOT IN (' . $excludeIds . ')');
            $sql->orderBy('p.date_add DESC');
            $sql->limit($count);

            $results = Db::getInstance()->executeS($sql);
            if ($results) {
                foreach ($results as $row) {
                    $productIds[] = (int) $row['id_product'];
                }
            }
        }

        // Build product data
        $suggestedProducts = [];
        foreach ($productIds as $productId) {
            if (count($suggestedProducts) >= $count) {
                break;
            }

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

            $cover = Product::getCover($product->id);
            $imageUrl = '';
            if ($cover) {
                $imageType = $this->getImageTypeFallback();
                $imageUrl = $this->context->link->getImageLink(
                    (string) $product->link_rewrite,
                    $cover['id_image'],
                    $imageType
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
        }

        return $suggestedProducts;
    }

    /**
     * Get image type name (PS 8.x compatible)
     */
    protected function getImageTypeFallback()
    {
        // For PS 8.x, query database
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            $sql = new DbQuery();
            $sql->select('name');
            $sql->from('image_type');
            $sql->where('name LIKE \'%home%\'');
            $sql->limit(1);

            $result = Db::getInstance()->getValue($sql);
            if ($result) {
                return (string) $result;
            }

            // Try getting any small image type
            $sql = new DbQuery();
            $sql->select('name');
            $sql->from('image_type');
            $sql->where('width <= 300');
            $sql->orderBy('width DESC');
            $sql->limit(1);

            $result = Db::getInstance()->getValue($sql);
            return $result ? (string) $result : 'home_default';
        }

        // For PS 1.7.x use ImageType class
        if (class_exists('ImageType') && method_exists('ImageType', 'getFormattedName')) {
            return ImageType::getFormattedName('home');
        }

        return 'home_default';
    }
}
