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
        $this->version = '1.12.0';
        $this->author = 'Developer';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
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
            && $this->registerHook('displayCrossSellingShoppingCart')
            && $this->registerHook('displayReassurance')
            && $this->registerHook('displayAfterBodyOpeningTag')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBanner')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayCheckoutSubtotalDetails')
            && Configuration::updateValue('MINORDER_FREE_SHIPPING_AMOUNT', 50)
            && Configuration::updateValue('MINORDER_MIN_ORDER_AMOUNT', 0)
            && Configuration::updateValue('MINORDER_SHOW_PROGRESS_BAR', 1)
            && Configuration::updateValue('MINORDER_USE_TAX_INCL', 1)
            && Configuration::updateValue('MINORDER_SHOW_SUGGESTED', 1)
            && Configuration::updateValue('MINORDER_SUGGESTED_COUNT', 4)
            // Style colors
            && Configuration::updateValue('MINORDER_COLOR_PRIMARY', '#28a745')
            && Configuration::updateValue('MINORDER_COLOR_SECONDARY', '#ff6b35')
            && Configuration::updateValue('MINORDER_COLOR_WARNING', '#ffc107')
            && Configuration::updateValue('MINORDER_COLOR_PROGRESS_BG', '#e9ecef')
            && Configuration::updateValue('MINORDER_COLOR_BUTTON', '#28a745')
            && Configuration::updateValue('MINORDER_COLOR_BESTSELLER', '#dc3545')
            && Configuration::updateValue('MINORDER_COLOR_PRICE_OLD', '#999999')
            && Configuration::updateValue('MINORDER_COLOR_TEXT', '#333333')
            && Configuration::updateValue('MINORDER_COLOR_CARD_BG', '#ffffff')
            && Configuration::updateValue('MINORDER_COLOR_CARD_BORDER', '#eeeeee')
            && Configuration::updateValue('MINORDER_COLOR_BUTTON_TEXT', '#ffffff');
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
            && Configuration::deleteByName('MINORDER_USE_TAX_INCL')
            && Configuration::deleteByName('MINORDER_SHOW_SUGGESTED')
            && Configuration::deleteByName('MINORDER_SUGGESTED_COUNT')
            // Style colors
            && Configuration::deleteByName('MINORDER_COLOR_PRIMARY')
            && Configuration::deleteByName('MINORDER_COLOR_SECONDARY')
            && Configuration::deleteByName('MINORDER_COLOR_WARNING')
            && Configuration::deleteByName('MINORDER_COLOR_PROGRESS_BG')
            && Configuration::deleteByName('MINORDER_COLOR_BUTTON')
            && Configuration::deleteByName('MINORDER_COLOR_BESTSELLER')
            && Configuration::deleteByName('MINORDER_COLOR_PRICE_OLD')
            && Configuration::deleteByName('MINORDER_COLOR_TEXT')
            && Configuration::deleteByName('MINORDER_COLOR_CARD_BG')
            && Configuration::deleteByName('MINORDER_COLOR_CARD_BORDER')
            && Configuration::deleteByName('MINORDER_COLOR_BUTTON_TEXT');
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        $output = '';

        // Save main settings
        if (Tools::isSubmit('submitMinOrderSettings')) {
            $freeShippingAmount = (float) Tools::getValue('MINORDER_FREE_SHIPPING_AMOUNT');
            $minOrderAmount = (float) Tools::getValue('MINORDER_MIN_ORDER_AMOUNT');
            $showProgressBar = (int) Tools::getValue('MINORDER_SHOW_PROGRESS_BAR');
            $useTaxIncl = (int) Tools::getValue('MINORDER_USE_TAX_INCL');
            $showSuggested = (int) Tools::getValue('MINORDER_SHOW_SUGGESTED');
            $suggestedCount = (int) Tools::getValue('MINORDER_SUGGESTED_COUNT');

            Configuration::updateValue('MINORDER_FREE_SHIPPING_AMOUNT', $freeShippingAmount);
            Configuration::updateValue('MINORDER_MIN_ORDER_AMOUNT', $minOrderAmount);
            Configuration::updateValue('MINORDER_SHOW_PROGRESS_BAR', $showProgressBar);
            Configuration::updateValue('MINORDER_USE_TAX_INCL', $useTaxIncl);
            Configuration::updateValue('MINORDER_SHOW_SUGGESTED', $showSuggested);
            Configuration::updateValue('MINORDER_SUGGESTED_COUNT', max(1, min(8, $suggestedCount)));

            $output .= $this->displayConfirmation($this->l('Impostazioni salvate con successo.'));
        }

        // Save color settings (separate action)
        if (Tools::isSubmit('submitMinOrderColors')) {
            $colorPrimary = Tools::getValue('MINORDER_COLOR_PRIMARY', '#28a745');
            $colorSecondary = Tools::getValue('MINORDER_COLOR_SECONDARY', '#ff6b35');
            $colorWarning = Tools::getValue('MINORDER_COLOR_WARNING', '#ffc107');
            $colorProgressBg = Tools::getValue('MINORDER_COLOR_PROGRESS_BG', '#e9ecef');
            $colorButton = Tools::getValue('MINORDER_COLOR_BUTTON', '#28a745');
            $colorBestseller = Tools::getValue('MINORDER_COLOR_BESTSELLER', '#dc3545');
            $colorPriceOld = Tools::getValue('MINORDER_COLOR_PRICE_OLD', '#999999');
            $colorText = Tools::getValue('MINORDER_COLOR_TEXT', '#333333');
            $colorCardBg = Tools::getValue('MINORDER_COLOR_CARD_BG', '#ffffff');
            $colorCardBorder = Tools::getValue('MINORDER_COLOR_CARD_BORDER', '#eeeeee');
            $colorButtonText = Tools::getValue('MINORDER_COLOR_BUTTON_TEXT', '#ffffff');

            Configuration::updateValue('MINORDER_COLOR_PRIMARY', $colorPrimary);
            Configuration::updateValue('MINORDER_COLOR_SECONDARY', $colorSecondary);
            Configuration::updateValue('MINORDER_COLOR_WARNING', $colorWarning);
            Configuration::updateValue('MINORDER_COLOR_PROGRESS_BG', $colorProgressBg);
            Configuration::updateValue('MINORDER_COLOR_BUTTON', $colorButton);
            Configuration::updateValue('MINORDER_COLOR_BESTSELLER', $colorBestseller);
            Configuration::updateValue('MINORDER_COLOR_PRICE_OLD', $colorPriceOld);
            Configuration::updateValue('MINORDER_COLOR_TEXT', $colorText);
            Configuration::updateValue('MINORDER_COLOR_CARD_BG', $colorCardBg);
            Configuration::updateValue('MINORDER_COLOR_CARD_BORDER', $colorCardBorder);
            Configuration::updateValue('MINORDER_COLOR_BUTTON_TEXT', $colorButtonText);

            $output .= $this->displayConfirmation($this->l('Colori salvati con successo.'));
        }

        return $output . $this->renderForm() . $this->renderStyleForm();
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
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mostra prodotti consigliati'),
                        'name' => 'MINORDER_SHOW_SUGGESTED',
                        'desc' => $this->l('Mostra prodotti consigliati per raggiungere l\'ordine minimo quando il carrello è sotto la soglia.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'suggested_on',
                                'value' => 1,
                                'label' => $this->l('Si'),
                            ],
                            [
                                'id' => 'suggested_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Numero prodotti consigliati'),
                        'name' => 'MINORDER_SUGGESTED_COUNT',
                        'desc' => $this->l('Numero di prodotti da mostrare come suggerimento (da 1 a 8).'),
                        'class' => 'fixed-width-xs',
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
     * Render style/color configuration form
     */
    protected function renderStyleForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Personalizzazione Colori'),
                    'icon' => 'icon-paint-brush',
                ],
                'input' => [
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore primario (successo/barra)'),
                        'name' => 'MINORDER_COLOR_PRIMARY',
                        'desc' => $this->l('Colore della barra di progresso e messaggi di successo. Default: #28a745 (verde)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore secondario (prezzi)'),
                        'name' => 'MINORDER_COLOR_SECONDARY',
                        'desc' => $this->l('Colore dei prezzi nei prodotti consigliati. Default: #ff6b35 (arancione)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore avviso'),
                        'name' => 'MINORDER_COLOR_WARNING',
                        'desc' => $this->l('Colore delle icone di avviso. Default: #ffc107 (giallo)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Sfondo barra progresso'),
                        'name' => 'MINORDER_COLOR_PROGRESS_BG',
                        'desc' => $this->l('Colore di sfondo della barra di progresso. Default: #e9ecef (grigio chiaro)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore pulsante Aggiungi'),
                        'name' => 'MINORDER_COLOR_BUTTON',
                        'desc' => $this->l('Colore del pulsante "Aggiungi al carrello". Default: #28a745 (verde)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore testo pulsante'),
                        'name' => 'MINORDER_COLOR_BUTTON_TEXT',
                        'desc' => $this->l('Colore del testo nel pulsante "Aggiungi". Default: #ffffff (bianco)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore badge Bestseller'),
                        'name' => 'MINORDER_COLOR_BESTSELLER',
                        'desc' => $this->l('Colore del badge "Più acquistato". Default: #dc3545 (rosso)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore prezzo barrato'),
                        'name' => 'MINORDER_COLOR_PRICE_OLD',
                        'desc' => $this->l('Colore del prezzo originale barrato. Default: #999999 (grigio)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore testo'),
                        'name' => 'MINORDER_COLOR_TEXT',
                        'desc' => $this->l('Colore del testo principale. Default: #333333 (grigio scuro)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Sfondo schede prodotto'),
                        'name' => 'MINORDER_COLOR_CARD_BG',
                        'desc' => $this->l('Colore di sfondo delle schede prodotto. Default: #ffffff (bianco)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Bordo schede prodotto'),
                        'name' => 'MINORDER_COLOR_CARD_BORDER',
                        'desc' => $this->l('Colore del bordo delle schede prodotto. Default: #eeeeee (grigio chiaro)'),
                        'class' => 'mColorPicker',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Salva Colori'),
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
        $helper->submit_action = 'submitMinOrderColors';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getStyleFormValues(),
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
            'MINORDER_SHOW_SUGGESTED' => Configuration::get('MINORDER_SHOW_SUGGESTED'),
            'MINORDER_SUGGESTED_COUNT' => Configuration::get('MINORDER_SUGGESTED_COUNT'),
        ];
    }

    /**
     * Get style/color configuration values
     */
    protected function getStyleFormValues()
    {
        return [
            'MINORDER_COLOR_PRIMARY' => Configuration::get('MINORDER_COLOR_PRIMARY') ?: '#28a745',
            'MINORDER_COLOR_SECONDARY' => Configuration::get('MINORDER_COLOR_SECONDARY') ?: '#ff6b35',
            'MINORDER_COLOR_WARNING' => Configuration::get('MINORDER_COLOR_WARNING') ?: '#ffc107',
            'MINORDER_COLOR_PROGRESS_BG' => Configuration::get('MINORDER_COLOR_PROGRESS_BG') ?: '#e9ecef',
            'MINORDER_COLOR_BUTTON' => Configuration::get('MINORDER_COLOR_BUTTON') ?: '#28a745',
            'MINORDER_COLOR_BESTSELLER' => Configuration::get('MINORDER_COLOR_BESTSELLER') ?: '#dc3545',
            'MINORDER_COLOR_PRICE_OLD' => Configuration::get('MINORDER_COLOR_PRICE_OLD') ?: '#999999',
            'MINORDER_COLOR_TEXT' => Configuration::get('MINORDER_COLOR_TEXT') ?: '#333333',
            'MINORDER_COLOR_CARD_BG' => Configuration::get('MINORDER_COLOR_CARD_BG') ?: '#ffffff',
            'MINORDER_COLOR_CARD_BORDER' => Configuration::get('MINORDER_COLOR_CARD_BORDER') ?: '#eeeeee',
            'MINORDER_COLOR_BUTTON_TEXT' => Configuration::get('MINORDER_COLOR_BUTTON_TEXT') ?: '#ffffff',
        ];
    }

    /**
     * Add CSS and JS to header
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/minordertaxincluded.css');
        $this->context->controller->addJS($this->_path . 'views/js/minordertaxincluded.js');

        // Pass configuration to JavaScript, including current page info
        $controller = Tools::getValue('controller');
        Media::addJsDef([
            'minorder_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax'),
            'minorder_free_shipping' => (float) Configuration::get('MINORDER_FREE_SHIPPING_AMOUNT'),
            'minorder_min_order' => (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT'),
            'minorder_currency_sign' => $this->context->currency->sign,
            'minorder_cart_total' => $this->getCartTotalTaxIncluded(),
            'minorder_current_controller' => $controller,
        ]);

        // Add dynamic color CSS
        return $this->generateDynamicCSS();
    }

    /**
     * Generate dynamic CSS based on color configuration
     */
    protected function generateDynamicCSS()
    {
        $colorPrimary = Configuration::get('MINORDER_COLOR_PRIMARY') ?: '#28a745';
        $colorSecondary = Configuration::get('MINORDER_COLOR_SECONDARY') ?: '#ff6b35';
        $colorWarning = Configuration::get('MINORDER_COLOR_WARNING') ?: '#ffc107';
        $colorProgressBg = Configuration::get('MINORDER_COLOR_PROGRESS_BG') ?: '#e9ecef';
        $colorButton = Configuration::get('MINORDER_COLOR_BUTTON') ?: '#28a745';
        $colorButtonText = Configuration::get('MINORDER_COLOR_BUTTON_TEXT') ?: '#ffffff';
        $colorBestseller = Configuration::get('MINORDER_COLOR_BESTSELLER') ?: '#dc3545';
        $colorPriceOld = Configuration::get('MINORDER_COLOR_PRICE_OLD') ?: '#999999';
        $colorText = Configuration::get('MINORDER_COLOR_TEXT') ?: '#333333';
        $colorCardBg = Configuration::get('MINORDER_COLOR_CARD_BG') ?: '#ffffff';
        $colorCardBorder = Configuration::get('MINORDER_COLOR_CARD_BORDER') ?: '#eeeeee';

        // Calculate darker shade for hover
        $colorButtonHover = $this->adjustBrightness($colorButton, -20);
        $colorPrimaryLight = $this->adjustBrightness($colorPrimary, 40);
        $colorBestsellerHover = $this->adjustBrightness($colorBestseller, -15);
        $colorCardBorderHover = $this->adjustBrightness($colorCardBorder, -15);

        $css = "
        <style>
        /* Dynamic colors for Min Order module */
        :root {
            --minorder-primary: {$colorPrimary};
            --minorder-secondary: {$colorSecondary};
            --minorder-warning: {$colorWarning};
            --minorder-progress-bg: {$colorProgressBg};
            --minorder-button: {$colorButton};
            --minorder-button-hover: {$colorButtonHover};
            --minorder-primary-light: {$colorPrimaryLight};
            --minorder-bestseller: {$colorBestseller};
            --minorder-price-old: {$colorPriceOld};
            --minorder-text: {$colorText};
            --minorder-card-bg: {$colorCardBg};
            --minorder-card-border: {$colorCardBorder};
            --minorder-button-text: {$colorButtonText};
        }

        .minorder-success {
            background-color: {$colorPrimaryLight} !important;
            border-color: {$colorPrimary} !important;
        }
        .minorder-success .minorder-icon,
        .minorder-target {
            color: {$colorPrimary} !important;
        }

        .minorder-progress-bar {
            background-color: {$colorProgressBg} !important;
        }
        .minorder-progress-fill {
            background: linear-gradient(90deg, {$colorPrimary} 0%, {$colorPrimaryLight} 100%) !important;
        }

        .minorder-icon-warning {
            color: {$colorWarning} !important;
        }

        .minorder-suggested-price {
            color: {$colorSecondary} !important;
        }
        .minorder-suggested-title .minorder-icon {
            color: {$colorSecondary} !important;
        }

        .minorder-add-btn {
            background: linear-gradient(135deg, {$colorButton} 0%, {$colorButtonHover} 100%) !important;
            color: {$colorButtonText} !important;
        }
        .minorder-add-btn .minorder-icon,
        .minorder-add-btn .minorder-add-text {
            color: {$colorButtonText} !important;
        }
        .minorder-add-btn:hover {
            background: linear-gradient(135deg, {$colorButtonHover} 0%, {$colorButton} 100%) !important;
        }

        .minorder-suggested-item.minorder-reaches-min {
            border-color: {$colorPrimary} !important;
            box-shadow: 0 0 0 1px {$colorPrimary} !important;
        }
        .minorder-suggested-item.minorder-reaches-min::before {
            background: linear-gradient(90deg, {$colorPrimary}, {$colorPrimaryLight}) !important;
        }
        .minorder-badge-reaches {
            background: linear-gradient(135deg, {$colorPrimary} 0%, {$colorPrimaryLight} 100%) !important;
        }

        /* Bestseller badge */
        .minorder-badge-bestseller {
            background: linear-gradient(135deg, {$colorBestseller} 0%, {$colorBestsellerHover} 100%) !important;
        }

        /* Old/strikethrough price */
        .minorder-suggested-price-regular {
            color: {$colorPriceOld} !important;
        }

        /* Text color */
        .minorder-suggested-name,
        .minorder-suggested-title,
        .minorder-message {
            color: {$colorText} !important;
        }

        /* Product card styling */
        .minorder-suggested-item {
            background: {$colorCardBg} !important;
            border-color: {$colorCardBorder} !important;
        }
        .minorder-suggested-image {
            background: {$colorCardBg} !important;
        }
        .minorder-suggested-item:hover {
            border-color: {$colorCardBorderHover} !important;
        }
        </style>
        ";

        return $css;
    }

    /**
     * Adjust color brightness
     */
    protected function adjustBrightness($hex, $percent)
    {
        // PHP 8.x compatibility: ensure $hex is a string
        $hex = ltrim((string) $hex, '#');

        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Display progress bar in shopping cart page (footer)
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        return $this->renderMinOrderProgressBarOnce();
    }

    /**
     * Display progress bar in shopping cart (main hook)
     */
    public function hookDisplayShoppingCart($params)
    {
        return $this->renderMinOrderProgressBarOnce();
    }

    /**
     * Display progress bar in cross-selling area (PS 8.x compatibility)
     */
    public function hookDisplayCrossSellingShoppingCart($params)
    {
        return $this->renderMinOrderProgressBarOnce();
    }

    /**
     * Display progress bar in reassurance area (Classic theme)
     */
    public function hookDisplayReassurance($params)
    {
        // Only show on cart page
        $controller = (string) Tools::getValue('controller');
        if ($controller === 'cart') {
            return $this->renderMinOrderProgressBarOnce();
        }
        return '';
    }

    /**
     * Display progress bar after body opening tag (fallback)
     */
    public function hookDisplayAfterBodyOpeningTag($params)
    {
        // Only show on cart page as floating bar
        $controller = (string) Tools::getValue('controller');
        if ($controller === 'cart') {
            $html = $this->renderMinOrderProgressBarOnce();
            if (!empty($html)) {
                // Wrap in a fixed container for visibility
                return '<div class="minorder-floating-wrapper" style="position:relative;z-index:999;">' . $html . '</div>';
            }
        }
        return '';
    }

    /**
     * Render progress bar only once (prevents duplicates from multiple hooks)
     */
    protected function renderMinOrderProgressBarOnce()
    {
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;
        return $this->renderMinOrderProgressBar();
    }

    /**
     * Render the unified progress bar for minimum order
     */
    protected function renderMinOrderProgressBar()
    {
        try {
            $minOrderAmount = (float) Configuration::get('MINORDER_MIN_ORDER_AMOUNT');

            if ($minOrderAmount <= 0) {
                return '';
            }

            $cartTotal = $this->getCartTotalTaxIncluded();
            $remaining = max(0, $minOrderAmount - $cartTotal);
            $percentage = min(100, ($cartTotal / $minOrderAmount) * 100);
            $minOrderReached = $cartTotal >= $minOrderAmount;

            // Get suggested products if minimum not reached
            $suggestedProducts = [];
            $showSuggested = (bool) Configuration::get('MINORDER_SHOW_SUGGESTED');
            if (!$minOrderReached && $showSuggested) {
                $suggestedProducts = $this->getSuggestedProducts($remaining);
                // Ensure it's always an array
                if (!is_array($suggestedProducts)) {
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

            return $this->display(__FILE__, 'views/templates/hook/min_order_progress.tpl');
        } catch (Exception $e) {
            // Log error and return empty to prevent breaking the page
            PrestaShopLogger::addLog(
                'MinOrderTaxIncluded error: ' . $e->getMessage(),
                3,
                null,
                'Module',
                $this->id
            );
            return '';
        }
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
        // Skip if this is a payment gateway callback/validation
        if ($this->isPaymentCallback()) {
            return;
        }

        $controller = Tools::getValue('controller');

        // Block access to checkout if minimum order not reached
        if ($controller === 'order' || $controller === 'orderopc') {
            // Don't block if cart is empty (order might already be processed)
            if (!$this->hasCartProducts()) {
                return;
            }

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
     * Check if current request is a payment gateway callback
     * This prevents blocking PayPal, Nexi, Stripe and other payment returns
     */
    protected function isPaymentCallback()
    {
        // PHP 8.x compatibility: cast to string to avoid null/false issues
        $controller = (string) Tools::getValue('controller');
        $module = (string) Tools::getValue('module');
        $fc = (string) Tools::getValue('fc');

        // Payment module controllers (fc=module means it's a module front controller)
        if ($fc === 'module') {
            return true;
        }

        // Common payment validation/return controller names
        $paymentControllers = [
            'validation',
            'confirm',
            'return',
            'cancel',
            'notify',
            'ipn',
            'webhook',
            'callback',
            'success',
            'error',
            'payment',
            'paymentreturn',
            'orderconfirmation',
        ];

        if ($controller !== '' && in_array(strtolower($controller), $paymentControllers)) {
            return true;
        }

        // Check for specific payment module names in controller
        $paymentModules = ['paypal', 'nexi', 'stripe', 'braintree', 'mollie', 'adyen', 'klarna', 'satispay', 'scalapay'];
        foreach ($paymentModules as $pm) {
            if (($controller !== '' && stripos($controller, $pm) !== false) ||
                ($module !== '' && stripos($module, $pm) !== false)) {
                return true;
            }
        }

        // Check if we're coming from a payment gateway (referer check)
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        $paymentDomains = ['paypal.com', 'nexi.it', 'stripe.com', 'braintree', 'mollie.com'];
        foreach ($paymentDomains as $domain) {
            if ($referer !== '' && stripos($referer, $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cart has products
     */
    protected function hasCartProducts()
    {
        $cart = $this->context->cart;
        return Validate::isLoadedObject($cart) && $cart->nbProducts() > 0;
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

    /**
     * Get suggested products to reach minimum order
     * Uses smart logic: products bought together + bestsellers, sorted by price proximity
     */
    public function getSuggestedProducts($remainingAmount = null)
    {
        if (!Configuration::get('MINORDER_SHOW_SUGGESTED')) {
            return [];
        }

        $count = (int) Configuration::get('MINORDER_SUGGESTED_COUNT');
        $count = max(1, min(8, $count));

        if ($remainingAmount === null) {
            $remainingAmount = $this->getRemainingForMinimumOrder();
        }

        if ($remainingAmount <= 0) {
            return [];
        }

        $cart = $this->context->cart;
        $idLang = $this->context->language->id;
        $useTaxIncl = (bool) Configuration::get('MINORDER_USE_TAX_INCL');

        // Get product IDs already in cart to exclude them
        $cartProductIds = [];
        if (Validate::isLoadedObject($cart)) {
            $cartProducts = $cart->getProducts();
            foreach ($cartProducts as $product) {
                $cartProductIds[] = (int) $product['id_product'];
            }
        }

        // Step 1: Get products frequently bought together with cart products
        $relatedProductIds = $this->getFrequentlyBoughtTogether($cartProductIds, $count * 2);

        // Step 2: If not enough, add bestsellers
        if (count($relatedProductIds) < $count * 2) {
            $bestsellerIds = $this->getBestsellerProductIds(
                $count * 2 - count($relatedProductIds),
                array_merge($cartProductIds, $relatedProductIds)
            );
            $relatedProductIds = array_merge($relatedProductIds, $bestsellerIds);
        }

        // Step 3: If still not enough, add newest products
        if (empty($relatedProductIds)) {
            $relatedProductIds = $this->getNewestProductIds($count, $cartProductIds);
        }

        if (empty($relatedProductIds)) {
            return [];
        }

        // Get bestseller IDs to mark them
        $bestsellerProductIds = $this->getBestsellerProductIds($count * 3, $cartProductIds);

        // Step 4: Build product data with prices
        $suggestedProducts = [];
        foreach ($relatedProductIds as $productId) {
            $product = new Product((int) $productId, true, $idLang);

            if (!Validate::isLoadedObject($product)) {
                continue;
            }

            // Get current price (with any discount applied)
            $price = $useTaxIncl ? $product->getPrice(true) : $product->getPrice(false);

            // Skip if price is 0
            if ($price <= 0) {
                continue;
            }

            // Get regular price (without discount)
            $regularPrice = $useTaxIncl
                ? $product->getPrice(true, null, 6, null, false, false)
                : $product->getPrice(false, null, 6, null, false, false);

            // Check if product has a discount
            $hasDiscount = ($regularPrice > $price && ($regularPrice - $price) > 0.01);

            // Check if product is a bestseller
            $isBestseller = in_array($productId, $bestsellerProductIds);

            $cover = Product::getCover($product->id);
            $imageUrl = '';
            if ($cover) {
                // PS 8.x compatibility: getFormattedName is deprecated
                $imageType = $this->getImageTypeName('home');
                $imageUrl = $this->context->link->getImageLink(
                    $product->link_rewrite,
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
                'is_bestseller' => $isBestseller,
                'link' => $this->context->link->getProductLink($product),
                'image_url' => $imageUrl,
                'description_short' => strip_tags((string) $product->description_short),
                'reaches_minimum' => ($price >= $remainingAmount),
                'price_distance' => abs($price - $remainingAmount),
            ];
        }

        // Step 5: Sort by price proximity to remaining amount (closest first)
        usort($suggestedProducts, function ($a, $b) {
            return $a['price_distance'] <=> $b['price_distance'];
        });

        // Return only the requested count
        return array_slice($suggestedProducts, 0, $count);
    }

    /**
     * Get products frequently bought together with the cart products
     * This is the "chi ha comprato X ha comprato anche Y" logic
     */
    protected function getFrequentlyBoughtTogether($cartProductIds, $limit = 8)
    {
        if (empty($cartProductIds)) {
            return [];
        }

        $idShop = $this->context->shop->id;

        // Find orders that contain the cart products, then get other products from those orders
        // Ordered by frequency (how many times they appear together)
        $sql = new DbQuery();
        $sql->select('od2.product_id, COUNT(DISTINCT od2.id_order) as frequency');
        $sql->from('order_detail', 'od1');
        $sql->innerJoin('order_detail', 'od2', 'od2.id_order = od1.id_order AND od2.product_id != od1.product_id');
        $sql->innerJoin('product', 'p', 'p.id_product = od2.product_id');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->innerJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = ' . (int) $idShop);

        // Cart products are the source
        $sql->where('od1.product_id IN (' . implode(',', array_map('intval', $cartProductIds)) . ')');

        // Exclude cart products from results
        $sql->where('od2.product_id NOT IN (' . implode(',', array_map('intval', $cartProductIds)) . ')');

        // Only active, available products with stock
        $sql->where('ps.active = 1');
        $sql->where('p.available_for_order = 1');
        $sql->where('sa.quantity > 0');

        // Group and order by frequency
        $sql->groupBy('od2.product_id');
        $sql->orderBy('frequency DESC');
        $sql->limit((int) $limit);

        $results = Db::getInstance()->executeS($sql);

        if (empty($results)) {
            return [];
        }

        return array_column($results, 'product_id');
    }

    /**
     * Get bestseller product IDs
     */
    protected function getBestsellerProductIds($limit, $excludeIds = [])
    {
        $idShop = $this->context->shop->id;

        $sql = new DbQuery();
        $sql->select('p.id_product, IFNULL(SUM(od.product_quantity), 0) as total_sold');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->innerJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = ' . (int) $idShop);
        $sql->leftJoin('order_detail', 'od', 'od.product_id = p.id_product');

        $sql->where('ps.active = 1');
        $sql->where('p.available_for_order = 1');
        $sql->where('sa.quantity > 0');

        if (!empty($excludeIds)) {
            $sql->where('p.id_product NOT IN (' . implode(',', array_map('intval', $excludeIds)) . ')');
        }

        $sql->groupBy('p.id_product');
        $sql->orderBy('total_sold DESC, p.id_product DESC');
        $sql->limit((int) $limit);

        $results = Db::getInstance()->executeS($sql);

        if (empty($results)) {
            return [];
        }

        return array_column($results, 'id_product');
    }

    /**
     * Get newest product IDs as fallback
     */
    protected function getNewestProductIds($limit, $excludeIds = [])
    {
        $idShop = $this->context->shop->id;

        $sql = new DbQuery();
        $sql->select('p.id_product');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->innerJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = ' . (int) $idShop);

        $sql->where('ps.active = 1');
        $sql->where('p.available_for_order = 1');
        $sql->where('sa.quantity > 0');

        if (!empty($excludeIds)) {
            $sql->where('p.id_product NOT IN (' . implode(',', array_map('intval', $excludeIds)) . ')');
        }

        $sql->orderBy('p.date_add DESC');
        $sql->limit((int) $limit);

        $results = Db::getInstance()->executeS($sql);

        if (empty($results)) {
            return [];
        }

        return array_column($results, 'id_product');
    }

    /**
     * Get image type name with PS 8.x compatibility
     * In PS 8.x, ImageType::getFormattedName() is deprecated
     */
    protected function getImageTypeName($type)
    {
        // PrestaShop 8.x compatibility
        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            // In PS 8.x, use the type name directly or query the database
            $sql = new DbQuery();
            $sql->select('name');
            $sql->from('image_type');
            $sql->where('name LIKE \'%' . pSQL($type) . '%\'');
            $sql->orderBy('width DESC');
            $sql->limit(1);

            $result = Db::getInstance()->getValue($sql);

            if ($result) {
                return $result;
            }

            // Fallback: return common PS 8 image type names
            $ps8Types = [
                'home' => 'home_default',
                'small' => 'small_default',
                'medium' => 'medium_default',
                'large' => 'large_default',
                'cart' => 'cart_default',
            ];

            return isset($ps8Types[$type]) ? $ps8Types[$type] : 'home_default';
        }

        // PrestaShop 1.7.x - use the old method
        if (method_exists('ImageType', 'getFormattedName')) {
            return ImageType::getFormattedName($type);
        }

        // Ultimate fallback
        return $type . '_default';
    }
}
