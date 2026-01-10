<?php
/**
 * Min Order Tax Included
 *
 * @author      Developer
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MinOrderTaxIncluded extends Module
{
    public function __construct()
    {
        $this->name = 'minordertaxincluded';
        $this->tab = 'checkout';
        $this->version = '1.3.0';
        $this->author = 'Developer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ordine Minimo IVA Inclusa');
        $this->description = $this->l('Modifica il calcolo dell\'ordine minimo usando prezzi IVA inclusa e mostra una barra di progresso per la spedizione gratuita.');
        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare questo modulo?');
    }

    /**
     * Install module
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('displayShoppingCartFooter')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBanner')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayCheckoutSubtotalDetails')
            && Configuration::updateValue('MINORDER_FREE_SHIPPING_AMOUNT', 50)
            && Configuration::updateValue('MINORDER_MIN_ORDER_AMOUNT', 0)
            && Configuration::updateValue('MINORDER_SHOW_PROGRESS_BAR', 1)
            && Configuration::updateValue('MINORDER_USE_TAX_INCL', 1);
    }

    /**
     * Uninstall module
     */
    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('MINORDER_FREE_SHIPPING_AMOUNT')
            && Configuration::deleteByName('MINORDER_MIN_ORDER_AMOUNT')
            && Configuration::deleteByName('MINORDER_SHOW_PROGRESS_BAR')
            && Configuration::deleteByName('MINORDER_USE_TAX_INCL');
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMinOrderSettings')) {
            $freeShippingAmount = (float) Tools::getValue('MINORDER_FREE_SHIPPING_AMOUNT');
            $minOrderAmount = (float) Tools::getValue('MINORDER_MIN_ORDER_AMOUNT');
            $showProgressBar = (int) Tools::getValue('MINORDER_SHOW_PROGRESS_BAR');
            $useTaxIncl = (int) Tools::getValue('MINORDER_USE_TAX_INCL');

            Configuration::updateValue('MINORDER_FREE_SHIPPING_AMOUNT', $freeShippingAmount);
            Configuration::updateValue('MINORDER_MIN_ORDER_AMOUNT', $minOrderAmount);
            Configuration::updateValue('MINORDER_SHOW_PROGRESS_BAR', $showProgressBar);
            Configuration::updateValue('MINORDER_USE_TAX_INCL', $useTaxIncl);

            $output .= $this->displayConfirmation($this->l('Impostazioni salvate con successo.'));
        }

        return $output . $this->renderForm();
    }

    /**
     * Render configuration form
     */
    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Impostazioni Ordine Minimo'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Usa prezzi IVA inclusa'),
                        'name' => 'MINORDER_USE_TAX_INCL',
                        'desc' => $this->l('Calcola l\'ordine minimo usando i prezzi con IVA inclusa invece che IVA esclusa.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Si'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Importo minimo ordine'),
                        'name' => 'MINORDER_MIN_ORDER_AMOUNT',
                        'desc' => $this->l('Importo minimo richiesto per completare un ordine (0 = disabilitato). Calcolato IVA inclusa se l\'opzione sopra è attiva.'),
                        'suffix' => '€',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Soglia spedizione gratuita'),
                        'name' => 'MINORDER_FREE_SHIPPING_AMOUNT',
                        'desc' => $this->l('Importo del carrello per ottenere la spedizione gratuita (0 = disabilitato).'),
                        'suffix' => '€',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mostra barra di progresso'),
                        'name' => 'MINORDER_SHOW_PROGRESS_BAR',
                        'desc' => $this->l('Mostra una barra di progresso nel carrello che indica quanto manca per la spedizione gratuita.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'bar_on',
                                'value' => 1,
                                'label' => $this->l('Si'),
                            ],
                            [
                                'id' => 'bar_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Salva'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMinOrderSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Get configuration values
     */
    protected function getConfigFormValues()
    {
        return [
            'MINORDER_FREE_SHIPPING_AMOUNT' => Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT'),
            'MINORDER_MIN_ORDER_AMOUNT' => Configuration::get('MINORDER_MIN_ORDER_AMOUNT'),
            'MINORDER_SHOW_PROGRESS_BAR' => Configuration::get('MINORDER_SHOW_PROGRESS_BAR'),
            'MINORDER_USE_TAX_INCL' => Configuration::get('MINORDER_USE_TAX_INCL'),
        ];
    }

    /**
     * Add CSS and JS to header
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/minordertaxincluded.css');
        $this->context->controller->addJS($this->_path . 'views/js/minordertaxincluded.js');

        // Pass configuration to JavaScript
        Media::addJsDef([
            'minorder_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax'),
            'minorder_free_shipping' => (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT'),
            'minorder_min_order' => (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT'),
            'minorder_currency_sign' => $this->context->currency->sign,
            'minorder_cart_total' => $this->getCartTotalTaxIncluded(),
        ]);

        return '';
    }

    /**
     * Display progress bar in shopping cart page (main content area only)
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        // Show unified progress bar for minimum order
        return $this->renderMinOrderProgressBar();
    }

    /**
     * Display progress bar in displayShoppingCart hook
     * Return empty to avoid duplicates - we only show in footer
     */
    public function hookDisplayShoppingCart($params)
    {
        // Return empty to avoid duplicate display
        return '';
    }

    /**
     * Render the unified progress bar for minimum order
     */
    protected function renderMinOrderProgressBar()
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');

        if ($minOrderAmount <= 0) {
            return '';
        }

        $cartTotal = $this->getCartTotalTaxIncluded();
        $remaining = max(0, $minOrderAmount - $cartTotal);
        $percentage = min(100, ($cartTotal / $minOrderAmount) * 100);
        $minOrderReached = $cartTotal >= $minOrderAmount;

        $this->context->smarty->assign([
            'min_order_amount' => $minOrderAmount,
            'cart_total' => $cartTotal,
            'remaining_amount' => $remaining,
            'progress_percentage' => $percentage,
            'min_order_reached' => $minOrderReached,
            'currency_sign' => $this->context->currency->sign,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/min_order_progress.tpl');
    }

    /**
     * Display banner with progress (optional)
     */
    public function hookDisplayBanner($params)
    {
        if (!Configuration::get('MINORDER_SHOW_PROGRESS_BAR')) {
            return '';
        }

        $freeShippingAmount = (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT');

        if ($freeShippingAmount <= 0) {
            return '';
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart) || $cart->nbProducts() == 0) {
            return '';
        }

        $cartTotal = $this->getCartTotalTaxIncluded();
        $remaining = $freeShippingAmount - $cartTotal;

        if ($remaining <= 0) {
            return '';
        }

        $this->context->smarty->assign([
            'remaining_for_free_shipping' => $remaining,
            'currency_sign' => $this->context->currency->sign,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/banner.tpl');
    }

    /**
     * Hook on cart save to check minimum order
     */
    public function hookActionCartSave($params)
    {
        // Validation is done via JavaScript and checkout blocking
    }

    /**
     * Hook on front controller to block checkout access
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');

        // Block access to checkout if minimum order not reached
        if ($controller === 'order' || $controller === 'orderopc') {
            if (!$this->isMinimumOrderReached()) {
                $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
                $cartTotal = $this->getCartTotalTaxIncluded();
                $remaining = $minOrderAmount - $cartTotal;

                // Add error message
                $this->context->controller->errors[] = sprintf(
                    $this->l('L\'importo minimo per effettuare un ordine è di %s. Il tuo carrello è di %s. Ti mancano %s.'),
                    Tools::displayPrice($minOrderAmount),
                    Tools::displayPrice($cartTotal),
                    Tools::displayPrice($remaining)
                );

                // Redirect to cart
                Tools::redirect($this->context->link->getPageLink('cart', true, null, ['action' => 'show']));
            }
        }
    }

    /**
     * Display warning in checkout subtotal - disabled to avoid duplicates
     * The redirect in hookActionFrontControllerSetMedia handles checkout blocking
     */
    public function hookDisplayCheckoutSubtotalDetails($params)
    {
        // Return empty - checkout is blocked by redirect, no need for duplicate warnings
        return '';
    }

    /**
     * Validate order - check minimum amount with tax included
     * This is a last line of defense
     */
    public function hookActionValidateOrder($params)
    {
        if (!$this->isMinimumOrderReached()) {
            throw new PrestaShopException($this->l('Ordine minimo non raggiunto. Impossibile completare l\'ordine.'));
        }
    }

    /**
     * Get cart total with tax included
     */
    public function getCartTotalTaxIncluded()
    {
        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart)) {
            return 0;
        }

        // Get total with tax included (products only, no shipping)
        if (Configuration::get('MINORDER_USE_TAX_INCL')) {
            return (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        }

        return (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
    }

    /**
     * Check if minimum order amount is reached
     */
    public function isMinimumOrderReached()
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');

        if ($minOrderAmount <= 0) {
            return true;
        }

        $cartTotal = $this->getCartTotalTaxIncluded();

        return $cartTotal >= $minOrderAmount;
    }

    /**
     * Get remaining amount for minimum order
     */
    public function getRemainingForMinimumOrder()
    {
        $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');
        $cartTotal = $this->getCartTotalTaxIncluded();

        return max(0, $minOrderAmount - $cartTotal);
    }

    /**
     * Get remaining amount for free shipping
     */
    public function getRemainingForFreeShipping()
    {
        $freeShippingAmount = (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT');
        $cartTotal = $this->getCartTotalTaxIncluded();

        return max(0, $freeShippingAmount - $cartTotal);
    }
}
