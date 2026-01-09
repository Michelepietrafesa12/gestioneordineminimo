/**
 * Min Order Tax Included - JavaScript
 * Handles dynamic updates of the progress bar when cart changes
 */

(function() {
    'use strict';

    var MinOrderProgress = {
        container: null,
        freeShippingAmount: 0,
        currencySign: '€',

        /**
         * Initialize the module
         */
        init: function() {
            this.container = document.getElementById('minorder-progress-container');

            if (typeof minorder_free_shipping !== 'undefined') {
                this.freeShippingAmount = parseFloat(minorder_free_shipping);
            }

            if (typeof minorder_currency_sign !== 'undefined') {
                this.currencySign = minorder_currency_sign;
            }

            this.bindEvents();
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
                });
            }

            // Also listen for AJAX completion on cart-related requests
            if (typeof $ !== 'undefined' && $.ajaxComplete) {
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && (
                        settings.url.indexOf('cart') !== -1 ||
                        settings.url.indexOf('checkout') !== -1
                    )) {
                        setTimeout(function() {
                            self.refreshProgressBar();
                        }, 500);
                    }
                });
            }
        },

        /**
         * Handle cart update event
         */
        onCartUpdate: function(event) {
            var self = this;

            if (this.container) {
                this.container.classList.add('updating');
            }

            // Small delay to allow cart totals to update
            setTimeout(function() {
                self.refreshProgressBar();
            }, 300);
        },

        /**
         * Refresh the progress bar via AJAX
         */
        refreshProgressBar: function() {
            var self = this;

            if (typeof minorder_ajax_url === 'undefined') {
                this.updateFromDOM();
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
                            self.updateProgressBar(response);
                        }
                    } catch (e) {
                        console.warn('MinOrderProgress: Error parsing response', e);
                    }
                }

                if (self.container) {
                    self.container.classList.remove('updating');
                    self.container.classList.add('updated');
                    setTimeout(function() {
                        self.container.classList.remove('updated');
                    }, 500);
                }
            };

            xhr.onerror = function() {
                if (self.container) {
                    self.container.classList.remove('updating');
                }
            };

            xhr.send('action=getProgress&ajax=1');
        },

        /**
         * Update progress bar with new data
         */
        updateProgressBar: function(data) {
            if (!this.container) {
                return;
            }

            var cartTotal = parseFloat(data.cart_total) || 0;
            var remaining = parseFloat(data.remaining) || 0;
            var percentage = parseFloat(data.percentage) || 0;
            var freeShippingReached = data.free_shipping_reached || false;

            if (freeShippingReached) {
                this.showSuccessMessage();
            } else {
                this.showProgressBar(cartTotal, remaining, percentage);
            }
        },

        /**
         * Show success message when free shipping is reached
         */
        showSuccessMessage: function() {
            var successHtml = '<div class="minorder-success">' +
                '<i class="material-icons">&#xE86C;</i>' +
                '<span>Congratulazioni! Hai raggiunto la spedizione gratuita!</span>' +
                '</div>';

            this.container.innerHTML = successHtml;
        },

        /**
         * Show progress bar with current values
         */
        showProgressBar: function(cartTotal, remaining, percentage) {
            var html = '<div class="minorder-info">' +
                '<p class="minorder-message">' +
                '<i class="material-icons">&#xE558;</i>' +
                '<span>Ti mancano solo ' +
                '<strong class="minorder-remaining-amount">' + this.formatCurrency(remaining) + '</strong> ' +
                'per ottenere la spedizione gratuita!</span>' +
                '</p>' +
                '</div>' +
                '<div class="minorder-progress-bar-wrapper">' +
                '<div class="minorder-progress-bar">' +
                '<div class="minorder-progress-fill" style="width: ' + percentage + '%;">' +
                '<span class="minorder-progress-text">' + Math.round(percentage) + '%</span>' +
                '</div>' +
                '</div>' +
                '<div class="minorder-progress-labels">' +
                '<span class="minorder-current">' + this.formatCurrency(cartTotal) + '</span>' +
                '<span class="minorder-target">' + this.formatCurrency(this.freeShippingAmount) + '</span>' +
                '</div>' +
                '</div>';

            this.container.innerHTML = html;
        },

        /**
         * Format currency value
         */
        formatCurrency: function(value) {
            return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + this.currencySign;
        },

        /**
         * Update from DOM (fallback when AJAX is not available)
         */
        updateFromDOM: function() {
            // Try to get cart total from PrestaShop's cart summary
            var cartTotalElement = document.querySelector('.cart-total .value, .cart-summary-line.cart-total .value');

            if (cartTotalElement && this.container) {
                var cartTotalText = cartTotalElement.textContent || '';
                var cartTotal = parseFloat(cartTotalText.replace(/[^\d,.-]/g, '').replace(',', '.'));

                if (!isNaN(cartTotal) && this.freeShippingAmount > 0) {
                    var remaining = Math.max(0, this.freeShippingAmount - cartTotal);
                    var percentage = Math.min(100, (cartTotal / this.freeShippingAmount) * 100);

                    if (remaining <= 0) {
                        this.showSuccessMessage();
                    } else {
                        this.showProgressBar(cartTotal, remaining, percentage);
                    }
                }

                this.container.classList.remove('updating');
                this.container.classList.add('updated');

                var self = this;
                setTimeout(function() {
                    self.container.classList.remove('updated');
                }, 500);
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            MinOrderProgress.init();
        });
    } else {
        MinOrderProgress.init();
    }

    // Make available globally for debugging
    window.MinOrderProgress = MinOrderProgress;

})();
