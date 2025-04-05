/**
 * Pricing Slider JavaScript
 * Handles interactive functionality for both single and multiple sliders.
 */
(function($) {
    'use strict';

    // Add basic CSS for notifications that will be created by JS
    function addBasicNotificationCSS() {
        if ($('#king-addons-pricing-slider-notification-styles').length) {
            return; // Already added
        }
        
        var css = `
            .king-addons-slider-notice {
                display: block;
                padding: 10px 15px;
                margin: 10px 0;
                border-radius: 4px;
                font-size: 14px;
                line-height: 1.5;
            }
            .king-addons-slider-notice.success {
                color: #3c763d;
                background-color: #dff0d8;
                border: 1px solid #d6e9c6;
            }
            .king-addons-slider-notice.error {
                color: #a94442;
                background-color: #f2dede;
                border: 1px solid #ebccd1;
            }
            .king-addons-slider-view-cart {
                display: block;
                margin: 10px 0;
                padding: 8px 12px;
                text-align: center;
                text-decoration: none;
                color: #4e4e4e;
                background-color: #f7f7f7;
                border: 1px solid #ddd;
                border-radius: 4px;
                transition: all 0.3s ease;
            }
            .king-addons-slider-view-cart:hover {
                color: #23527c;
                background-color: #e6e6e6;
                border-color: #adadad;
            }
        `;
        
        $('<style id="king-addons-pricing-slider-notification-styles">' + css + '</style>').appendTo('head');
    }

    // Execute once when script loads
    addBasicNotificationCSS();

    // --- Shared Helper Functions --- (Moved here)

    /**
     * Calculates the price based on the value and formula settings.
     * @param {number} value The current slider value.
     * @param {object} formulaSettings Settings object containing formula type and parameters.
     * @returns {number} Calculated price.
     */
    function calculatePrice(value, formulaSettings) {
        if (!formulaSettings) return value; // Return raw value if no settings
        
        var formulaType = formulaSettings.advanced_formula_types || formulaSettings.formula || 'linear';
        
        // Linear formula (Default in free version)
        if (formulaType === 'linear') {
            var a = typeof formulaSettings.a !== 'undefined' ? parseFloat(formulaSettings.a) : 1;
            var b = typeof formulaSettings.b !== 'undefined' ? parseFloat(formulaSettings.b) : 0;
            return a * value + b;
        } 
        // Pro version formulas...
        else if (formulaType === 'exponential') {
            var base = typeof formulaSettings.formula_exp_base !== 'undefined' ? parseFloat(formulaSettings.formula_exp_base) : 1.1;
            var multiplier = typeof formulaSettings.formula_exp_multiplier !== 'undefined' ? parseFloat(formulaSettings.formula_exp_multiplier) : 1;
            return multiplier * Math.pow(base, value);
        }
        else if (formulaType === 'logarithmic') {
            var baseLog = typeof formulaSettings.formula_log_base !== 'undefined' ? parseFloat(formulaSettings.formula_log_base) : 10;
            var multiplierLog = typeof formulaSettings.formula_log_multiplier !== 'undefined' ? parseFloat(formulaSettings.formula_log_multiplier) : 10;
            value = Math.max(1, value); // Logarithm is undefined for 0 or negative
            return multiplierLog * (Math.log(value) / Math.log(baseLog));
        }
        else if (formulaType === 'power') {
            var exponent = typeof formulaSettings.formula_power_exponent !== 'undefined' ? parseFloat(formulaSettings.formula_power_exponent) : 2;
            var multiplierPower = typeof formulaSettings.formula_power_multiplier !== 'undefined' ? parseFloat(formulaSettings.formula_power_multiplier) : 1;
            return multiplierPower * Math.pow(value, exponent);
        }
        else if (formulaType === 'custom') {
            // Find the closest custom price point <= current value
             var customPrices = formulaSettings.custom_prices || [];
             var bestMatchPrice = value; // Fallback
             var closestLowerVal = -Infinity;

             customPrices.forEach(function(point) {
                 var pointValue = parseFloat(point.value);
                 var pointPrice = parseFloat(point.price);
                 if (!isNaN(pointValue) && !isNaN(pointPrice) && pointValue <= value && pointValue > closestLowerVal) {
                     closestLowerVal = pointValue;
                     bestMatchPrice = pointPrice;
                 }
             });
             return bestMatchPrice;
        }
        
        // Fallback to linear if formula type is unknown or default linear params if only 'linear' is specified
         var defaultA = typeof formulaSettings.a !== 'undefined' ? parseFloat(formulaSettings.a) : 1;
         var defaultB = typeof formulaSettings.b !== 'undefined' ? parseFloat(formulaSettings.b) : 0;
         return defaultA * value + defaultB;
    }

    /**
     * Formats a price number into an HTML string with currency, prefix, suffix, etc.
     * @param {number} price The numerical price.
     * @param {object} priceFormatData Settings for formatting (currency_symbol, etc.).
     * @returns {string} HTML string for the price display.
     */
    function formatPriceHTML(price, priceFormatData) {
        var decimals = typeof priceFormatData.decimals !== 'undefined' ? parseInt(priceFormatData.decimals) : 2;
        var formattedPrice = parseFloat(price).toFixed(decimals);
        var currencySymbol = priceFormatData.currency_symbol || '';
        var pricePrefix = priceFormatData.price_prefix || '';
        var priceSuffix = priceFormatData.price_suffix || '';
        var period = priceFormatData.period || '';
        var currencyPosition = priceFormatData.currency_position || 'before';
        
        var price_html = '';
        if (currencyPosition === 'before') {
            price_html += '<span class="king-addons-pricing-slider__currency">' + currencySymbol + '</span>';
        }
        if (pricePrefix) {
             price_html += '<span class="king-addons-pricing-slider__prefix">' + pricePrefix + '</span>';
        }
        // Ensure the value itself has the specific class for targeting if needed later
        price_html += '<span class="king-addons-pricing-slider__price-value">' + formattedPrice + '</span>';
        if (priceSuffix) {
             price_html += '<span class="king-addons-pricing-slider__suffix">' + priceSuffix + '</span>';
        }
         if (currencyPosition === 'after') {
            price_html += '<span class="king-addons-pricing-slider__currency">' + currencySymbol + '</span>';
        }
        if (period) {
            price_html += '<span class="king-addons-pricing-slider__period">' + period + '</span>';
        }
        return price_html;
    }

    /**
     * Updates the visual state (icon, class) of feature items based on the current value.
     * @param {number} value The current slider value.
     * @param {jQuery} featureItems jQuery object of all feature list items.
     * @param {Array} featuresDefinition Array of feature definition objects from settings.
     */
    function updateFeatures(value, featureItems, featuresDefinition) {
        featureItems.each(function() {
             var feature = $(this);
            // Use min-value from data attribute if present, otherwise assume 0
            var minValueFeature = parseInt(feature.data('min-value')) || 0;
             // Determine if the feature should be 'included' based on the slider value
            var isIncluded = value >= minValueFeature;
            
            var featureTextElement = feature.find('.king-addons-pricing-slider__feature-text');
            var featureText = featureTextElement.length ? featureTextElement.text().trim() : ''; // Get text content for matching
            var featureData = null;
            
            // Find the matching feature definition from the settings array
            if (Array.isArray(featuresDefinition) && featureText) {
                for (var i = 0; i < featuresDefinition.length; i++) {
                    // Match based on the feature text content
                    if (featuresDefinition[i] && featuresDefinition[i].text && featuresDefinition[i].text.trim() === featureText) {
                        featureData = featuresDefinition[i];
                        break;
                    }
                }
            }
            
            // Update icon and class if we found matching data
            if (featureData) {
                var iconSpan = feature.find('span:first-child'); // Assuming icon is in the first span
                var iconIncluded = featureData.icon_included || 'fas fa-check'; // Default icons
                var iconExcluded = featureData.icon_excluded || 'fas fa-times';
                
                iconSpan.removeClass('king-addons-pricing-slider__feature-included king-addons-pricing-slider__feature-excluded');
                
                if (isIncluded) {
                    iconSpan.addClass('king-addons-pricing-slider__feature-included');
                    iconSpan.html('<i class="' + iconIncluded + '" aria-hidden="true"></i>');
                } else {
                    iconSpan.addClass('king-addons-pricing-slider__feature-excluded');
                    iconSpan.html('<i class="' + iconExcluded + '" aria-hidden="true"></i>');
                }
            } else {
                 // Optional: Handle case where feature item in HTML doesn't match any definition
                 // console.warn('[KA Slider] No feature definition found for:', featureText);
            }
        });
    }
    
    /**
     * Updates the UI elements of a single slider instance (progress bar, thumb, value display).
     * Moved here to be a shared helper.
     * @param {jQuery} singleSliderWrapper The wrapper (.king-addons-single-slider or .king-addons-pricing-slider).
     * @param {number} value The current value.
     */
     function sharedUpdateSliderUI(singleSliderWrapper, value) {
        var rangeInput = singleSliderWrapper.find('.king-addons-pricing-slider__range');
        var progressBar = singleSliderWrapper.find('.king-addons-pricing-slider__progress');
        var customThumb = singleSliderWrapper.find('.king-addons-pricing-slider__custom-thumb');
        var currentValDisplay = singleSliderWrapper.find('.king-addons-pricing-slider__current-value');

        if (!rangeInput.length) {
             console.error('[KA Slider] Range input not found within:', singleSliderWrapper[0]);
            return;
        }
        if (!progressBar.length) {
             console.warn('[KA Slider] Progress bar not found within:', singleSliderWrapper[0]);
             // Continue without progress bar update
        }
         if (!customThumb.length) {
            console.warn('[KA Slider] Custom thumb not found within:', singleSliderWrapper[0]); // Log raw DOM element
            // Continue without thumb update
        }
        
        var min = parseFloat(rangeInput.attr('min')) || 0;
        var max = parseFloat(rangeInput.attr('max')) || 100;
        var percentage = 0;

        if (max > min) {
            percentage = ((value - min) / (max - min)) * 100;
        } else {
            // If max <= min, slider is effectively fixed. Percentage is 0 if value <= min, 100 if value >= max.
            // Or just treat as 0 to be safe.
             percentage = (value >= max) ? 100 : 0; 
            console.warn('[KA Slider] Max <= Min for slider:', rangeInput[0]);
        }
        
        percentage = Math.max(0, Math.min(100, percentage)); // Clamp percentage

        // console.log('[KA Slider] sharedUpdateSliderUI called:', { // Keep logs for now
        //     wrapper: singleSliderWrapper[0],
        //     value: value,
        //     percentage: percentage,
        //     thumbElement: customThumb.length ? customThumb[0] : 'Not Found'
        // });

        // Apply styles
        if (progressBar.length) {
            progressBar.css('width', percentage + '%');
        }
        if (customThumb.length) {
            customThumb.css('left', percentage + '%'); 
            // console.log('[KA Slider] Applied left: ' + percentage + '% to thumb:', customThumb[0]); 
        }

        // Update current value indicator (if exists)
        if (currentValDisplay.length) {
            currentValDisplay.text(value);
            currentValDisplay.css('left', percentage + '%');
            // Recalculate margin based on actual thumb height at the time of update
            var thumbHeight = customThumb.length ? customThumb.outerHeight() : 0; 
            if (thumbHeight) {
                 var indicatorMarginTop = (thumbHeight / 2) + 10; // 10px gap above thumb center
                currentValDisplay.css('margin-top', indicatorMarginTop + 'px');
            } else {
                 // Fallback margin if thumb isn't found or has no height
                 currentValDisplay.css('margin-top', '22px'); 
            }
        }
    }

    // --- Initialization Functions (Using Shared Helpers) ---

    /**
     * Initialize a specific SINGLE pricing slider. (Revisited Version)
     * @param {jQuery} sliderWrapper The main wrapper element (.king-addons-pricing-slider).
     */
    function initSinglePricingSliderRevisited(sliderWrapper) {
        // Get elements
        var rangeInput = sliderWrapper.find('.king-addons-pricing-slider__range');
        var priceWrapper = sliderWrapper.find('.king-addons-pricing-slider__price'); 
        var button = sliderWrapper.find('.king-addons-pricing-slider__button'); // Could be <a> or <button>
        var features = sliderWrapper.find('.king-addons-pricing-slider__feature-item');
        
        if (!rangeInput.length) {
            console.error("[KA Slider] Single slider init failed: Range input not found in", sliderWrapper[0]);
            return;
        }

        // Get data (use attributes as fallback for robustness)
        var defaultValue = parseFloat(rangeInput.val()) || parseFloat(sliderWrapper.data('default-value')) || parseFloat(rangeInput.attr('min')) || 0;
        var priceData = sliderWrapper.data('price-data') || {}; // Get combined price settings
        var featuresData = sliderWrapper.data('features') || []; // Get features definitions

         // Correct initial value if it's outside min/max bounds
         var minValue = parseFloat(rangeInput.attr('min')) || 0;
         var maxValue = parseFloat(rangeInput.attr('max')) || 100;
         defaultValue = Math.max(minValue, Math.min(maxValue, defaultValue));
         if (parseFloat(rangeInput.val()) !== defaultValue) {
             rangeInput.val(defaultValue); // Ensure input value matches calculated default
         }

        /**
         * Updates the price display, button link (if applicable), and features for the single slider.
         */
        function updateDisplay() {
            var value = parseFloat(rangeInput.val()); // Get current value from input
            var price = calculatePrice(value, priceData); // Use shared function
            var new_price_html = formatPriceHTML(price, priceData); // Use shared function
            
            if (priceWrapper.length) {
                priceWrapper.html(new_price_html);
            }
            
            // Update button URL only if it's a link (<a> tag)
            if (button.length && button.is('a')) { 
                 var url = button.attr('href');
                 // Check if href exists and is not just '#' or empty
                 if (url && url !== '#') { 
                    try {
                        var urlParts = url.split('?');
                        var baseUrl = urlParts[0];
                        var params = new URLSearchParams(urlParts.length > 1 ? urlParts[1] : '');
                        params.set('price', parseFloat(price).toFixed(priceData.decimals || 2)); // Use decimals from data
                        button.attr('href', baseUrl + '?' + params.toString());
                    } catch (e) {
                        console.error("[KA Slider] Error updating button URL:", e);
                         // Potentially invalid URL, leave it as is
                    }
                }
            }
            
            updateFeatures(value, features, featuresData); // Update icons

            // --- Add dynamic class update for feature list items --- START
            if (features.length) {
                features.each(function() {
                    var item = $(this);
                    var minValue = parseFloat(item.data('min-value')); // Get minimum value from data-attribute
                    var isIncluded = value >= minValue;

                    // Remove both classes before adding the correct one
                    item.removeClass('king-addons-feature-included king-addons-feature-excluded');

                    if (isIncluded) {
                        item.addClass('king-addons-feature-included');
                    } else {
                        item.addClass('king-addons-feature-excluded');
                    }
                });
            }
            // --- Add dynamic class update for feature list items --- END
        }

        // --- Initialization ---
        sharedUpdateSliderUI(sliderWrapper, defaultValue); // Initial UI positioning
        updateDisplay(); // Initial price, features, button link update
        
        // --- Event Listener ---
        // Ensure previous listeners are removed before adding new ones (important for editor)
        rangeInput.off('.pricingSlider').on('input.pricingSlider change.pricingSlider', function() {
            var value = parseFloat($(this).val());
            sharedUpdateSliderUI(sliderWrapper, value); // Update slider UI visuals
            updateDisplay(); // Update price, button, features based on new value
        });
    }
    
     /**
      * Initialize MULTIPLE pricing sliders within a container. (Revisited Version)
      * @param {jQuery} slidersContainer The main wrapper element (.king-addons-pricing-sliders).
      */
    function initMultiplePricingSlidersRevisited(slidersContainer) {
        // Get common elements and data for the whole group
        var priceData = slidersContainer.data('price-data') || {}; // Combined price display settings
        var featuresData = slidersContainer.data('features') || []; // Combined features definitions
        var priceWrapper = slidersContainer.find('.king-addons-pricing-slider__price'); // The single price display for the group
        var featuresList = slidersContainer.find('.king-addons-pricing-slider__feature-list'); // Container for features
        var button = slidersContainer.find('.king-addons-pricing-slider__button'); // Combined button (link or WC)
        var addToCartButton = slidersContainer.find('.king-addons-pricing-slider__add-to-cart'); // Specific WC button
        var individualSliders = slidersContainer.find('.king-addons-single-slider'); // Wrappers for each slider
        var allSlidersData = slidersContainer.data('sliders') || []; // Array of settings for each slider (from repeater)

        if (!individualSliders.length) {
             console.error("[KA Slider] Multi-slider init failed: No individual sliders found in", slidersContainer[0]);
             return;
        }
        if (allSlidersData.length !== individualSliders.length) {
            console.warn("[KA Slider] Mismatch between slider data count and slider element count.");
             // Attempt to continue, but calculations might be off
        }

        /**
         * Calculates the combined weighted value/price for all sliders in the group.
         * @returns {number} The final calculated price.
         */
        function calculateCombinedPriceForMulti() {
            var combinedValue = 0;
            var totalWeight = 0;
            
            individualSliders.each(function(index) {
                 var singleSliderWrapper = $(this);
                var rangeInput = singleSliderWrapper.find('.king-addons-pricing-slider__range');
                if (!rangeInput.length) return; // Skip if input not found

                var value = parseFloat(rangeInput.val());
                // Get weight from the corresponding data object, default to 1 if missing or invalid
                var weight = (allSlidersData[index] && typeof allSlidersData[index].weight !== 'undefined' && !isNaN(parseFloat(allSlidersData[index].weight))) 
                                ? parseFloat(allSlidersData[index].weight) 
                                : 1;
                
                combinedValue += value * weight;
                totalWeight += weight;
            });

            // Avoid division by zero if totalWeight is 0
             var averageValue = (totalWeight > 0) ? (combinedValue / totalWeight) : 0;
             
             // Now calculate the final price based on the combined/averaged value using the group's price settings
             return calculatePrice(averageValue, priceData); // Use shared function
        }
        
        /**
         * Updates the combined price display, features list, and button(s) for the group.
         */
        function updateCombinedDisplayForMulti() {
            var finalPrice = calculateCombinedPriceForMulti(); // Use the final calculated price/value for feature check
            var new_price_html = formatPriceHTML(finalPrice, priceData); // Use shared function
            
            if (priceWrapper.length) {
                priceWrapper.html(new_price_html);
            }

            // Update features based on the final calculated price/value
            if (featuresList.length) {
                 var featureItems = featuresList.find('.king-addons-pricing-slider__feature-item');
                 updateFeatures(finalPrice, featureItems, featuresData); // Update icons based on final price

                 // --- Add dynamic class update for feature list items (Multi-Slider) --- START
                 featureItems.each(function() {
                    var item = $(this);
                    var minValue = parseFloat(item.data('min-value')); 
                    // Use the final calculated price/value to determine inclusion for the combined list
                    var isIncluded = finalPrice >= minValue; 

                    item.removeClass('king-addons-feature-included king-addons-feature-excluded');

                    if (isIncluded) {
                        item.addClass('king-addons-feature-included');
                    } else {
                        item.addClass('king-addons-feature-excluded');
                    }
                });
                 // --- Add dynamic class update for feature list items (Multi-Slider) --- END
            }
            
             // Update standard button link (if it's an <a> tag)
             if (button.length && button.is('a')) {
                var url = button.attr('href');
                 if (url && url !== '#') {
                     try {
                        var urlParts = url.split('?');
                        var baseUrl = urlParts[0];
                        var params = new URLSearchParams(urlParts.length > 1 ? urlParts[1] : '');
                        params.set('price', parseFloat(finalPrice).toFixed(priceData.decimals || 2));
                        button.attr('href', baseUrl + '?' + params.toString());
                    } catch(e) {
                         console.error("[KA Slider] Error updating multi-slider button URL:", e);
                    }
                }
            }
             // Update WooCommerce button data attribute (if exists)
            if (addToCartButton.length) {
                 addToCartButton.data('price', finalPrice); // Store calculated price for potential AJAX use
                 addToCartButton.attr('data-price', finalPrice); // Also set attribute for easier debugging/selection
            }
        }

        // --- Initialization ---
        // Initialize UI for each individual slider
        individualSliders.each(function(index) {
            var singleSliderWrapper = $(this);
            var rangeInput = singleSliderWrapper.find('.king-addons-pricing-slider__range');
            if (!rangeInput.length) return; // Skip if no input

            var minValue = parseFloat(rangeInput.attr('min')) || 0;
            var maxValue = parseFloat(rangeInput.attr('max')) || 100;
            // Use default value from data if available, otherwise from input, clamped
            var defaultValue = (allSlidersData[index] && typeof allSlidersData[index].default_value !== 'undefined') 
                                ? parseFloat(allSlidersData[index].default_value)
                                : parseFloat(rangeInput.val());
            defaultValue = Math.max(minValue, Math.min(maxValue, defaultValue || minValue)); 
            
            if (parseFloat(rangeInput.val()) !== defaultValue) {
                 rangeInput.val(defaultValue); // Set initial value correctly
            }
            sharedUpdateSliderUI(singleSliderWrapper, defaultValue); // Use shared function
        });
        // Update the combined display based on initial values
        updateCombinedDisplayForMulti(); 

        // --- Event Listener (Delegated) ---
        // Remove previous listeners first
        slidersContainer.off('.pricingSlider', '.king-addons-pricing-slider__range'); 
        // Add new delegated listener
        slidersContainer.on('input.pricingSlider change.pricingSlider', '.king-addons-pricing-slider__range', function() {
            var rangeInput = $(this); // The input that triggered the event
            var singleSliderWrapper = rangeInput.closest('.king-addons-single-slider');
            var value = parseFloat(rangeInput.val());

            // Log for debugging multi-slider events
            // console.log('[KA Slider] Multi-Slider Input Event Fired:', {
            //     value: value,
            //     targetSliderWrapper: singleSliderWrapper.length ? singleSliderWrapper[0] : 'Not Found', 
            //     eventTarget: rangeInput[0]
            // });

            if (!singleSliderWrapper.length) { 
                console.error('[KA Slider] Could not find .king-addons-single-slider parent for', rangeInput[0]);
                return;
            } 
            
            sharedUpdateSliderUI(singleSliderWrapper, value); // Update the UI of the slider that changed
            updateCombinedDisplayForMulti(); // Recalculate and update the combined price/features
        });
        
        // --- WooCommerce Handler Placeholder ---
         if (addToCartButton.length) {
             addToCartButton.off('click.wcAddToCart').on('click.wcAddToCart', function(e) {
                 e.preventDefault();
                 var $button = $(this);
                 
                 // --- DEBUGGING START ---
                 console.log('[KA Slider] Add to Cart clicked. Localized vars:', typeof king_addons_slider_vars !== 'undefined' ? king_addons_slider_vars : 'Not Defined');
                 // --- DEBUGGING END ---
                 
                 var price = $button.data('price') || calculateCombinedPriceForMulti(); // Get price
                 var productId = $button.data('product-id');
                 var productType = $button.data('product-type') || 'dynamic'; // Get product type
                 var autoQuantity = $button.data('auto-quantity') === 'yes'; // Check if auto quantity is enabled
                 var useAsBudget = $button.data('use-budget') === 'yes'; // Check if using slider as budget

                 var quantity = 1; // Default quantity

                 // If auto quantity is enabled, try to get the value from the relevant slider
                 // NOTE: This assumes auto_quantity makes sense primarily with a single slider scenario.
                 // If used with multiple, logic might need refinement (e.g., use combined value?).
                 if (autoQuantity) {
                     // Find the slider input within the same container as the button
                     // This works for single slider and might need adjustment for complex multi-slider quantity logic
                     var $sliderInput = $button.closest('.king-addons-pricing-slider, .king-addons-pricing-sliders').find('.king-addons-pricing-slider__range').first(); // Get the first slider in the context
                     if ($sliderInput.length) {
                         quantity = parseInt($sliderInput.val()) || 1;
                     } else {
                         console.warn('[KA Slider] Could not find slider input for auto quantity.');
                     }
                     quantity = Math.max(1, quantity); // Ensure quantity is at least 1
                 }

                 if (!productId && productType === 'specific') { // Only require product ID for specific type
                     // Maybe show a user-facing error?
                     // Clear any existing notices or links
                     $button.siblings('.king-addons-slider-notice, .king-addons-slider-view-cart').remove();
                     var errorMsg = $('<span class="king-addons-slider-notice error">Missing Product ID.</span>').insertAfter($button);
                     setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                     return;
                 }
                 
                 // Basic AJAX add-to-cart implementation (needs refinement)
                 // Consider adding loading state to button
                 $button.addClass('loading'); 
                 // Clear any existing notices or links before making the request
                 $button.siblings('.king-addons-slider-notice, .king-addons-slider-view-cart').remove();
                 
                 $.ajax({
                     type: 'POST',
                     url: king_addons_slider_vars.ajax_url, // Make sure this variable is localized
                     data: {
                         action: 'king_addons_add_to_cart', // PHP action hook
                         nonce: $button.data('nonce'), // Get nonce from button data attribute
                         product_id: productId,
                         price: price, // Send the calculated price
                         product_type: productType,
                         quantity: quantity,
                         use_as_budget: useAsBudget ? 'true' : 'false', // Send as string 'true'/'false'
                         auto_quantity: autoQuantity ? 'true' : 'false' // Send as string 'true'/'false'
                     },
                     success: function(response) {
                          $button.removeClass('loading');
                          
                          // Find the target container for notifications based on settings
                          var notificationsContainer;
                          var notificationPosition = (priceData && priceData.notification_position) ? priceData.notification_position : 'after_button';
                          
                          // Remove existing notifications
                          $button.closest('.king-addons-pricing-slider, .king-addons-pricing-sliders')
                                 .find('.king-addons-slider-notice, .king-addons-slider-view-cart').remove();
                          
                          switch(notificationPosition) {
                              case 'before_button':
                                  notificationsContainer = $button.parent();
                                  // Insert before button
                                  if (response.success) {
                                      console.log('[KA Slider] Product added to cart:', response.data);
                                      // Trigger WooCommerce added_to_cart event for themes/plugins to hook into
                                      $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                                      
                                      var successMsg = $('<span class="king-addons-slider-notice success">' + (response.data.message || 'Added to cart!') + '</span>');
                                      successMsg.insertBefore($button);
                                      
                                      if (response.data.cart_url) {
                                          var viewCartText = (typeof king_addons_slider_vars !== 'undefined' && king_addons_slider_vars.view_cart_text) 
                                                              ? king_addons_slider_vars.view_cart_text : 'View Cart';
                                          var viewCartLink = $('<a href="' + response.data.cart_url + '" class="king-addons-slider-view-cart">' + viewCartText + '</a>');
                                          viewCartLink.insertBefore($button);
                                          
                                          setTimeout(function(){
                                              successMsg.fadeOut(function(){ $(this).remove(); });
                                              viewCartLink.fadeOut(function(){ $(this).remove(); });
                                          }, 5000);
                                      } else {
                                          setTimeout(function(){ successMsg.fadeOut(function(){ $(this).remove(); }); }, 3000);
                                      }
                                  } else {
                                      var errorMessage = 'Could not add product to cart. Please try again.';
                                      if (response.data && response.data.error_code === 'budget_too_low') {
                                          var templateMessage = priceData.budget_too_low_message || 'Your selected budget is too low. Please increase the budget to at least {product_price}.';
                                          var productPrice = response.data.product_price || '';
                                          errorMessage = templateMessage.replace('{product_price}', productPrice);
                                      } else if (response.data && response.data.message) {
                                          errorMessage = response.data.message;
                                      }
                                      
                                      var errorMsg = $('<span class="king-addons-slider-notice error">' + errorMessage + '</span>');
                                      errorMsg.insertBefore($button);
                                      setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                                  }
                                  break;
                                  
                              case 'after_price':
                                  var priceElement = $button.closest('.king-addons-pricing-slider, .king-addons-pricing-sliders')
                                                    .find('.king-addons-pricing-slider__price');
                                  if (priceElement.length) {
                                      if (response.success) {
                                          console.log('[KA Slider] Product added to cart:', response.data);
                                          // Trigger WooCommerce added_to_cart event for themes/plugins to hook into
                                          $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                                          
                                          var successMsg = $('<span class="king-addons-slider-notice success">' + (response.data.message || 'Added to cart!') + '</span>');
                                          successMsg.insertAfter(priceElement);
                                          
                                          if (response.data.cart_url) {
                                              var viewCartText = (typeof king_addons_slider_vars !== 'undefined' && king_addons_slider_vars.view_cart_text) 
                                                                  ? king_addons_slider_vars.view_cart_text : 'View Cart';
                                              var viewCartLink = $('<a href="' + response.data.cart_url + '" class="king-addons-slider-view-cart">' + viewCartText + '</a>');
                                              viewCartLink.insertAfter(successMsg);
                                              
                                              setTimeout(function(){
                                                  successMsg.fadeOut(function(){ $(this).remove(); });
                                                  viewCartLink.fadeOut(function(){ $(this).remove(); });
                                              }, 5000);
                                          } else {
                                              setTimeout(function(){ successMsg.fadeOut(function(){ $(this).remove(); }); }, 3000);
                                          }
                                      } else {
                                          var errorMessage = 'Could not add product to cart. Please try again.';
                                          if (response.data && response.data.error_code === 'budget_too_low') {
                                              var templateMessage = priceData.budget_too_low_message || 'Your selected budget is too low. Please increase the budget to at least {product_price}.';
                                              var productPrice = response.data.product_price || '';
                                              errorMessage = templateMessage.replace('{product_price}', productPrice);
                                          } else if (response.data && response.data.message) {
                                              errorMessage = response.data.message;
                                          }
                                          
                                          var errorMsg = $('<span class="king-addons-slider-notice error">' + errorMessage + '</span>');
                                          errorMsg.insertAfter(priceElement);
                                          setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                                      }
                                  } else {
                                      // Fallback to after button if price element not found
                                      notificationPosition = 'after_button';
                                  }
                                  break;
                                  
                              case 'in_container':
                                  var customContainer = $button.closest('.king-addons-pricing-slider, .king-addons-pricing-sliders')
                                                     .find('.king-addons-notification-container');
                                  if (customContainer.length) {
                                      customContainer.empty(); // Clear previous notifications
                                      
                                      if (response.success) {
                                          console.log('[KA Slider] Product added to cart:', response.data);
                                          // Trigger WooCommerce added_to_cart event for themes/plugins to hook into
                                          $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                                          
                                          var successMsg = $('<span class="king-addons-slider-notice success">' + (response.data.message || 'Added to cart!') + '</span>');
                                          customContainer.append(successMsg);
                                          
                                          if (response.data.cart_url) {
                                              var viewCartText = (typeof king_addons_slider_vars !== 'undefined' && king_addons_slider_vars.view_cart_text) 
                                                                  ? king_addons_slider_vars.view_cart_text : 'View Cart';
                                              var viewCartLink = $('<a href="' + response.data.cart_url + '" class="king-addons-slider-view-cart">' + viewCartText + '</a>');
                                              customContainer.append(viewCartLink);
                                              
                                              setTimeout(function(){
                                                  successMsg.fadeOut(function(){ $(this).remove(); });
                                                  viewCartLink.fadeOut(function(){ $(this).remove(); });
                                              }, 5000);
                                          } else {
                                              setTimeout(function(){ successMsg.fadeOut(function(){ $(this).remove(); }); }, 3000);
                                          }
                                      } else {
                                          var errorMessage = 'Could not add product to cart. Please try again.';
                                          if (response.data && response.data.error_code === 'budget_too_low') {
                                              var templateMessage = priceData.budget_too_low_message || 'Your selected budget is too low. Please increase the budget to at least {product_price}.';
                                              var productPrice = response.data.product_price || '';
                                              errorMessage = templateMessage.replace('{product_price}', productPrice);
                                          } else if (response.data && response.data.message) {
                                              errorMessage = response.data.message;
                                          }
                                          
                                          var errorMsg = $('<span class="king-addons-slider-notice error">' + errorMessage + '</span>');
                                          customContainer.append(errorMsg);
                                          setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                                      }
                                  } else {
                                      // Fallback to after button if custom container not found
                                      notificationPosition = 'after_button';
                                  }
                                  break;
                                  
                              case 'after_button':
                              default:
                                  // This is the original behavior - place notifications after the button
                          if (response.success) {
                             console.log('[KA Slider] Product added to cart:', response.data);
                             // Trigger WooCommerce added_to_cart event for themes/plugins to hook into
                             $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                                      
                                      var successMsg = $('<span class="king-addons-slider-notice success">' + (response.data.message || 'Added to cart!') + '</span>');
                                      successMsg.insertAfter($button);
                                      
                                      if (response.data.cart_url) {
                                          var viewCartText = (typeof king_addons_slider_vars !== 'undefined' && king_addons_slider_vars.view_cart_text) 
                                                              ? king_addons_slider_vars.view_cart_text : 'View Cart';
                                          var viewCartLink = $('<a href="' + response.data.cart_url + '" class="king-addons-slider-view-cart">' + viewCartText + '</a>');
                                          viewCartLink.insertAfter(successMsg);
                                          
                                          setTimeout(function(){
                                              successMsg.fadeOut(function(){ $(this).remove(); });
                                              viewCartLink.fadeOut(function(){ $(this).remove(); });
                                          }, 5000);
                                      } else {
                              setTimeout(function(){ successMsg.fadeOut(function(){ $(this).remove(); }); }, 3000);
                                      }
                         } else {
                                      var errorMessage = 'Could not add product to cart. Please try again.';
                             console.error('[KA Slider] Error adding to cart:', response.data);
                                      
                                      if (response.data && response.data.error_code === 'budget_too_low') {
                                          var templateMessage = priceData.budget_too_low_message || 'Your selected budget is too low. Please increase the budget to at least {product_price}.';
                                          var productPrice = response.data.product_price || '';
                                          errorMessage = templateMessage.replace('{product_price}', productPrice);
                                      } else if (response.data && response.data.message) {
                                          errorMessage = response.data.message;
                                      }
                                      
                                      var errorMsg = $('<span class="king-addons-slider-notice error">' + errorMessage + '</span>');
                                      errorMsg.insertAfter($button);
                               setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                                  }
                                  break;
                         }
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                          $button.removeClass('loading');
                         console.error('[KA Slider] AJAX Error:', textStatus, errorThrown);
                          // Show generic user-friendly error for AJAX failures
                          var errorMsg = $('<span class="king-addons-slider-notice error">An error occurred. Please try again later.</span>').insertAfter($button);
                           setTimeout(function(){ errorMsg.fadeOut(function(){ $(this).remove(); }); }, 5000);
                     }
                 });
             });
         }
    }

    // --- Elementor Hook --- (Handles both Editor and Frontend)
    $(window).on('elementor/frontend/init', function() {
        var initAction = function($scope) {
            // Use $scope if provided (in editor), otherwise use document context (frontend)
            var context = $scope ? $scope : $(document);

            // Initialize Single Sliders
            // Find only direct .king-addons-pricing-slider that are NOT .king-addons-pricing-sliders containers
            context.find('.king-addons-pricing-slider:not(.king-addons-pricing-sliders)').each(function() {
                var sliderInstance = $(this);
                // Check if already initialized (e.g., by multiple init calls in editor)
                if (!sliderInstance.data('ka-slider-initialized')) {
                    initSinglePricingSliderRevisited(sliderInstance);
                     sliderInstance.data('ka-slider-initialized', true);
                } else {
                     // console.log('[KA Slider] Single slider already initialized, skipping:', sliderInstance[0]);
                     // Optionally re-trigger UI update if needed in editor on changes
                     var rangeInput = sliderInstance.find('.king-addons-pricing-slider__range');
                     if(rangeInput.length) {
                         sharedUpdateSliderUI(sliderInstance, parseFloat(rangeInput.val()));
                     }
                }
            });

            // Initialize Multiple Sliders Containers
            context.find('.king-addons-pricing-sliders').each(function() {
                var slidersContainer = $(this);
                 if (!slidersContainer.data('ka-slider-initialized')) {
                    initMultiplePricingSlidersRevisited(slidersContainer);
                    slidersContainer.data('ka-slider-initialized', true);
                } else {
                    // console.log('[KA Slider] Multi-slider container already initialized, skipping:', slidersContainer[0]);
                     // Optionally re-trigger UI update for all children
                     slidersContainer.find('.king-addons-single-slider').each(function() {
                         var singleSliderWrapper = $(this);
                         var rangeInput = singleSliderWrapper.find('.king-addons-pricing-slider__range');
                         if (rangeInput.length) {
                             sharedUpdateSliderUI(singleSliderWrapper, parseFloat(rangeInput.val()));
                         }
                     });
                     // And update combined display
                     // Need a way to call updateCombinedDisplayForMulti() here if instance already exists
                     // For now, just re-init might be simpler, or store the update function on the element data
                }
            });
        };

        // --- Elementor Editor Integration ---
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode()) {
            elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-pricing-slider.default', function($scope) {
                // Use timeout to allow elements and data attributes to be fully rendered by Elementor
                 // console.log('[KA Slider] Elementor element_ready hook fired for scope:', $scope[0]);
                setTimeout(function() { 
                    // Clear initialized flag before re-init to handle widget updates
                     $scope.find('.king-addons-pricing-slider, .king-addons-pricing-sliders').removeData('ka-slider-initialized');
                    initAction($scope); 
                }, 150); // Slightly longer timeout for editor stability
            });
        } else {
            // --- Frontend Initialization ---
            // Run on document ready for frontend
             $(document).ready(function() {
                 initAction();
            });
        }
    });
    
})(jQuery); 
// --- REMOVE OLD FUNCTIONS ---
/* 
// Old function, replaced by initSinglePricingSliderRevisited
function initSinglePricingSlider(sliderWrapper) { 
    // ... implementation removed ... 
}

// Old function, replaced by initMultiplePricingSlidersRevisited
function initMultiplePricingSliders(slidersContainer) { 
    // ... implementation removed ... 
} 
*/ 