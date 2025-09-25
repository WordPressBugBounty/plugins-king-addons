/**
 * Pricing Calculator
 * JavaScript functionality for the Pricing Calculator widget
 */
(function($) {
    "use strict";
    
    /**
     * Initialize the pricing calculator
     */
    var initPricingCalculator = function() {
        $('.king-pricing-calculator').each(function() {
            var $calculator = $(this);
            var calculatorId = $calculator.attr('id');
            
            if ($calculator.data('initialized')) {
                return;
            }
            
            $calculator.data('initialized', true);
            
            // Calculator settings
            var basePrice = parseFloat($calculator.data('base-price')) || 0;
            var decimalPlaces = parseInt($calculator.data('decimal-places')) || 2;
            var thousandSeparator = $calculator.data('thousand-separator') || ',';
            var decimalSeparator = $calculator.data('decimal-separator') || '.';
            var pricePrefix = $calculator.data('price-prefix') || '';
            var priceSuffix = $calculator.data('price-suffix') || '';
            
            // Live calculation mode
            var isLiveMode = $calculator.hasClass('king-pricing-calculator--live');
            
            // Cache selectors
            var $fields = $calculator.find('.king-pricing-calculator__field');
            var $totalPrice = $calculator.find('.king-pricing-calculator__total-price');
            var $summaryItems = $calculator.find('.king-pricing-calculator__summary-items');
            var $calculateButton = $calculator.find('.king-pricing-calculator__calculate-button');
            
            // Debug info - log initial calculator settings
            console.log('Calculator initialized with base price:', basePrice);
            
            // Get ajaxurl from localized script if it exists, fallback to global
            var ajaxUrl = (typeof king_addons_calculator_vars !== 'undefined') ? king_addons_calculator_vars.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            // Get nonces from localized script
            var addToCartNonce = (typeof king_addons_calculator_vars !== 'undefined') ? king_addons_calculator_vars.add_to_cart_nonce : '';
            var sendEmailQuoteNonce = (typeof king_addons_calculator_vars !== 'undefined') ? king_addons_calculator_vars.send_email_quote_nonce : '';
            
            // Debounce function for performance
            var debounce = function(func, wait) {
                var timeout;
                return function() {
                    var context = this, args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        func.apply(context, args);
                    }, wait);
                };
            };
            
            // Initialize field values and events
            initFields($fields);
            
            // Add accessibility attributes
            addAccessibilityAttributes();
            
            // Initial calculation
            calculatePrice();
            
            // Calculate button click event
            if ($calculateButton.length && !isLiveMode) {
                $calculateButton.on('click', calculatePrice);
            }
            
            // Initialize Pro feature handlers if Pro is active
            if ($calculator.hasClass('king-pricing-calculator--pro')) {
                initProFeatures($calculator);
            }
            
            /**
             * Add ARIA attributes for accessibility
             */
            function addAccessibilityAttributes() {
                // Range sliders
                $calculator.find('input[type="range"]').each(function() {
                    var $range = $(this);
                    var fieldLabel = $range.closest('.king-pricing-calculator__field').find('.king-pricing-calculator__field-label').text();
                    
                    $range.attr({
                        'role': 'slider',
                        'aria-label': fieldLabel,
                        'aria-valuemin': $range.attr('min'),
                        'aria-valuemax': $range.attr('max'),
                        'aria-valuenow': $range.val()
                    });
                    
                    // Update aria values on change
                    $range.on('input change', function() {
                        $range.attr('aria-valuenow', $range.val());
                    });
                });
            }
            
            /**
             * Initialize calculator fields with events
             */
            function initFields($fields) {
                $fields.each(function() {
                    var $field = $(this);
                    var fieldType = $field.data('field-type');
                    
                    // Debug info - log field attributes
                    console.log('Field:', fieldType, 'Price Type:', $field.data('price-type'), 'Price:', $field.data('price'));
                    
                    // Check if custom formula is used (Pro feature)
                    if ($field.data('price-type') === 'custom') {
                        // Show a one-time console message
                        if (!window.customFormulaMessageShown) {
                            console.info('Custom formula calculation requires Pro version.');
                            window.customFormulaMessageShown = true;
                        }
                    }
                    
                    switch (fieldType) {
                        case 'number':
                            var $input = $field.find('input[type="number"]');
                            
                            // Ensure default value is set
                            if ($input.val() === '') {
                                $input.val($input.attr('min') || 0);
                            }
                            
                            // Debounce input for better performance
                            $input.on('input change', debounce(function() {
                                // Validate input
                                var value = parseFloat($input.val());
                                var min = parseFloat($input.attr('min'));
                                var max = parseFloat($input.attr('max'));
                                
                                if (isNaN(value)) {
                                    $input.val(min || 0);
                                    value = parseFloat($input.val());
                                }
                                
                                if (!isNaN(min) && value < min) {
                                    $input.val(min);
                                } else if (!isNaN(max) && value > max) {
                                    $input.val(max);
                                }
                                
                                if (isLiveMode) {
                                    calculatePrice();
                                }
                            }, 300));
                            break;
                            
                        case 'range':
                            var $range = $field.find('input[type="range"]');
                            var $value = $field.find('.king-pricing-calculator__range-value');
                            
                            // Ensure default value is set
                            if ($range.val() === '') {
                                $range.val($range.attr('min') || 0);
                                $value.text($range.val());
                            }
                            
                            // Set initial range progress
                            updateRangeProgress($range);
                            
                            // Use both input and change events without debounce for immediate feedback
                            $range.on('input', function() {
                                $value.text($range.val());
                                
                                // Update range progress visual
                                updateRangeProgress($range);
                                
                                // Always calculate on range change regardless of live mode
                                calculatePrice();
                            });
                            break;
                            
                        case 'select':
                            var $select = $field.find('select');
                            
                            // Ensure the first option is selected by default
                            if (!$select.find('option:selected').length) {
                                $select.find('option:first').prop('selected', true);
                            }
                            
                            $select.on('change', function() {
                                if (isLiveMode) {
                                    calculatePrice();
                                }
                            });
                            break;
                            
                        case 'radio':
                            var $radios = $field.find('input[type="radio"]');
                            
                            // Ensure at least one radio is checked
                            if (!$radios.filter(':checked').length) {
                                $radios.first().prop('checked', true);
                            }
                            
                            $radios.on('change', function() {
                                if (isLiveMode) {
                                    calculatePrice();
                                }
                            });
                            break;
                            
                        case 'checkbox':
                        case 'switch':
                            var $checkbox = $field.find('input[type="checkbox"]');
                            
                            $checkbox.on('change', function() {
                                if (isLiveMode) {
                                    calculatePrice();
                                }
                            });
                            break;
                    }
                });
            }
            
            /**
             * Calculate the total price based on all field values
             */
            function calculatePrice() {
                try {
                    // Start with base price
                    var total = basePrice;
                    var summaryHtml = '';
                    
                    // Debug info
                    console.log('Starting calculation with base price:', total);
                    
                    // Advanced Formula will be applied after default calculation
                    
                    // First handle additions
                    $fields.each(function() {
                        var $field = $(this);
                        var fieldType = $field.data('field-type');
                        var priceType = $field.data('price-type');
                        var fieldLabel = $field.find('.king-pricing-calculator__field-label').text();
                        var fieldValue = 0;
                        var fieldPrice = 0;
                        var subTotal = 0;
                        
                        if (priceType !== 'add') {
                            return; // Skip non-addition fields on first pass
                        }
                        
                        try {
                            switch (fieldType) {
                                case 'number':
                                    var $input = $field.find('input[type="number"]');
                                    fieldValue = parseFloat($input.val());
                                    fieldPrice = parseFloat($field.data('price'));
                                    
                                    if (!isNaN(fieldValue) && !isNaN(fieldPrice)) {
                                        subTotal = fieldValue * fieldPrice;
                                        total += subTotal;
                                    }
                                    break;
                                    
                                case 'range':
                                    var $range = $field.find('input[type="range"]');
                                    fieldValue = parseFloat($range.val());
                                    fieldPrice = parseFloat($field.data('price'));
                                    
                                    if (!isNaN(fieldValue) && !isNaN(fieldPrice)) {
                                        subTotal = fieldValue * fieldPrice;
                                        total += subTotal;
                                    }
                                    break;
                                    
                                case 'select':
                                    var $select = $field.find('select');
                                    var $option = $select.find('option:selected');
                                    
                                    fieldValue = $option.text();
                                    fieldPrice = parseFloat($option.data('price'));
                                    
                                    if (!isNaN(fieldPrice)) {
                                        subTotal = fieldPrice;
                                        total += subTotal;
                                    }
                                    break;
                                    
                                case 'radio':
                                    var $radio = $field.find('input[type="radio"]:checked');
                                    
                                    if ($radio.length) {
                                        fieldValue = $radio.siblings('label').text();
                                        fieldPrice = parseFloat($radio.data('price'));
                                        
                                        if (!isNaN(fieldPrice)) {
                                            subTotal = fieldPrice;
                                            total += subTotal;
                                        }
                                    }
                                    break;
                                    
                                case 'checkbox':
                                case 'switch':
                                    var $checkbox = $field.find('input[type="checkbox"]');
                                    
                                    if ($checkbox.is(':checked')) {
                                        fieldValue = 'Yes';
                                        fieldPrice = parseFloat($field.data('price'));
                                        
                                        if (!isNaN(fieldPrice)) {
                                            subTotal = fieldPrice;
                                            total += subTotal;
                                        }
                                    } else {
                                        fieldValue = 'No';
                                    }
                                    break;
                            }
                            
                            // Add to summary if there's a value and subtotal
                            if (fieldValue !== '' && fieldValue !== 0 && subTotal !== 0) {
                                summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                    '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                    '<span class="king-pricing-calculator__summary-value">' + formatPrice(subTotal) + '</span>' +
                                    '</div>';
                            }
                            
                            // Debug info
                            console.log('Field:', fieldLabel, 'Type:', fieldType, 'Value:', fieldValue, 'Price:', fieldPrice, 'Subtotal:', subTotal);
                        
                        } catch (fieldError) {
                            console.error('Error processing field for addition:', fieldError);
                        }
                    });
                    
                    // Debug info
                    console.log('After additions, total is:', total);
                    
                    // Then handle multiplications
                    $fields.each(function() {
                        var $field = $(this);
                        var fieldType = $field.data('field-type');
                        var priceType = $field.data('price-type');
                        var fieldLabel = $field.find('.king-pricing-calculator__field-label').text();
                        var fieldValue = 0;
                        var fieldPrice = 0;
                        var beforeMult = total;
                        
                        if (priceType !== 'multiply') {
                            return; // Skip non-multiplication fields on second pass
                        }
                        
                        try {
                            switch (fieldType) {
                                case 'number':
                                    var $input = $field.find('input[type="number"]');
                                    fieldValue = parseFloat($input.val());
                                    
                                    if (!isNaN(fieldValue) && fieldValue !== 0) {
                                        beforeMult = total;
                                        total *= fieldValue;
                                        
                                        summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                            '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                            '<span class="king-pricing-calculator__summary-value">' + formatPrice(total - beforeMult) + '</span>' +
                                            '</div>';
                                    }
                                    break;
                                    
                                case 'range':
                                    var $range = $field.find('input[type="range"]');
                                    fieldValue = parseFloat($range.val());
                                    
                                    if (!isNaN(fieldValue) && fieldValue !== 0) {
                                        beforeMult = total;
                                        total *= fieldValue;
                                        
                                        summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                            '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                            '<span class="king-pricing-calculator__summary-value">' + formatPrice(total - beforeMult) + '</span>' +
                                            '</div>';
                                    }
                                    break;
                                    
                                case 'select':
                                    var $select = $field.find('select');
                                    var $option = $select.find('option:selected');
                                    
                                    fieldValue = $option.text();
                                    fieldPrice = parseFloat($option.data('price'));
                                    
                                    if (!isNaN(fieldPrice) && fieldPrice !== 0) {
                                        beforeMult = total;
                                        total *= fieldPrice;
                                        
                                        summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                            '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                            '<span class="king-pricing-calculator__summary-value">' + formatPrice(total - beforeMult) + '</span>' +
                                            '</div>';
                                    }
                                    break;
                                    
                                case 'radio':
                                    var $radio = $field.find('input[type="radio"]:checked');
                                    
                                    if ($radio.length) {
                                        fieldValue = $radio.siblings('label').text();
                                        fieldPrice = parseFloat($radio.data('price'));
                                        
                                        if (!isNaN(fieldPrice) && fieldPrice !== 0) {
                                            beforeMult = total;
                                            total *= fieldPrice;
                                            
                                            summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                                '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                                '<span class="king-pricing-calculator__summary-value">' + formatPrice(total - beforeMult) + '</span>' +
                                                '</div>';
                                        }
                                    }
                                    break;
                                    
                                case 'checkbox':
                                case 'switch':
                                    var $checkbox = $field.find('input[type="checkbox"]');
                                    
                                    if ($checkbox.is(':checked')) {
                                        fieldValue = 'Yes';
                                        fieldPrice = parseFloat($field.data('price'));
                                        
                                        if (!isNaN(fieldPrice) && fieldPrice !== 0) {
                                            beforeMult = total;
                                            total *= fieldPrice;
                                            
                                            summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                                '<span class="king-pricing-calculator__summary-label">' + fieldLabel + ': ' + fieldValue + '</span>' +
                                                '<span class="king-pricing-calculator__summary-value">' + formatPrice(total - beforeMult) + '</span>' +
                                                '</div>';
                                        }
                                    }
                                    break;
                            }
                            
                            // Debug info
                            console.log('Field (multiply):', fieldLabel, 'Type:', fieldType, 'Value:', fieldValue, 'BeforeMult:', beforeMult, 'After:', total);
                        
                        } catch (fieldError) {
                            console.error('Error processing field for multiplication:', fieldError);
                        }
                    });
                    
                    // Then handle custom formula (fallback to addition in free version)
                    $fields.each(function() {
                        var $field = $(this);
                        var fieldType = $field.data('field-type');
                        var priceType = $field.data('price-type');
                        
                        if (priceType !== 'custom') {
                            return; // Skip non-custom fields
                        }
                        
                        try {
                            // In the free version, just treat custom formula as add
                            var fieldLabel = $field.find('.king-pricing-calculator__field-label').text();
                            var fieldValue, fieldPrice, subTotal = 0;
                            
                            switch (fieldType) {
                                case 'number':
                                case 'range':
                                    var $input = fieldType === 'number' ? 
                                        $field.find('input[type="number"]') : 
                                        $field.find('input[type="range"]');
                                    
                                    fieldValue = parseFloat($input.val());
                                    fieldPrice = parseFloat($field.data('price'));
                                    
                                    if (!isNaN(fieldValue) && !isNaN(fieldPrice)) {
                                        // Fallback to addition in free version
                                        subTotal = fieldValue * fieldPrice;
                                        total += subTotal;
                                        
                                        summaryHtml += '<div class="king-pricing-calculator__summary-item">' +
                                            '<span class="king-pricing-calculator__summary-label">' + 
                                                fieldLabel + ': ' + fieldValue + 
                                                ' <small>(Pro formula fallback)</small>' +
                                            '</span>' +
                                            '<span class="king-pricing-calculator__summary-value">' + formatPrice(subTotal) + '</span>' +
                                            '</div>';
                                    }
                                    break;
                                    
                                case 'select':
                                case 'radio':
                                case 'checkbox':
                                case 'switch':
                                    // Handle similar to add, with pro notice
                                    // Implementation follows similar pattern to the addition case
                                    break;
                            }
                            
                            console.log('Custom formula field (free fallback):', fieldLabel, 'Added:', subTotal);
                            
                        } catch (fieldError) {
                            console.error('Error processing custom formula field:', fieldError);
                        }
                    });
                    
                    // Advanced Formula override (Pro) after default calculation loops
                    if ($calculator.hasClass('king-pricing-calculator--advanced-formula')) {
                        var formulaType = $calculator.data('formulaType') || 'standard';
                        // Map fields for formula execution
                        var fieldsMap = {};
                        var aliasMap = {}; // Create a user-friendly alias map using custom field IDs
                        $fields.each(function() {
                            var $field = $(this);
                            var id = $field.data('field-id');
                            var type = $field.data('field-type');
                            var value;
                            if (type === 'checkbox' || type === 'switch') {
                                value = $field.find('input[type="checkbox"]').is(':checked');
                            } else if (type === 'radio') {
                                value = $field.find('input[type="radio"]:checked').val();
                            } else {
                                var v = $field.find('input, select').val();
                                var num = parseFloat(v);
                                value = isNaN(num) ? v : num;
                            }
                            fieldsMap[id] = value;
                            
                            // If this is a custom field ID (starting with 'king-calc-'), create an alias
                            if (id.indexOf('king-calc-') === 0) {
                                var customId = id.replace('king-calc-', '');
                                aliasMap[customId] = value;
                            }
                        });
                        
                        // For debugging custom formulas
                        console.log('Available field IDs:', fieldsMap);
                        console.log('User-friendly field aliases:', aliasMap);
                        
                        // Apply chosen formula on the aggregated total
                        var newTotal = total;
                        if (formulaType === 'exponential') {
                            var expBase = parseFloat($calculator.data('exponentialBase')) || 1;
                            newTotal = Math.pow(total, expBase);
                        } else if (formulaType === 'logarithmic') {
                            var logBase = parseFloat($calculator.data('logarithmicBase')) || Math.E;
                            // Avoid log(0) or log of negative
                            newTotal = total > 0 ? Math.log(total) / Math.log(logBase) : 0;
                        } else if (formulaType === 'custom') {
                            var customCode = $calculator.data('customFormula') || '';
                            try {
                                // Security fix: Only allow safe mathematical operations
                                if (!isCustomFormulaSafe(customCode)) {
                                    console.error('Custom formula contains unsafe code');
                                    return;
                                }
                                // Pass both the full field map and user-friendly aliases
                                var fn = new Function('fields', 'basePrice', 'aliases', customCode);
                                newTotal = fn(fieldsMap, basePrice, aliasMap);
                            } catch (e) {
                                console.error('Custom formula error:', e);
                            }

                            function isCustomFormulaSafe(formula) {
                                // Only allow safe mathematical operations
                                var safePatterns = [
                                    /^[0-9+\-*/().\s]+$/, // Basic math
                                    /Math\.(abs|round|ceil|floor|min|max|pow|sqrt|sin|cos|tan|log|exp|PI|E)/g, // Safe Math functions
                                ];

                                for (var i = 0; i < safePatterns.length; i++) {
                                    if (safePatterns[i].test(formula)) {
                                        return true;
                                    }
                                }

                                // Check for dangerous patterns
                                var dangerousPatterns = [
                                    /eval|Function|new|this|window|document|script|alert|prompt|confirm/g,
                                    /[\[\]{}]/g, // Object/array notation
                                    /\w+\s*\(/g, // Function calls (except Math functions already checked)
                                ];

                                for (var i = 0; i < dangerousPatterns.length; i++) {
                                    if (dangerousPatterns[i].test(formula)) {
                                        return false;
                                    }
                                }

                                return true;
                            }
                        }
                        // Override total with formula result
                        total = (typeof newTotal === 'number' && !isNaN(newTotal)) ? newTotal : total;
                    }
                    
                    // Final check to prevent NaN
                    if (isNaN(total)) {
                        console.error('Total calculation resulted in NaN');
                        total = basePrice;
                    }
                    
                    // Debug info
                    console.log('Final total:', total);
                    
                    // Update the total price
                    $totalPrice.text(formatPrice(total));
                    
                    // Update the summary items
                    $summaryItems.html(summaryHtml);
                } catch(e) {
                    console.error('Calculation error:', e);
                    $totalPrice.text(formatPrice(basePrice));
                }
            }
            
            /**
             * Format a price number with proper separators
             */
            function formatPrice(number) {
                if (isNaN(number)) {
                    console.error('Attempting to format NaN as price');
                    number = 0;
                }
                var parts = number.toFixed(decimalPlaces).toString().split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
                return pricePrefix + parts.join(decimalSeparator) + priceSuffix;
            }
            
            /**
             * Update the visual appearance of range slider progress
             */
            function updateRangeProgress($range) {
                var min = parseFloat($range.attr('min')) || 0;
                var max = parseFloat($range.attr('max')) || 100;
                var value = parseFloat($range.val()) || 0;
                var progress = ((value - min) / (max - min)) * 100;
                
                // Set CSS variable for the progress
                $range.css('--range-progress', progress + '%');
            }
        });
    };
    
    /**
     * Initialize Pro feature handlers for Pricing Calculator
     * @param jQuery $calculator
     */
    function initProFeatures($calculator) {
        var calculatorId = $calculator.attr('id');
        var $summaryItems = $calculator.find('.king-pricing-calculator__summary-items');
        var $totalPrice = $calculator.find('.king-pricing-calculator__total-price');
        
        // Use localized nonces if available, fallback to data attributes
        var addToCartNonce = addToCartNonce || $calculator.data('addToCartNonce');
        var sendEmailNonce = sendEmailQuoteNonce || $calculator.data('sendEmailNonce');
        var recipientEmail = $calculator.data('recipientEmail');
        var emailSubject = $calculator.data('emailSubject');
        
        // Try to get conditional rules from data attribute, accounting for possible casing issues
        var conditionalRules;
        try {
            // First try direct data attribute access
            conditionalRules = $calculator.data('conditionalRules');
            
            // If that doesn't work, try to get the raw attribute and parse it
            if (!conditionalRules && $calculator.attr('data-conditional-rules')) {
                console.log('Found data-conditional-rules attribute, trying to parse:', $calculator.attr('data-conditional-rules'));
                conditionalRules = JSON.parse($calculator.attr('data-conditional-rules'));
            }
            
            console.log('Conditional rules loaded:', conditionalRules);
        } catch (e) {
            console.error('Error parsing conditional rules:', e);
        }

        // Add to Cart Integration
        $calculator.find('.king-pricing-calculator-pro__add-to-cart-button').on('click', function() {
            var price = parseFloat($totalPrice.text().replace(/[^0-9\.\-]/g, '')) || 0;
            var productId = $(this).data('productId') || '';
            var includeDetails = $(this).data('includeDetails') === 'yes';
            var productType = productId ? 'specific' : 'dynamic';
            var data = {
                action: 'king_addons_add_to_cart',
                nonce: addToCartNonce,
                price: price,
                product_type: productType,
                product_id: productId,
                quantity: 1,
                use_as_budget: includeDetails
            };
            $.post(ajaxUrl, data, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.fragments) {
                        $.each(response.data.fragments, function(key, html) {
                            $(key).replaceWith(html);
                        });
                    }
                } else {
                    alert(response.data.message || 'Error adding to cart.');
                }
            });
        });

        // Email Quote Option
        var $emailButton = $calculator.find('.king-pricing-calculator-pro__email-button');
        var $emailForm = $calculator.find('.king-pricing-calculator-pro__email-form');
        $emailButton.on('click', function() {
            $emailForm.toggle();
        });
        $emailForm.find('.king-pricing-calculator-pro__cancel-email-button').on('click', function() {
            $emailForm.hide();
        });
        $emailForm.find('.king-pricing-calculator-pro__send-email-button').on('click', function() {
            var name = $emailForm.find('#quote_name').val();
            var email = $emailForm.find('#quote_email').val();
            var message = $emailForm.find('#quote_message').val();
            var quoteHtml = $summaryItems.html();
            var totalHtml = '<div class="king-pricing-calculator__summary-item"><span class="king-pricing-calculator__summary-label">Total</span><span class="king-pricing-calculator__summary-value">' + $totalPrice.text() + '</span></div>';
            var data = {
                action: 'king_addons_send_email_quote',
                nonce: sendEmailNonce,
                name: name,
                email: email,
                message: message,
                quote_data: quoteHtml + totalHtml,
                recipient: recipientEmail,
                subject: emailSubject
            };
            $.post(ajaxUrl, data, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $emailForm.hide();
                } else {
                    alert(response.data.message || 'Error sending quote.');
                }
            });
        });

        // Generate PDF Quote (print to PDF)
        $calculator.find('.king-pricing-calculator-pro__pdf-button').on('click', function() {
            // Create a more styled printable version with better formatting
            var calculatorTitle = $calculator.find('.king-pricing-calculator__title').text() || document.title;
            var calculatorDesc = $calculator.find('.king-pricing-calculator__description').text() || '';
            var logo = $calculator.data('companyLogo') || '';
            var logoHtml = logo ? '<img src="' + logo + '" alt="Company Logo" style="max-width: 200px; margin-bottom: 20px;">' : '';
            
            // Get current date in a nice format
            var today = new Date();
            var date = today.toLocaleDateString();
            
            // Create a well-formatted HTML document for printing
            var content = '<html><head>' + 
                          '<title>' + calculatorTitle + ' - Quote</title>' +
                          '<style>' +
                          'body { font-family: Arial, sans-serif; padding: 30px; max-width: 800px; margin: 0 auto; color: #333; }' +
                          '.quote-header { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }' +
                          '.quote-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; color: #2c3e50; }' +
                          '.quote-date { font-size: 14px; color: #7f8c8d; margin-bottom: 15px; }' +
                          '.quote-description { margin-bottom: 20px; }' +
                          '.quote-items { margin-bottom: 30px; }' +
                          '.quote-item { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }' +
                          '.quote-item:last-child { border-bottom: none; }' +
                          '.quote-label { font-weight: normal; }' +
                          '.quote-value { font-weight: bold; }' +
                          '.quote-total { padding: 15px 0; border-top: 2px solid #2c3e50; display: flex; justify-content: space-between; font-weight: bold; }' +
                          '.quote-total .quote-label { font-size: 18px; }' +
                          '.quote-total .quote-value { font-size: 18px; color: #2c3e50; }' +
                          '</style>' +
                          '</head><body>' + 
                          '<div class="quote-header">' +
                          logoHtml +
                          '<div class="quote-title">' + calculatorTitle + '</div>' +
                          '<div class="quote-date">Date: ' + date + '</div>' +
                          (calculatorDesc ? '<div class="quote-description">' + calculatorDesc + '</div>' : '') +
                          '</div>' +
                          '<div class="quote-items">';
            
            // Add all line items
            $summaryItems.find('.king-pricing-calculator__summary-item').each(function() {
                var label = $(this).find('.king-pricing-calculator__summary-label').text();
                var value = $(this).find('.king-pricing-calculator__summary-value').text();
                content += '<div class="quote-item">' +
                           '<span class="quote-label">' + label + '</span>' +
                           '<span class="quote-value">' + value + '</span>' +
                           '</div>';
            });
            
            // Add the total
            content += '</div>' +
                       '<div class="quote-total">' +
                       '<span class="quote-label">Total</span>' +
                       '<span class="quote-value">' + $totalPrice.text() + '</span>' +
                       '</div>' +
                       '</body></html>';
            
            // Open a new window, write the content, and trigger print
            var win = window.open('', '_blank');
            win.document.write(content);
            win.document.close();
            
            // Slight delay to ensure content is loaded before printing
            setTimeout(function() {
                win.focus();
                win.print();
                // Don't close automatically to allow users to save as PDF
                // win.close();
            }, 250);
        });

        // Save/Load Calculations using localStorage
        var storageKey = calculatorId + '_data';
        
        $calculator.find('.king-pricing-calculator-pro__save-button').on('click', function() {
            try {
                var fieldsData = {};
                var hasValues = false;
                
                // Collect all field values
                $calculator.find('.king-pricing-calculator__field').each(function() {
                    var $field = $(this);
                    var id = $field.data('field-id');
                    var type = $field.data('field-type');
                    var val;
                    
                    if (type === 'checkbox' || type === 'switch') {
                        val = $field.find('input[type="checkbox"]').prop('checked');
                    } else if (type === 'radio') {
                        val = $field.find('input[type="radio"]:checked').val();
                    } else {
                        val = $field.find('input, select').val();
                    }
                    
                    if (val !== undefined && val !== null && val !== '') {
                        hasValues = true;
                    }
                    
                    fieldsData[id] = val;
                });
                
                // Add metadata
                fieldsData._meta = {
                    date: new Date().toISOString(),
                    total: $totalPrice.text(),
                    calculatorId: calculatorId
                };
                
                if (!hasValues) {
                    alert('No values to save. Please fill in the calculator fields first.');
                    return;
                }
                
                // Save to localStorage
                localStorage.setItem(storageKey, JSON.stringify(fieldsData));
                alert('Calculation saved successfully.');
            } catch (error) {
                console.error('Error saving calculation:', error);
                alert('There was an error saving your calculation. Please try again.');
            }
        });
        
        $calculator.find('.king-pricing-calculator-pro__load-button').on('click', function() {
            try {
                var saved = localStorage.getItem(storageKey);
                if (!saved) {
                    alert('No saved calculation found for this calculator.');
                    return;
                }
                
                var fieldsData = JSON.parse(saved);
                var foundFields = false;
                
                // Check if metadata matches this calculator
                if (fieldsData._meta && fieldsData._meta.calculatorId && fieldsData._meta.calculatorId !== calculatorId) {
                    alert('The saved calculation is for a different calculator. Unable to load.');
                    return;
                }
                
                // Apply the saved values to fields
                $.each(fieldsData, function(id, val) {
                    // Skip metadata
                    if (id === '_meta') return;
                    
                    var $field = $calculator.find('[data-field-id="' + id + '"]');
                    if ($field.length) {
                        foundFields = true;
                        var type = $field.data('field-type');
                        
                        if (type === 'checkbox' || type === 'switch') {
                            $field.find('input[type="checkbox"]').prop('checked', val);
                        } else if (type === 'radio') {
                            $field.find('input[type="radio"]').filter('[value="' + val + '"]').prop('checked', true);
                        } else {
                            $field.find('input, select').val(val).trigger('change');
                        }
                    }
                });
                
                if (!foundFields) {
                    alert('No saved values could be applied to this calculator.');
                    return;
                }
                
                // Recalculate with the loaded values
                calculatePrice();
                
                // Show success message with saved date if available
                var savedDate = fieldsData._meta && fieldsData._meta.date ? 
                    new Date(fieldsData._meta.date).toLocaleString() : '';
                
                if (savedDate) {
                    alert('Calculation loaded successfully. (Saved on: ' + savedDate + ')');
                } else {
                    alert('Calculation loaded successfully.');
                }
            } catch (error) {
                console.error('Error loading calculation:', error);
                alert('There was an error loading your calculation. The saved data may be corrupted.');
            }
        });

        // Conditional Logic
        if (conditionalRules) {
            console.log('Conditional rules found:', conditionalRules);
            function evaluateCondition(val, operator, expected) {
                try {
                    // Convert values for proper comparison
                    var compVal = val;
                    var compExpected = expected;
                    
                    // For numeric comparisons, explicitly convert to numbers
                    if (operator === 'greater' || operator === 'less') {
                        compVal = parseFloat(val);
                        compExpected = parseFloat(expected);
                        
                        // Check if we have valid numbers
                        if (isNaN(compVal) || isNaN(compExpected)) {
                            console.warn('Invalid numeric comparison with NaN:', val, operator, expected);
                            return false;
                        }
                    }
                    
                    // Special handling for boolean values
                    if (operator === 'is_checked' || operator === 'is_not_checked') {
                        // These operators don't use the expected value, they check the val directly
                        return operator === 'is_checked' ? compVal === true : compVal === false;
                    }
                    
                    // Log the comparison
                    console.log('Comparing:', compVal, operator, compExpected);
                    
                    // Perform the comparison
                    switch (operator) {
                        case 'equal': return compVal == compExpected;
                        case 'not_equal': return compVal != compExpected;
                        case 'greater': return compVal > compExpected;
                        case 'less': return compVal < compExpected;
                        case 'contains': return ('' + compVal).indexOf(compExpected) !== -1;
                        default: return false;
                    }
                } catch (e) {
                    console.error('Error evaluating condition:', e);
                    return false;
                }
            }
            $.each(conditionalRules, function(_, rule) {
                console.log('Processing rule:', rule);
                
                // Improve field selection to handle both custom IDs and auto-generated IDs
                var $ifField, $targetField;
                
                // Try to find the "if" field, first by exact ID, then by custom ID
                $ifField = $calculator.find('[data-field-id="' + rule.if_field + '"]');
                if (!$ifField.length) {
                    // Try finding a field with a custom ID (king-calc-*)
                    $ifField = $calculator.find('[data-field-id="king-calc-' + rule.if_field + '"]');
                }
                
                // Try to find the target field, first by exact ID, then by custom ID
                $targetField = $calculator.find('[data-field-id="' + rule.target_field + '"]');
                if (!$targetField.length) {
                    // Try finding a field with a custom ID (king-calc-*)
                    $targetField = $calculator.find('[data-field-id="king-calc-' + rule.target_field + '"]');
                }
                
                console.log('Condition field found:', $ifField.length > 0, 'Target field found:', $targetField.length > 0);
                
                // Log actual field IDs to help debug
                if ($ifField.length > 0) {
                    console.log('If field ID:', $ifField.data('field-id'));
                }
                if ($targetField.length > 0) {
                    console.log('Target field ID:', $targetField.data('field-id'));
                }
                
                // If either field is not found, skip this rule
                if (!$ifField.length || !$targetField.length) {
                    console.warn('Could not find one or both fields for this rule, skipping');
                    return true; // Continue to next rule
                }
                
                function applyRule() {
                    var val;
                    var type = $ifField.data('field-type');
                    
                    // Log the field type to debug
                    console.log('Evaluating field type:', type);
                    
                    if (type === 'checkbox' || type === 'switch') {
                        val = $ifField.find('input[type="checkbox"]').prop('checked');
                    } else if (type === 'radio') {
                        val = $ifField.find('input[type="radio"]:checked').val();
                    } else {
                        val = $ifField.find('input, select').val();
                    }
                    
                    // Log the actual field value
                    console.log('Field value:', val, 'comparing with:', rule.value, 'using operator:', rule.operator);
                    
                    var result = evaluateCondition(val, rule.operator, rule.value);
                    console.log('Condition evaluation result:', result);
                    
                    // Apply the action based on the result
                    try {
                        switch (rule.action) {
                            case 'show':
                                // Use CSS display property to properly show/hide
                                if (result) {
                                    $targetField.css('display', ''); // Use default display
                                } else {
                                    $targetField.css('display', 'none');
                                }
                                break;
                            case 'hide':
                                // Use CSS display property to properly show/hide (inverse of show)
                                if (!result) {
                                    $targetField.css('display', ''); // Use default display
                                } else {
                                    $targetField.css('display', 'none');
                                }
                                break;
                            case 'enable':
                                $targetField.find('input, select').prop('disabled', !result);
                                if (!result) {
                                    $targetField.addClass('king-pricing-calculator__field--disabled');
                                } else {
                                    $targetField.removeClass('king-pricing-calculator__field--disabled');
                                }
                                break;
                            case 'disable':
                                $targetField.find('input, select').prop('disabled', result);
                                if (result) {
                                    $targetField.addClass('king-pricing-calculator__field--disabled');
                                } else {
                                    $targetField.removeClass('king-pricing-calculator__field--disabled');
                                }
                                break;
                            case 'set_value':
                                if (result) {
                                    var $input = $targetField.find('input, select');
                                    $input.val(rule.set_value).trigger('change');
                                    console.log('Set value to:', rule.set_value);
                                }
                                break;
                        }
                    } catch (e) {
                        console.error('Error applying action:', e);
                    }
                    
                    // Log the action taken
                    console.log('Applied action:', rule.action, 'with result:', result);
                }
                $ifField.on('change input', applyRule);
                
                // Also execute immediately once to set initial state
                applyRule();
            });
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initPricingCalculator();
    });
    
    // Initialize after Elementor frontend init
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-pricing-calculator.default', function() {
                initPricingCalculator();
            });
        }
    });
    
})(jQuery); 