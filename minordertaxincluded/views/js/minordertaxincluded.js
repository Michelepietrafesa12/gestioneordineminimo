/**
 * Min Order Tax Included - JavaScript
 * Handles dynamic updates of the progress bar when cart changes
 */

(function() {
    'use strict';

    var MinOrderProgress = {
        container: null,
        minOrderAmount: 0,
        currencySign: '€',

        /**
         * Initialize the module
         */
        init: function() {
            this.container = document.getElementById('minorder-progress-container');

            if (typeof minorder_min_order !== 'undefined') {
                this.minOrderAmount = parseFloat(minorder_min_order);
            }

            if (typeof minorder_currency_sign !== 'undefined') {
                this.currencySign = minorder_currency_sign;
            }

            this.bindEvents();

            // If container doesn't exist and we're on cart page, inject it via JS
            if (!this.container && this.minOrderAmount > 0 && this.isCartPage()) {
                this.injectProgressBarIntoPage();
            } else if (this.isCheckoutPage()) {
                // On checkout page, only check minimum order (block if needed)
                // Don't show any UI - user is already past the cart
                this.checkMinimumOrderSilent();
            } else {
                this.checkMinimumOrder();
            }
        },

        /**
         * Check if current page is cart page
         */
        isCartPage: function() {
            var currentController = typeof minorder_current_controller !== 'undefined' ? minorder_current_controller : '';
            return currentController === 'cart';
        },

        /**
         * Check if current page is order/checkout page
         */
        isCheckoutPage: function() {
            var currentController = typeof minorder_current_controller !== 'undefined' ? minorder_current_controller : '';
            return ['order', 'checkout', 'orderopc'].indexOf(currentController) !== -1;
        },

        /**
         * Inject progress bar into cart page via JavaScript
         * This is used when theme doesn't call standard PrestaShop hooks
         */
        injectProgressBarIntoPage: function() {
            var self = this;

            console.log('MinOrder: Injecting progress bar via JS');

            // Find best location to inject
            var targetSelectors = [
                '.cart-grid-body',           // Classic theme
                '#main .cart-container',     // Some themes
                '.cart-detailed-totals',     // Before totals
                '.cart-summary',             // Cart summary area
                '#content-wrapper .cart',    // Generic cart
                '#main'                       // Fallback to main content
            ];

            var target = null;
            var insertPosition = 'beforeend'; // default: append inside

            for (var i = 0; i < targetSelectors.length; i++) {
                target = document.querySelector(targetSelectors[i]);
                if (target) {
                    // Special handling for certain selectors
                    if (targetSelectors[i] === '.cart-detailed-totals' ||
                        targetSelectors[i] === '.cart-summary') {
                        insertPosition = 'beforebegin'; // insert before
                    }
                    console.log('MinOrder: Found target:', targetSelectors[i]);
                    break;
                }
            }

            if (!target) {
                console.log('MinOrder: No suitable target found, trying body');
                target = document.body;
                insertPosition = 'afterbegin';
            }

            // Fetch progress bar HTML via AJAX
            this.fetchAndInjectProgressBar(target, insertPosition);
        },

        /**
         * Fetch progress data from server and inject progress bar via JS
         */
        fetchAndInjectProgressBar: function(target, insertPosition) {
            var self = this;

            if (typeof minorder_ajax_url === 'undefined') {
                console.log('MinOrder: AJAX URL not defined, creating inline');
                this.createInlineProgressBar(target, insertPosition);
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', minorder_ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                console.log('MinOrder: AJAX response status:', xhr.status);
                console.log('MinOrder: AJAX raw response:', xhr.responseText.substring(0, 500));

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log('MinOrder: Parsed response - success:', response.success, 'products:', (response.suggested_products || []).length);
                        if (response.success) {
                            // Use data to create HTML via JS
                            var cartTotal = parseFloat(response.cart_total) || 0;
                            var remaining = parseFloat(response.remaining) || 0;
                            var percentage = parseFloat(response.percentage) || 0;
                            var minOrderReached = response.min_order_reached;
                            var suggestedProducts = response.suggested_products || [];
                            var showSuggested = response.show_suggested && suggestedProducts.length > 0;

                            // Update currency sign if provided
                            if (response.currency_sign) {
                                self.currencySign = response.currency_sign;
                            }

                            // Create full HTML with suggested products
                            var html = self.createFullProgressBarWithProductsHtml(
                                cartTotal, remaining, percentage, minOrderReached,
                                showSuggested, suggestedProducts
                            );

                            // Create wrapper and insert
                            var wrapper = document.createElement('div');
                            wrapper.className = 'minorder-injected-wrapper';
                            wrapper.style.cssText = 'margin: 20px 0; clear: both;';
                            wrapper.innerHTML = html;

                            if (insertPosition === 'beforebegin') {
                                target.parentNode.insertBefore(wrapper, target);
                            } else if (insertPosition === 'afterbegin') {
                                target.insertBefore(wrapper, target.firstChild);
                            } else {
                                target.appendChild(wrapper);
                            }

                            // Update container reference
                            self.container = document.getElementById('minorder-progress-container');

                            // Update checkout buttons
                            self.updateCheckoutButton(!minOrderReached);

                            console.log('MinOrder: Progress bar injected with ' + suggestedProducts.length + ' suggested products');
                        } else {
                            console.log('MinOrder: AJAX returned error:', response.error || 'success=false');
                            self.createInlineProgressBar(target, insertPosition);
                        }
                    } catch (e) {
                        console.warn('MinOrder: Error parsing AJAX response', e, xhr.responseText.substring(0, 200));
                        self.createInlineProgressBar(target, insertPosition);
                    }
                } else {
                    console.warn('MinOrder: AJAX error', xhr.status);
                    self.createInlineProgressBar(target, insertPosition);
                }
            };

            xhr.onerror = function() {
                console.warn('MinOrder: AJAX request failed');
                self.createInlineProgressBar(target, insertPosition);
            };

            xhr.send('action=getProgressData&ajax=1');
        },

        /**
         * Create inline progress bar without AJAX (fallback)
         */
        createInlineProgressBar: function(target, insertPosition) {
            var cartTotal = typeof minorder_cart_total !== 'undefined' ? parseFloat(minorder_cart_total) : 0;
            var remaining = Math.max(0, this.minOrderAmount - cartTotal);
            var percentage = Math.min(100, (cartTotal / this.minOrderAmount) * 100);
            var minOrderReached = cartTotal >= this.minOrderAmount;

            var wrapper = document.createElement('div');
            wrapper.className = 'minorder-injected-wrapper';
            wrapper.style.cssText = 'margin: 20px 0; clear: both;';
            wrapper.innerHTML = this.createFullProgressBarHtml(cartTotal, remaining, percentage, minOrderReached);

            if (insertPosition === 'beforebegin') {
                target.parentNode.insertBefore(wrapper, target);
            } else if (insertPosition === 'afterbegin') {
                target.insertBefore(wrapper, target.firstChild);
            } else {
                target.appendChild(wrapper);
            }

            this.container = document.getElementById('minorder-progress-container');
            this.updateCheckoutButton(!minOrderReached);

            console.log('MinOrder: Inline progress bar created');
        },

        /**
         * Create full progress bar HTML with container ID
         */
        createFullProgressBarHtml: function(cartTotal, remaining, percentage, minOrderReached) {
            var html = '<div class="minorder-progress-container" id="minorder-progress-container" ' +
                'data-min-order="' + this.minOrderAmount + '" ' +
                'data-cart-total="' + cartTotal + '" ' +
                'data-remaining="' + remaining + '">';

            if (minOrderReached) {
                html += '<div class="minorder-success">' +
                    '<span class="minorder-icon">&#10003;</span>' +
                    '<span>Ordine minimo raggiunto! Puoi procedere al checkout.</span>' +
                    '</div>';
            } else {
                html += '<div class="minorder-info">' +
                    '<p class="minorder-message">' +
                    '<span class="minorder-icon minorder-icon-warning">&#9888;</span>' +
                    '<span>Ordine minimo: <strong>' + this.formatCurrency(this.minOrderAmount) + '</strong> &mdash; ' +
                    'Ti mancano <strong class="minorder-remaining-amount">' + this.formatCurrency(remaining) + '</strong></span>' +
                    '</p></div>' +
                    '<div class="minorder-progress-bar-wrapper">' +
                    '<div class="minorder-progress-bar">' +
                    '<div class="minorder-progress-fill" style="width: ' + percentage + '%;">' +
                    '<span class="minorder-progress-text">' + Math.round(percentage) + '%</span>' +
                    '</div></div>' +
                    '<div class="minorder-progress-labels">' +
                    '<span class="minorder-current">' + this.formatCurrency(cartTotal) + '</span>' +
                    '<span class="minorder-target">' + this.formatCurrency(this.minOrderAmount) + '</span>' +
                    '</div></div>';
            }

            html += '</div>';
            return html;
        },

        /**
         * Create full progress bar HTML with suggested products
         */
        createFullProgressBarWithProductsHtml: function(cartTotal, remaining, percentage, minOrderReached, showSuggested, suggestedProducts) {
            var html = '<div class="minorder-progress-container" id="minorder-progress-container" ' +
                'data-min-order="' + this.minOrderAmount + '" ' +
                'data-cart-total="' + cartTotal + '" ' +
                'data-remaining="' + remaining + '">';

            if (minOrderReached) {
                html += '<div class="minorder-success">' +
                    '<span class="minorder-icon">&#10003;</span>' +
                    '<span>Ordine minimo raggiunto! Puoi procedere al checkout.</span>' +
                    '</div>';
            } else {
                html += '<div class="minorder-info">' +
                    '<p class="minorder-message">' +
                    '<span class="minorder-icon minorder-icon-warning">&#9888;</span>' +
                    '<span>Ordine minimo: <strong>' + this.formatCurrency(this.minOrderAmount) + '</strong> &mdash; ' +
                    'Ti mancano <strong class="minorder-remaining-amount">' + this.formatCurrency(remaining) + '</strong></span>' +
                    '</p></div>' +
                    '<div class="minorder-progress-bar-wrapper">' +
                    '<div class="minorder-progress-bar">' +
                    '<div class="minorder-progress-fill" style="width: ' + percentage + '%;">' +
                    '<span class="minorder-progress-text">' + Math.round(percentage) + '%</span>' +
                    '</div></div>' +
                    '<div class="minorder-progress-labels">' +
                    '<span class="minorder-current">' + this.formatCurrency(cartTotal) + '</span>' +
                    '<span class="minorder-target">' + this.formatCurrency(this.minOrderAmount) + '</span>' +
                    '</div></div>';

                // Add suggested products
                if (showSuggested && suggestedProducts && suggestedProducts.length > 0) {
                    html += '<div class="minorder-suggested-products">' +
                        '<h4 class="minorder-suggested-title">' +
                        '<span class="minorder-icon">&#128722;</span> ' +
                        'Aggiungi questi prodotti per raggiungere l\'ordine minimo:' +
                        '</h4>' +
                        '<div class="minorder-suggested-grid">';

                    for (var i = 0; i < suggestedProducts.length; i++) {
                        var product = suggestedProducts[i];
                        var itemClass = 'minorder-suggested-item';
                        if (product.reaches_minimum) {
                            itemClass += ' minorder-reaches-min';
                        }

                        html += '<div class="' + itemClass + '">' +
                            '<a href="' + this.escapeHtml(product.link) + '" class="minorder-suggested-link">';

                        if (product.image_url) {
                            html += '<div class="minorder-suggested-image">' +
                                '<img src="' + this.escapeHtml(product.image_url) + '" alt="' + this.escapeHtml(product.name) + '" loading="lazy">';

                            if (product.reaches_minimum) {
                                html += '<span class="minorder-badge-reaches">Raggiungi la soglia!</span>';
                            }
                            if (product.is_bestseller) {
                                html += '<span class="minorder-badge-bestseller">Più acquistato</span>';
                            }
                            html += '</div>';
                        }

                        html += '<div class="minorder-suggested-info">' +
                            '<span class="minorder-suggested-name">' + this.escapeHtml(this.truncate(product.name, 40)) + '</span>' +
                            '<div class="minorder-suggested-prices">';

                        if (product.has_discount) {
                            html += '<span class="minorder-suggested-price-regular">' + this.escapeHtml(product.regular_price_formatted) + '</span>';
                        }
                        html += '<span class="minorder-suggested-price">' + this.escapeHtml(product.price_formatted) + '</span>' +
                            '</div></div></a>' +
                            '<button type="button" class="minorder-add-btn" ' +
                            'data-id-product="' + product.id_product + '" ' +
                            'data-id-product-attribute="0" ' +
                            'data-minimal-quantity="1" ' +
                            'title="Aggiungi al carrello">' +
                            '<span class="minorder-icon">&#128722;</span> ' +
                            '<span class="minorder-add-text">Aggiungi</span>' +
                            '</button></div>';
                    }

                    html += '</div></div>';
                }
            }

            html += '</div>';
            return html;
        },

        /**
         * Escape HTML special characters
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Truncate text to specified length
         */
        truncate: function(text, length) {
            if (!text) return '';
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        },

        /**
         * Bind events for cart updates
         */
        bindEvents: function() {
            var self = this;

            // Listen for PrestaShop cart update events
            if (typeof prestashop !== 'undefined') {
                prestashop.on('updateCart', function(event) {
                    self.onCartUpdate(event);
                });

                prestashop.on('updatedCart', function(event) {
                    self.onCartUpdate(event);
                    // Inject progress bar into modal after cart update
                    setTimeout(function() {
                        self.injectIntoModal();
                    }, 500);
                });
            }

            // Watch for modal opening
            this.observeModal();
        },

        /**
         * Observe for cart modal opening
         */
        observeModal: function() {
            var self = this;

            // Use MutationObserver to detect when modal is added to DOM
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            // Check if it's a modal or contains a modal
                            var modal = node.classList && node.classList.contains('modal') ? node : node.querySelector && node.querySelector('.modal');
                            if (modal) {
                                setTimeout(function() {
                                    self.injectIntoModal();
                                }, 100);
                            }
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        },

        /**
         * Inject progress bar into cart modal
         */
        injectIntoModal: function() {
            if (this.minOrderAmount <= 0) {
                return;
            }

            // Find the cart modal
            var modal = document.querySelector('.modal.show, .modal.in, #blockcart-modal, .blockcart-modal');
            if (!modal) {
                return;
            }

            // Check if already injected
            if (modal.querySelector('.minorder-modal-progress')) {
                this.updateModalProgress(modal);
                return;
            }

            // Find the best place to inject (before checkout button)
            var targetSelectors = [
                '.cart-content-btn',
                '.modal-body .cart-content',
                '.modal-body'
            ];

            var target = null;
            for (var i = 0; i < targetSelectors.length; i++) {
                target = modal.querySelector(targetSelectors[i]);
                if (target) break;
            }

            if (!target) {
                return;
            }

            // Get cart total from modal or AJAX
            var cartTotal = this.getCartTotalFromModal(modal);
            var remaining = Math.max(0, this.minOrderAmount - cartTotal);
            var percentage = Math.min(100, (cartTotal / this.minOrderAmount) * 100);
            var minOrderReached = cartTotal >= this.minOrderAmount;

            // Create progress bar HTML
            var progressHtml = this.createProgressBarHtml(cartTotal, remaining, percentage, minOrderReached);

            // Insert before the target
            var wrapper = document.createElement('div');
            wrapper.className = 'minorder-modal-progress';
            wrapper.innerHTML = progressHtml;

            if (target.classList.contains('cart-content-btn')) {
                target.parentNode.insertBefore(wrapper, target);
            } else {
                target.appendChild(wrapper);
            }

            // Update checkout button state in modal
            this.updateModalCheckoutButton(modal, !minOrderReached);
        },

        /**
         * Update existing progress bar in modal
         */
        updateModalProgress: function(modal) {
            var progressContainer = modal.querySelector('.minorder-modal-progress');
            if (!progressContainer) return;

            var cartTotal = this.getCartTotalFromModal(modal);
            var remaining = Math.max(0, this.minOrderAmount - cartTotal);
            var percentage = Math.min(100, (cartTotal / this.minOrderAmount) * 100);
            var minOrderReached = cartTotal >= this.minOrderAmount;

            progressContainer.innerHTML = this.createProgressBarHtml(cartTotal, remaining, percentage, minOrderReached);

            // Update checkout button state
            this.updateModalCheckoutButton(modal, !minOrderReached);
        },

        /**
         * Get cart total from modal
         */
        getCartTotalFromModal: function(modal) {
            var selectors = [
                '.product-total .value',
                '.cart-subtotals .value',
                '.cart-total .value'
            ];

            for (var i = 0; i < selectors.length; i++) {
                var el = modal.querySelector(selectors[i]);
                if (el) {
                    var text = el.textContent || '';
                    var value = parseFloat(text.replace(/[^\d,.-]/g, '').replace(',', '.'));
                    if (!isNaN(value) && value > 0) {
                        return value;
                    }
                }
            }

            return this.getCartTotalFromDOM();
        },

        /**
         * Update checkout button in modal
         */
        updateModalCheckoutButton: function(modal, disabled) {
            var checkoutBtn = modal.querySelector('a[href*="order"], a[href*="checkout"], .checkout');
            if (!checkoutBtn) return;

            if (disabled) {
                checkoutBtn.classList.add('disabled', 'minorder-blocked');
                checkoutBtn.style.pointerEvents = 'none';
                checkoutBtn.style.opacity = '0.5';
                if (!checkoutBtn.getAttribute('data-original-href')) {
                    checkoutBtn.setAttribute('data-original-href', checkoutBtn.getAttribute('href') || '');
                }
                checkoutBtn.setAttribute('href', 'javascript:void(0);');
            } else {
                checkoutBtn.classList.remove('disabled', 'minorder-blocked');
                checkoutBtn.style.pointerEvents = '';
                checkoutBtn.style.opacity = '';
                var originalHref = checkoutBtn.getAttribute('data-original-href');
                if (originalHref) {
                    checkoutBtn.setAttribute('href', originalHref);
                }
            }
        },

        /**
         * Create progress bar HTML
         */
        createProgressBarHtml: function(cartTotal, remaining, percentage, minOrderReached) {
            if (minOrderReached) {
                return '<div class="minorder-progress-container minorder-compact">' +
                    '<div class="minorder-success">' +
                    '<span class="minorder-icon">&#10003;</span>' +
                    '<span>Ordine minimo raggiunto!</span>' +
                    '</div></div>';
            }

            return '<div class="minorder-progress-container minorder-compact">' +
                '<div class="minorder-info">' +
                '<p class="minorder-message">' +
                '<span class="minorder-icon minorder-icon-warning">&#9888;</span>' +
                '<span>Ordine minimo: <strong>' + this.formatCurrency(this.minOrderAmount) + '</strong> &mdash; ' +
                'Ti mancano <strong class="minorder-remaining-amount">' + this.formatCurrency(remaining) + '</strong></span>' +
                '</p></div>' +
                '<div class="minorder-progress-bar-wrapper">' +
                '<div class="minorder-progress-bar">' +
                '<div class="minorder-progress-fill" style="width: ' + percentage + '%;">' +
                '<span class="minorder-progress-text">' + Math.round(percentage) + '%</span>' +
                '</div></div>' +
                '<div class="minorder-progress-labels">' +
                '<span class="minorder-current">' + this.formatCurrency(cartTotal) + '</span>' +
                '<span class="minorder-target">' + this.formatCurrency(this.minOrderAmount) + '</span>' +
                '</div></div></div>';
        },

        /**
         * Handle cart update event
         */
        onCartUpdate: function(event) {
            var self = this;

            if (this.container) {
                this.container.classList.add('updating');
            }

            // Reload entire section via AJAX to include suggested products
            setTimeout(function() {
                self.reloadProgressSection();
            }, 300);
        },

        /**
         * Check minimum order and block checkout if not reached
         */
        checkMinimumOrder: function() {
            if (this.minOrderAmount <= 0) {
                return;
            }

            var cartTotal = this.getCartTotalFromDOM();
            var isMinReached = cartTotal >= this.minOrderAmount;

            this.updateCheckoutButton(!isMinReached);
            this.updateProgressContainer(cartTotal);
        },

        /**
         * Silent check - only update checkout button, no UI
         * Used on checkout/order pages where we don't want to show the progress bar
         */
        checkMinimumOrderSilent: function() {
            if (this.minOrderAmount <= 0) {
                return;
            }

            var cartTotal = this.getCartTotalFromDOM();
            var isMinReached = cartTotal >= this.minOrderAmount;

            // Only block checkout if not reached - no visual UI
            if (!isMinReached) {
                this.updateCheckoutButton(true);
            }
        },

        /**
         * Update the main progress container
         */
        updateProgressContainer: function(cartTotal) {
            if (!this.container) return;

            var remaining = Math.max(0, this.minOrderAmount - cartTotal);
            var percentage = Math.min(100, (cartTotal / this.minOrderAmount) * 100);
            var minOrderReached = cartTotal >= this.minOrderAmount;

            if (minOrderReached) {
                this.container.innerHTML = '<div class="minorder-success">' +
                    '<span class="minorder-icon">&#10003;</span>' +
                    '<span>Ordine minimo raggiunto! Puoi procedere al checkout.</span></div>';
            } else {
                // Update values in existing HTML
                var remainingEl = this.container.querySelector('.minorder-remaining-amount');
                var progressFill = this.container.querySelector('.minorder-progress-fill');
                var progressText = this.container.querySelector('.minorder-progress-text');
                var currentEl = this.container.querySelector('.minorder-current');

                if (remainingEl) remainingEl.textContent = this.formatCurrency(remaining);
                if (progressFill) progressFill.style.width = percentage + '%';
                if (progressText) progressText.textContent = Math.round(percentage) + '%';
                if (currentEl) currentEl.textContent = this.formatCurrency(cartTotal);
            }

            this.container.classList.remove('updating');
            this.container.classList.add('updated');
            var self = this;
            setTimeout(function() {
                self.container.classList.remove('updated');
            }, 500);
        },

        /**
         * Get cart total from DOM
         */
        getCartTotalFromDOM: function() {
            var selectors = [
                '.cart-total .value',
                '.cart-summary-line.cart-total .value',
                '#cart-subtotal-products .value',
                '.cart-summary-totals .cart-total .value',
                '[data-subtotal-value]'
            ];

            for (var i = 0; i < selectors.length; i++) {
                var el = document.querySelector(selectors[i]);
                if (el) {
                    var text = el.textContent || el.getAttribute('data-subtotal-value') || '';
                    var value = parseFloat(text.replace(/[^\d,.-]/g, '').replace(',', '.'));
                    if (!isNaN(value) && value > 0) {
                        return value;
                    }
                }
            }

            // Try from container data attribute
            if (this.container) {
                var dataTotal = this.container.getAttribute('data-cart-total');
                if (dataTotal) {
                    return parseFloat(dataTotal);
                }
            }

            return 0;
        },

        /**
         * Update checkout button state
         */
        updateCheckoutButton: function(disabled) {
            // Only block buttons on cart-related pages, not on account pages
            var currentController = typeof minorder_current_controller !== 'undefined' ? minorder_current_controller : '';
            var allowedControllers = ['cart', 'order', 'orderopc', 'checkout'];

            if (allowedControllers.indexOf(currentController) === -1) {
                // Not on a cart page - don't block any buttons
                return;
            }

            var checkoutButtons = document.querySelectorAll(
                '.checkout a, ' +
                '.cart-detailed-actions a.btn, ' +
                '.cart-grid-right a.btn-primary, ' +
                '.checkout-button'
            );

            for (var i = 0; i < checkoutButtons.length; i++) {
                var btn = checkoutButtons[i];
                // Skip buttons inside modals - handled separately
                if (btn.closest('.modal')) continue;

                // Skip links to order history/details (account area)
                var href = btn.getAttribute('href') || '';
                if (href.indexOf('order-detail') !== -1 || href.indexOf('history') !== -1) {
                    continue;
                }

                if (disabled) {
                    btn.classList.add('disabled', 'minorder-blocked');
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.5';
                    if (!btn.getAttribute('data-original-href')) {
                        btn.setAttribute('data-original-href', href);
                    }
                    btn.setAttribute('href', 'javascript:void(0);');
                    btn.onclick = function(e) {
                        e.preventDefault();
                        alert('Ordine minimo non raggiunto. Aggiungi altri prodotti al carrello.');
                        return false;
                    };
                } else {
                    btn.classList.remove('disabled', 'minorder-blocked');
                    btn.style.pointerEvents = '';
                    btn.style.opacity = '';
                    var originalHref = btn.getAttribute('data-original-href');
                    if (originalHref) {
                        btn.setAttribute('href', originalHref);
                    }
                    btn.onclick = null;
                }
            }
        },

        /**
         * Reload entire progress section via AJAX (includes suggested products)
         */
        reloadProgressSection: function() {
            var self = this;

            if (typeof minorder_ajax_url === 'undefined') {
                this.refreshProgressBar();
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', minorder_ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var cartTotal = parseFloat(response.cart_total) || 0;
                            var remaining = parseFloat(response.remaining) || 0;
                            var percentage = parseFloat(response.percentage) || 0;
                            var minOrderReached = response.min_order_reached;
                            var suggestedProducts = response.suggested_products || [];
                            var showSuggested = response.show_suggested && suggestedProducts.length > 0;

                            // Update currency sign if provided
                            if (response.currency_sign) {
                                self.currencySign = response.currency_sign;
                            }

                            // Rebuild the container HTML via JavaScript
                            if (self.container) {
                                var html = self.createFullProgressBarWithProductsHtml(
                                    cartTotal, remaining, percentage, minOrderReached,
                                    showSuggested, suggestedProducts
                                );

                                // Replace container content
                                var wrapper = self.container.parentNode;
                                if (wrapper && wrapper.classList.contains('minorder-injected-wrapper')) {
                                    wrapper.innerHTML = html;
                                    self.container = document.getElementById('minorder-progress-container');
                                } else {
                                    self.container.outerHTML = html;
                                    self.container = document.getElementById('minorder-progress-container');
                                }
                            }

                            // Update checkout buttons - CRITICAL: this must always run
                            self.updateCheckoutButton(!minOrderReached);

                            // Also update modal if open
                            var modal = document.querySelector('.modal.show, .modal.in, #blockcart-modal');
                            if (modal) {
                                self.updateModalProgress(modal);
                            }

                            console.log('MinOrder: Progress section reloaded, minOrderReached:', minOrderReached);
                        }
                    } catch (e) {
                        console.warn('MinOrderProgress: Error parsing response', e);
                        self.refreshProgressBar();
                    }
                }
            };

            xhr.onerror = function() {
                self.refreshProgressBar();
            };

            xhr.send('action=getProgressData&ajax=1');
        },

        /**
         * Refresh the progress bar via AJAX (simple update without suggested products)
         */
        refreshProgressBar: function() {
            var self = this;

            if (typeof minorder_ajax_url === 'undefined') {
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', minorder_ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var cartTotal = parseFloat(response.cart_total) || 0;
                            self.updateProgressContainer(cartTotal);
                            self.updateCheckoutButton(!response.min_order_reached);

                            // Also update modal if open
                            var modal = document.querySelector('.modal.show, .modal.in, #blockcart-modal');
                            if (modal) {
                                self.updateModalProgress(modal);
                            }
                        }
                    } catch (e) {
                        console.warn('MinOrderProgress: Error parsing response', e);
                    }
                }
            };

            xhr.send('action=getProgress&ajax=1');
        },

        /**
         * Format currency value
         */
        formatCurrency: function(value) {
            return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + this.currencySign;
        },

        /**
         * Bind suggested products add to cart buttons
         */
        bindSuggestedProductsForms: function() {
            var self = this;
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.minorder-add-btn');
                if (btn && btn.dataset.idProduct) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.handleAddToCart(btn);
                }
            });
        },

        /**
         * Handle add to cart via PrestaShop's native AJAX
         */
        handleAddToCart: function(btn) {
            var self = this;
            var item = btn.closest('.minorder-suggested-item');
            var originalBtnHtml = btn.innerHTML;
            var idProduct = btn.dataset.idProduct;
            var idProductAttribute = btn.dataset.idProductAttribute || 0;
            var qty = btn.dataset.minimalQuantity || 1;

            // Add loading state
            if (item) item.classList.add('loading');
            btn.innerHTML = '<span class="minorder-spinner"></span>';
            btn.disabled = true;

            // Build cart URL for PrestaShop 1.7
            var cartUrl = prestashop.urls.pages.cart || '/carrello';
            var params = {
                add: 1,
                action: 'update',
                ajax: 1,
                qty: qty,
                id_product: idProduct,
                id_product_attribute: idProductAttribute,
                token: prestashop.static_token
            };

            // Build URL with parameters
            var url = cartUrl + '?' + Object.keys(params).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            }).join('&');

            // Send AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function() {
                // Remove loading state
                if (item) item.classList.remove('loading');
                btn.disabled = false;

                var success = false;
                try {
                    var response = JSON.parse(xhr.responseText);
                    success = response.success || xhr.status === 200;
                } catch (e) {
                    success = xhr.status === 200;
                }

                if (success) {
                    // Success - show checkmark
                    btn.innerHTML = '<span style="font-size:16px;">&#10003;</span>';
                    btn.style.background = '#28a745';

                    // Trigger PrestaShop cart update
                    if (typeof prestashop !== 'undefined') {
                        prestashop.emit('updateCart', {
                            reason: {
                                idProduct: idProduct,
                                idProductAttribute: idProductAttribute,
                                linkAction: 'add-to-cart',
                                cart: response ? response.cart : null
                            },
                            resp: response
                        });
                    }

                    // Restore button after delay (progress section already updated by updateCart event)
                    setTimeout(function() {
                        btn.innerHTML = originalBtnHtml;
                        btn.style.background = '';
                    }, 1000);
                } else {
                    // Error
                    btn.innerHTML = '<span style="font-size:16px;">&#10007;</span>';
                    btn.style.background = '#dc3545';
                    setTimeout(function() {
                        btn.innerHTML = originalBtnHtml;
                        btn.style.background = '';
                    }, 1500);
                }
            };

            xhr.onerror = function() {
                if (item) item.classList.remove('loading');
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
                // Fallback: reload page
                window.location.reload();
            };

            xhr.send();
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            MinOrderProgress.init();
            MinOrderProgress.bindSuggestedProductsForms();
        });
    } else {
        MinOrderProgress.init();
        MinOrderProgress.bindSuggestedProductsForms();
    }

    // Make available globally for debugging
    window.MinOrderProgress = MinOrderProgress;

})();
