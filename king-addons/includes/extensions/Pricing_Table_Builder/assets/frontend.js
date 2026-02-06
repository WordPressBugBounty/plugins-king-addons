/**
 * Pricing Table Builder - Frontend JavaScript
 * Handles billing toggle and animations
 */

(function() {
    'use strict';

    const KNG_PT = {
        /**
         * Initialize all pricing tables
         */
        init: function() {
            document.querySelectorAll('.kng-pt-wrapper').forEach(wrapper => {
                this.initToggle(wrapper);
            });
        },

        /**
         * Initialize billing toggle for a wrapper
         */
        initToggle: function(wrapper) {
            const toggle = wrapper.querySelector('.kng-pt-toggle');
            if (!toggle) return;

            const buttons = toggle.querySelectorAll('.kng-pt-toggle-btn');
            const cards = wrapper.querySelectorAll('.kng-pt-card');

            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const period = btn.dataset.period;
                    
                    // Update button states
                    buttons.forEach(b => {
                        b.classList.remove('is-active');
                        b.setAttribute('aria-checked', 'false');
                    });
                    btn.classList.add('is-active');
                    btn.setAttribute('aria-checked', 'true');
                    
                    // Update wrapper data attribute
                    wrapper.dataset.period = period;
                    
                    // Update price displays in each card
                    cards.forEach(card => {
                        this.updateCardPricing(card, period);
                    });
                });
            });
        },

        /**
         * Update pricing display in a card
         */
        updateCardPricing: function(card, period) {
            const priceGroups = card.querySelectorAll('.kng-pt-price-group');
            
            priceGroups.forEach(group => {
                if (group.dataset.period === period) {
                    group.style.display = '';
                    // Trigger animation
                    group.style.animation = 'none';
                    group.offsetHeight; // Force reflow
                    group.style.animation = '';
                } else {
                    group.style.display = 'none';
                }
            });
        },

        /**
         * Get current period for a wrapper
         */
        getCurrentPeriod: function(wrapper) {
            return wrapper.dataset.period || 'monthly';
        }
    };

    // Expose for admin preview
    window.kngPTFrontend = KNG_PT;

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => KNG_PT.init());
    } else {
        KNG_PT.init();
    }

})();
