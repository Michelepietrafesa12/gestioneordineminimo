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
            this.checkMinimumOrder();
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
                            // Replace entire container with new HTML
                            if (self.container && response.html) {
                                var wrapper = self.container.parentNode;
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = response.html;
                                var newContainer = tempDiv.querySelector('#minorder-progress-container');

                                if (newContainer) {
                                    self.container.parentNode.replaceChild(newContainer, self.container);
                                    self.container = newContainer;
                                } else if (response.html.trim()) {
                                    self.container.outerHTML = response.html;
                                    self.container = document.getElementById('minorder-progress-container');
                                }
                            }

                            // Update checkout buttons
                            self.updateCheckoutButton(!response.min_order_reached);

                            // Also update modal if open
                            var modal = document.querySelector('.modal.show, .modal.in, #blockcart-modal');
                            if (modal) {
                                self.updateModalProgress(modal);
                            }
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

            xhr.send('action=getProgressHtml&ajax=1');
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
