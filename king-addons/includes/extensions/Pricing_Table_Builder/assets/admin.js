/**
 * Pricing Table Builder - Admin JavaScript
 * Handles editor interactions and live preview
 */

(function($) {
    'use strict';

    // Config state
    let config = {};
    let previewTimeout = null;

    /**
     * Initialize the editor
     */
    function init() {
        // Get initial config from window (set by inline script in template)
        config = window.kngPTInitialConfig || getDefaultConfig();
        
        initTabs();
        initPlans();
        initBillingToggle();
        initAlignmentButtons();
        initPresetSelector();
        initDeviceToggle();
        initCopyButtons();
        initSaveButtons();
        initColorPickers();
        bindChangeEvents();
        updatePreview();
    }

    /**
     * Get default config if none provided
     */
    function getDefaultConfig() {
        return {
            schema_version: 1,
            table: {
                name: '',
                status: 'draft',
                layout: {
                    mode: 'cards',
                    columns_desktop: 3,
                    columns_tablet: 2,
                    columns_mobile: 1,
                    gap: 24,
                    max_width: 1200,
                    alignment: 'center'
                }
            },
            billing: {
                enabled: true,
                type: 'segmented',
                periods: [
                    { key: 'monthly', label: 'Monthly', suffix: '/mo', is_default: false },
                    { key: 'annual', label: 'Annual', suffix: '/yr', is_default: true, badge: { enabled: true, text: 'Save 16%' } }
                ],
                currency: '$',
                currency_position: 'before'
            },
            plans: [],
            features: {
                mode: 'per_plan',
                show_icons: true
            },
            style: {
                preset_id: 'free_modern_cards',
                tokens: {},
                overrides: { custom_css_class: '' }
            },
            advanced: {
                hide_toggle: false,
                force_period: '',
                disable_animations: false
            }
        };
    }

    /**
     * Tab switching
     */
    function initTabs() {
        $('.kng-pt-tab').on('click', function() {
            const tab = $(this).data('tab');
            
            $('.kng-pt-tab').removeClass('is-active');
            $(this).addClass('is-active');
            
            $('.kng-pt-tab-panel').removeClass('is-active');
            $(`.kng-pt-tab-panel[data-panel="${tab}"]`).addClass('is-active');
        });
    }

    /**
     * Initialize plans list
     */
    function initPlans() {
        renderPlans();
        
        // Make plans sortable
        $('#kng-pt-plans-list').sortable({
            handle: '.kng-pt-plan-drag',
            update: function() {
                updatePlansOrder();
                schedulePreview();
            }
        });

        // Add plan button
        $('#kng-pt-add-plan').on('click', function() {
            const newPlan = createDefaultPlan();
            config.plans = config.plans || [];
            config.plans.push(newPlan);
            renderPlans();
            schedulePreview();
        });
    }

    /**
     * Render plans list
     */
    function renderPlans() {
        const $list = $('#kng-pt-plans-list');
        const template = $('#kng-pt-plan-template').html();
        
        $list.empty();
        
        (config.plans || []).forEach((plan, index) => {
            const $plan = $(template);
            $plan.attr('data-plan-id', plan.id);
            $plan.find('.kng-pt-plan-name-input').val(plan.name);
            $plan.find('.kng-pt-plan-subtitle').val(plan.subtitle || '');
            $plan.find('.kng-pt-plan-highlight').prop('checked', plan.highlight?.enabled);
            $plan.find('.kng-pt-plan-badge-enabled').prop('checked', plan.badge?.enabled);
            $plan.find('.kng-pt-plan-badge-text').val(plan.badge?.text || '');
            
            if (plan.badge?.enabled) {
                $plan.find('.kng-pt-plan-badge-fields').show();
            }
            
            // Pricing
            const periods = config.billing?.periods || [];
            periods.forEach(period => {
                const $periodEl = $plan.find(`.kng-pt-plan-pricing-period[data-period="${period.key}"]`);
                const priceData = plan.pricing?.[period.key] || {};
                $periodEl.find('.kng-pt-plan-price').val(priceData.price || '');
                $periodEl.find('.kng-pt-plan-note').val(priceData.note || '');
            });
            
            // CTA
            $plan.find('.kng-pt-plan-cta-text').val(plan.cta?.text || 'Get Started');
            $plan.find('.kng-pt-plan-cta-url').val(plan.cta?.url || '#');
            $plan.find('.kng-pt-plan-cta-style').val(plan.cta?.style || 'primary');
            $plan.find('.kng-pt-plan-cta-target').val(plan.cta?.target || '_self');
            
            // Features
            renderPlanFeatures($plan, plan.features || []);
            
            $list.append($plan);
        });
        
        // Bind plan events
        bindPlanEvents();
    }

    /**
     * Render features for a plan
     */
    function renderPlanFeatures($plan, features) {
        const $list = $plan.find('.kng-pt-plan-features-list');
        const template = $('#kng-pt-feature-template').html();
        
        $list.empty();
        
        features.forEach((feature, index) => {
            const $feature = $(template);
            $feature.attr('data-index', index);
            $feature.find('.kng-pt-feature-text').val(feature.text);
            $feature.find('.kng-pt-feature-state').val(feature.state);
            $list.append($feature);
        });
        
        // Make features sortable
        $list.sortable({
            handle: '.kng-pt-feature-drag',
            update: function() {
                updatePlanFromDOM($plan);
                schedulePreview();
            }
        });
    }

    /**
     * Bind events to plan items
     */
    function bindPlanEvents() {
        // Toggle plan body
        $('.kng-pt-plan-toggle').off('click').on('click', function(e) {
            e.stopPropagation();
            $(this).closest('.kng-pt-plan-item').toggleClass('is-collapsed');
        });
        
        // Delete plan
        $('.kng-pt-plan-delete').off('click').on('click', function(e) {
            e.stopPropagation();
            if (confirm(kngPTAdmin.i18n.confirmDeletePlan)) {
                const planId = $(this).closest('.kng-pt-plan-item').data('plan-id');
                config.plans = config.plans.filter(p => p.id !== planId);
                renderPlans();
                schedulePreview();
            }
        });
        
        // Badge toggle
        $('.kng-pt-plan-badge-enabled').off('change').on('change', function() {
            const $fields = $(this).closest('.kng-pt-plan-body').find('.kng-pt-plan-badge-fields');
            $fields.toggle(this.checked);
            updatePlanFromDOM($(this).closest('.kng-pt-plan-item'));
            schedulePreview();
        });
        
        // Add feature
        $('.kng-pt-add-feature').off('click').on('click', function() {
            const $plan = $(this).closest('.kng-pt-plan-item');
            const planId = $plan.data('plan-id');
            const plan = config.plans.find(p => p.id === planId);
            
            if (plan) {
                plan.features = plan.features || [];
                plan.features.push({ text: '', state: 'enabled' });
                renderPlanFeatures($plan, plan.features);
                bindFeatureEvents();
                schedulePreview();
            }
        });
        
        // Delete feature
        bindFeatureEvents();
        
        // Plan field changes
        $('.kng-pt-plan-name-input, .kng-pt-plan-subtitle, .kng-pt-plan-highlight, .kng-pt-plan-badge-text, .kng-pt-plan-price, .kng-pt-plan-note, .kng-pt-plan-cta-text, .kng-pt-plan-cta-url, .kng-pt-plan-cta-style, .kng-pt-plan-cta-target')
            .off('input change')
            .on('input change', function() {
                updatePlanFromDOM($(this).closest('.kng-pt-plan-item'));
                schedulePreview();
            });
    }

    /**
     * Bind feature item events
     */
    function bindFeatureEvents() {
        $('.kng-pt-feature-delete').off('click').on('click', function() {
            const $plan = $(this).closest('.kng-pt-plan-item');
            $(this).closest('.kng-pt-feature-item').remove();
            updatePlanFromDOM($plan);
            schedulePreview();
        });
        
        $('.kng-pt-feature-text, .kng-pt-feature-state').off('input change').on('input change', function() {
            const $plan = $(this).closest('.kng-pt-plan-item');
            updatePlanFromDOM($plan);
            schedulePreview();
        });
    }

    /**
     * Update plan object from DOM
     */
    function updatePlanFromDOM($plan) {
        const planId = $plan.data('plan-id');
        const plan = config.plans.find(p => p.id === planId);
        
        if (!plan) return;
        
        plan.name = $plan.find('.kng-pt-plan-name-input').val();
        plan.subtitle = $plan.find('.kng-pt-plan-subtitle').val();
        plan.highlight = {
            enabled: $plan.find('.kng-pt-plan-highlight').prop('checked'),
            style: 'border'
        };
        plan.badge = {
            enabled: $plan.find('.kng-pt-plan-badge-enabled').prop('checked'),
            text: $plan.find('.kng-pt-plan-badge-text').val(),
            style: 'pill'
        };
        
        // Pricing
        const periods = config.billing?.periods || [];
        plan.pricing = plan.pricing || {};
        periods.forEach(period => {
            const $periodEl = $plan.find(`.kng-pt-plan-pricing-period[data-period="${period.key}"]`);
            plan.pricing[period.key] = {
                price: $periodEl.find('.kng-pt-plan-price').val(),
                note: $periodEl.find('.kng-pt-plan-note').val()
            };
        });
        
        // CTA
        plan.cta = {
            enabled: true,
            text: $plan.find('.kng-pt-plan-cta-text').val(),
            url: $plan.find('.kng-pt-plan-cta-url').val(),
            style: $plan.find('.kng-pt-plan-cta-style').val(),
            target: $plan.find('.kng-pt-plan-cta-target').val()
        };
        
        // Features
        plan.features = [];
        $plan.find('.kng-pt-feature-item').each(function() {
            plan.features.push({
                text: $(this).find('.kng-pt-feature-text').val(),
                state: $(this).find('.kng-pt-feature-state').val()
            });
        });
    }

    /**
     * Update plans order after sorting
     */
    function updatePlansOrder() {
        const newOrder = [];
        $('#kng-pt-plans-list .kng-pt-plan-item').each(function(index) {
            const planId = $(this).data('plan-id');
            const plan = config.plans.find(p => p.id === planId);
            if (plan) {
                plan.order = index + 1;
                newOrder.push(plan);
            }
        });
        config.plans = newOrder;
    }

    /**
     * Create default plan object
     */
    function createDefaultPlan() {
        const id = 'plan_' + Date.now();
        const order = (config.plans?.length || 0) + 1;
        
        return {
            id: id,
            order: order,
            name: kngPTAdmin.i18n.planName + ' ' + order,
            subtitle: '',
            badge: { enabled: false, text: '', style: 'pill' },
            highlight: { enabled: false, style: 'border' },
            pricing: {
                monthly: { price: '0', note: '' },
                annual: { price: '0', note: '' }
            },
            cta: {
                enabled: true,
                text: 'Get Started',
                url: '#',
                style: 'secondary',
                target: '_self'
            },
            features: []
        };
    }

    /**
     * Initialize billing toggle
     */
    function initBillingToggle() {
        $('#kng-pt-billing-enabled').on('change', function() {
            config.billing = config.billing || {};
            config.billing.enabled = this.checked;
            $('.kng-pt-billing-options').toggle(this.checked);
            schedulePreview();
        });
        
        // Period badge toggle
        $('.kng-pt-period-badge-enabled').on('change', function() {
            $(this).closest('.kng-pt-period-item').find('.kng-pt-period-badge-text-wrap').toggle(this.checked);
            updatePeriodsFromDOM();
            schedulePreview();
        });
        
        // Period fields
        $('.kng-pt-period-key, .kng-pt-period-label, .kng-pt-period-suffix, .kng-pt-period-badge-text').on('input', function() {
            updatePeriodsFromDOM();
            schedulePreview();
        });
        
        // Default period radio
        $('input[name="default_period"]').on('change', function() {
            updatePeriodsFromDOM();
            schedulePreview();
        });
        
        // Billing type
        $('input[name="billing_type"]').on('change', function() {
            config.billing = config.billing || {};
            config.billing.type = this.value;
            schedulePreview();
        });
        
        // Currency
        $('#kng-pt-currency, #kng-pt-currency-pos').on('input change', function() {
            config.billing = config.billing || {};
            config.billing.currency = $('#kng-pt-currency').val();
            config.billing.currency_position = $('#kng-pt-currency-pos').val();
            schedulePreview();
        });
    }

    /**
     * Update periods config from DOM
     */
    function updatePeriodsFromDOM() {
        const periods = [];
        const defaultPeriod = $('input[name="default_period"]:checked').val();
        
        $('.kng-pt-period-item').each(function() {
            const key = $(this).find('.kng-pt-period-key').val();
            periods.push({
                key: key,
                label: $(this).find('.kng-pt-period-label').val(),
                suffix: $(this).find('.kng-pt-period-suffix').val(),
                is_default: key === defaultPeriod,
                badge: {
                    enabled: $(this).find('.kng-pt-period-badge-enabled').prop('checked'),
                    text: $(this).find('.kng-pt-period-badge-text').val()
                }
            });
        });
        
        config.billing = config.billing || {};
        config.billing.periods = periods;
    }

    /**
     * Initialize alignment buttons
     */
    function initAlignmentButtons() {
        $('.kng-v3-btn-group-item').on('click', function() {
            const value = $(this).data('value');
            $(this).siblings().removeClass('is-active');
            $(this).addClass('is-active');
            $('#kng-pt-alignment').val(value);
            
            config.table = config.table || {};
            config.table.layout = config.table.layout || {};
            config.table.layout.alignment = value;
            schedulePreview();
        });
    }

    /**
     * Initialize preset selector
     */
    function initPresetSelector() {
        $('.kng-pt-preset-card:not(.is-disabled)').on('click', function() {
            const presetId = $(this).data('preset');
            
            $('.kng-pt-preset-card').removeClass('is-active');
            $(this).addClass('is-active');
            
            config.style = config.style || {};
            config.style.preset_id = presetId;
            schedulePreview();
        });
    }

    /**
     * Initialize device toggle for preview
     */
    function initDeviceToggle() {
        $('.kng-pt-device-btn').on('click', function() {
            const device = $(this).data('device');
            
            $('.kng-pt-device-btn').removeClass('is-active');
            $(this).addClass('is-active');
            
            $('#kng-pt-preview-frame').attr('data-device', device);
        });
    }

    /**
     * Initialize copy buttons
     */
    function initCopyButtons() {
        $(document).on('click', '.kng-pt-copy-btn', function() {
            const text = $(this).data('copy') || $(this).siblings('.kng-pt-shortcode, .kng-pt-shortcode-code').text();
            
            navigator.clipboard.writeText(text).then(() => {
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>');
                setTimeout(() => $btn.html(originalHtml), 1500);
            });
        });
    }

    /**
     * Initialize save buttons
     */
    function initSaveButtons() {
        $('#kng-pt-save-draft').on('click', function() {
            config.table = config.table || {};
            config.table.status = 'draft';
            saveTable();
        });
        
        $('#kng-pt-publish').on('click', function() {
            config.table = config.table || {};
            config.table.status = 'publish';
            saveTable();
        });
        
        $('#kng-pt-preview-btn').on('click', function() {
            updatePreview();
        });
    }

    /**
     * Save table
     */
    function saveTable() {
        // Update config from form
        collectConfigFromForm();
        
        // Set config JSON to hidden input
        $('#kng-pt-config-input').val(JSON.stringify(config));
        
        // Submit form
        $('#kng-pt-form').submit();
    }

    /**
     * Collect all config values from form
     */
    function collectConfigFromForm() {
        // Table settings
        config.table = config.table || {};
        config.table.name = $('#kng-pt-name').val();
        config.table.layout = config.table.layout || {};
        config.table.layout.mode = $('input[name="layout_mode"]:checked').val();
        config.table.layout.columns_desktop = parseInt($('#kng-pt-cols-desktop').val());
        config.table.layout.columns_tablet = parseInt($('#kng-pt-cols-tablet').val());
        config.table.layout.columns_mobile = parseInt($('#kng-pt-cols-mobile').val());
        config.table.layout.gap = parseInt($('#kng-pt-gap').val());
        config.table.layout.max_width = parseInt($('#kng-pt-max-width').val());
        config.table.layout.alignment = $('#kng-pt-alignment').val();
        
        // Features settings
        config.features = config.features || {};
        config.features.mode = $('input[name="features_mode"]:checked').val();
        config.features.show_icons = $('#kng-pt-show-icons').prop('checked');
        
        // Style settings
        config.style = config.style || {};
        config.style.tokens = config.style.tokens || {};
        config.style.tokens.colors = config.style.tokens.colors || {};
        
        const accentColor = $('#kng-pt-color-accent').val();
        const cardBg = $('#kng-pt-color-card-bg').val();
        const textColor = $('#kng-pt-color-text').val();
        
        if (accentColor) config.style.tokens.colors.accent = accentColor;
        if (cardBg) config.style.tokens.colors.card_bg = cardBg;
        if (textColor) config.style.tokens.colors.text = textColor;
        
        config.style.overrides = config.style.overrides || {};
        config.style.overrides.custom_css_class = $('#kng-pt-custom-class').val();
        
        // Advanced settings
        config.advanced = config.advanced || {};
        config.advanced.hide_toggle = $('#kng-pt-hide-toggle').prop('checked');
        config.advanced.force_period = $('#kng-pt-force-period').val();
        config.advanced.disable_animations = $('#kng-pt-disable-animations').prop('checked');
    }

    /**
     * Initialize color pickers
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.kng-v3-color-input').wpColorPicker({
                change: function() {
                    schedulePreview();
                },
                clear: function() {
                    schedulePreview();
                }
            });
        }
    }

    /**
     * Bind change events to form fields
     */
    function bindChangeEvents() {
        // General tab
        $('#kng-pt-name, #kng-pt-cols-desktop, #kng-pt-cols-tablet, #kng-pt-cols-mobile, #kng-pt-gap, #kng-pt-max-width')
            .on('input change', function() {
                collectConfigFromForm();
                schedulePreview();
            });
        
        $('input[name="layout_mode"]').on('change', function() {
            collectConfigFromForm();
            schedulePreview();
        });
        
        // Features tab
        $('input[name="features_mode"], #kng-pt-show-icons').on('change', function() {
            collectConfigFromForm();
            schedulePreview();
        });
        
        // Advanced tab
        $('#kng-pt-hide-toggle, #kng-pt-force-period, #kng-pt-disable-animations').on('change', function() {
            collectConfigFromForm();
            schedulePreview();
        });
        
        // Custom class
        $('#kng-pt-custom-class').on('input', function() {
            collectConfigFromForm();
            schedulePreview();
        });
    }

    /**
     * Schedule preview update (debounced)
     */
    function schedulePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(updatePreview, 300);
    }

    /**
     * Update preview via AJAX
     */
    function updatePreview() {
        collectConfigFromForm();
        
        $.ajax({
            url: kngPTAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_pt_preview',
                nonce: kngPTAdmin.nonce,
                config: JSON.stringify(config)
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('#kng-pt-preview-content').html(response.data.html);
                    
                    // Reinitialize frontend JS for the preview
                    if (window.kngPTFrontend) {
                        window.kngPTFrontend.init();
                    }
                }
            }
        });
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);
