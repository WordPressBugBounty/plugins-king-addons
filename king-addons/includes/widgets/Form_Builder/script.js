"use strict";
(function ($) {
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-form-builder.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(elementorModules.frontend.handlers.Base.extend({
                onInit: function onInit() {
                    let $scope = this.$element;

                    // console.log("FORM BUILDER");

                    var formContent = {};

                    var fileUrl = {};

                    if ($scope.find('#g-recaptcha-response').length > 0 && $scope.find('#g-recaptcha-response').data('site-key')) {
                        var script = document.createElement('script');
                        script.src = 'https://www.google.com/recaptcha/api.js?render=' + $scope.find('#g-recaptcha-response').data('site-key') + '';
                        document.body.appendChild(script);
                    }

                    var currentTab = 0; 
                    if (0 < $scope.find('.king-addons-fb-step-tab').length) {
                        // console.log(currentTab);
                        showTab(currentTab); 

                        $scope.find('.king-addons-fb-step-prev').each(function () {
                            $(this).on('click', function () {
                                nextPrev(-1);
                            });
                        });

                        $scope.find(".king-addons-fb-step-next").each(function () {
                            $(this).on('click', function () {
                                nextPrev(1);
                            });
                        });
                    }

                    var actions = $scope.find('.king-addons-form-field-type-submit').data('actions');

                    initExtraFields($scope);
                    initConditionalLogic($scope);
                    initCalculations($scope);
                    initPartialEntries($scope);

                    $scope.find('input[type="file"]').on('change', function (e) {
                        var files = this.files;
                        var thisInput = $(this);
                        var eventType = e.type;
                        handleFileValidityAndUpload(thisInput, files, eventType);
                    });

                    $scope.find('input, select, textarea').each(function () {
                        $(this).on('change', function () {
                            var $this = $(this);
                            if ('checkbox' == $this.attr('type')) {
                                var $option = $this.closest('.king-addons-form-field-option');
                                if ($option.hasClass('king-addons-checked')) {
                                    $option.removeClass('king-addons-checked');
                                } else {
                                    $option.addClass('king-addons-checked');
                                }
                            } else if ('radio' == $this.attr('type')) {
                                
                                var name = $this.attr('name');
                                var $group = $('input[type="radio"][name="' + name + '"]');

                                
                                $group.closest('.king-addons-form-field-option').removeClass('king-addons-checked');

                                
                                if ($this.is(':checked')) {
                                    $this.closest('.king-addons-form-field-option').addClass('king-addons-checked');
                                }
                            }
                        });

                        $(this).on('input change keyup', function (e) {
                            if ($(this).closest('.king-addons-select-wrap').length > 0) {
                                $(this).closest('.king-addons-select-wrap').removeClass('king-addons-form-error-wrap');
                            }
                            $(this).removeClass('king-addons-form-error');
                            $(this).closest('.king-addons-field-group').find('.king-addons-submit-error').remove();
                        });
                    });

                    $scope.find('.king-addons-button').on('click', function (e) {
                        e.preventDefault();

                        var eventType = e.type;

                        formContent = {};

                        
                        let fileUploadPromises = [];

                        if (0 < $scope.find('input[type="file"').length) {
                            $scope.find('input[type="file"]').each(function () {
                                var files = this.files;
                                var thisInput = $(this);

                                fileUploadPromises.push(handleFileValidityAndUpload(thisInput, files, eventType));
                            });

                            
                            Promise.all(fileUploadPromises)
                                .then(() => {
                                    createFormContent();

                                    
                                    if (validateForm()) {
                                        $(this).closest('form').trigger('submit');
                                    }
                                })
                                .catch((error) => {
                                    
                                    // console.error(error);
                                });
                        } else {
                            createFormContent();

                            if (validateForm()) {
                                $(this).closest('form').trigger('submit');
                            }
                        }
                    });


                    $scope.find('form').on('submit', function (e) {

                        e.preventDefault();

                        let responsesArray = [];
                        let paymentRedirect = '';

                        $scope.find('.king-addons-button>span').addClass('king-addons-loader-hidden');
                        $scope.find('.king-addons-button').find('.king-addons-double-bounce').removeClass('king-addons-loader-hidden');

                        $scope.find('.king-addons-submit-error, .king-addons-submit-success, .king-addons-submit-warning').remove();

                        function processRecaptcha(callback) {
                            if ($scope.find('#g-recaptcha-response').length > 0 && $scope.find('#g-recaptcha-response').data('site-key')) {
                                grecaptcha.ready(function () {
                                    grecaptcha.execute(KingAddonsFormBuilderData.recaptcha_v3_site_key, {action: 'submit'}).then(function (token) {
                                        
                                        $scope.find('#g-recaptcha-response').val(token);

                                        
                                        $.ajax({
                                            type: 'POST',
                                            url: KingAddonsFormBuilderData.ajaxurl,
                                            data: {
                                                action: 'king_addons_verify_recaptcha',
                                                'g-recaptcha-response': token,
                                                nonce: KingAddonsFormBuilderData.nonce
                                            },
                                            success: function (response) {
                                                if (!response.success) {
                                                    // console.log(response);
                                                    setTimeout(function () {
                                                        $scope.find('.king-addons-button').find('.king-addons-double-bounce').addClass('king-addons-loader-hidden');
                                                        $scope.find('.king-addons-button>span').removeClass('king-addons-loader-hidden');
                                                        $scope.find('form').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.recaptcha_error + '</p>');
                                                    }, 500);
                                                    callback(false); 
                                                } else {
                                                    // console.log(response);
                                                    callback(true); 
                                                }
                                            },
                                            error: function (error) {
                                                // console.log(error);
                                                setTimeout(function () {
                                                    $scope.find('.king-addons-button').find('.king-addons-double-bounce').addClass('king-addons-loader-hidden');
                                                    $scope.find('.king-addons-button>span').removeClass('king-addons-loader-hidden');
                                                    $scope.find('form').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.recaptcha_error + '</p>');
                                                }, 500);
                                                callback(false); 
                                            }
                                        });
                                    });
                                });
                            } else {
                                callback(true); 
                            }
                        }

                        
                        processRecaptcha(function (isRecaptchaSuccessful) {
                            if (isRecaptchaSuccessful) {

                                
                                var actionsObject = {
                                    emailPromise: sendEmail,
                                    submissionsPromise: createPost,
                                    mailchimpPromise: subscribeMailchimp,
                                    webhookPromise: sendWebhook,
                                    integrationsPromise: sendIntegrations
                                }

                                var firstActions = actions.filter(function (action) {
                                    return action !== 'payment';
                                });

                                Promise.all(
                                    firstActions.map((action) => {
                                        try {
                                            if (actionsObject[action + 'Promise']) {
                                                return actionsObject[action + 'Promise']();
                                            }
                                        } catch (error) {
                                            return Promise.reject(error);
                                        }
                                    })
                                )
                                    .then((responses) => {
                                        if (!actions.includes('payment')) {
                                            return responses;
                                        }
                                        var created = responses.find(function (response) {
                                            return response && response.data && response.data.action === 'king_addons_form_builder_submissions';
                                        });
                                        var submissionId = created && created.data ? created.data.post_id : 0;
                                        var accessSecret = created && created.data ? created.data.access_secret : '';
                                        return startPayment(submissionId, accessSecret).then(function (payResponse) {
                                            return responses.concat([payResponse]);
                                        });
                                    })
                                    .then((responses) => {
                                        // console.log(responses);

                                        
                                        const createPostResponse = responses.find((response) => response && response.data.action === 'king_addons_form_builder_submissions');

                                        const postId = createPostResponse ? createPostResponse.data.post_id : null;

                                        
                                        var updateMetaPromises = actions.map((action) => {
                                            if (action !== 'redirect') {
                                                action = 'king_addons_form_builder_' + action;

                                                
                                                const response = responses.find((response) => response && response.data.action === action);

                                                
                                                const message = response ? response.data.message : '';

                                                if (response && response.data.status === 'success') {
                                                    responsesArray.push('success');

                                                    if (postId) {
                                                        return updateFormActionMeta(postId, action, 'success', message);
                                                    }
                                                } else {
                                                    responsesArray.push('error');

                                                    if (postId) {
                                                        return updateFormActionMeta(postId, action, 'error', message);
                                                    }
                                                }
                                            }
                                        });

                                        return Promise.all(updateMetaPromises).then(() => {
                                            var anySuccess = responsesArray.includes('success');
                                            var anyError = responsesArray.includes('error');
                                            var paymentConfigured = actions.includes('payment');
                                            var paymentOk = responses.some(function (response) {
                                                return response && response.data
                                                    && response.data.action === 'king_addons_form_builder_payment'
                                                    && response.data.status === 'success';
                                            });
                                            var paymentFailed = paymentConfigured && !paymentOk;

                                            function firstActionMessage() {
                                                var note = '';
                                                responses.forEach(function (response) {
                                                    if (note || !response || !response.data || !response.data.message) {
                                                        return;
                                                    }
                                                    if (response.data.status === 'error' || response.success === false) {
                                                        note = String(response.data.message);
                                                    }
                                                });
                                                return note || $scope.data('settings').error_message;
                                            }

                                            // A broken Slack key must not hide a sent email. A failed
                                            // payment must, or the visitor thinks they have paid.
                                            if (anySuccess && !paymentFailed) {
                                                var successCopy = $scope.data('settings').success_message;
                                                // Provider None (and any payment with no checkout URL)
                                                // still answers success; show that copy, not the generic
                                                // “Submission successful”, or the visitor thinks they paid.
                                                if (paymentConfigured && !paymentRedirect) {
                                                    responses.forEach(function (response) {
                                                        if (response && response.data
                                                            && response.data.action === 'king_addons_form_builder_payment'
                                                            && response.data.status === 'success'
                                                            && response.data.message) {
                                                            successCopy = String(response.data.message);
                                                        }
                                                    });
                                                }
                                                $scope.find('form').append(
                                                    $('<p class="king-addons-submit-success"></p>').text(successCopy)
                                                );
                                                $scope.find('form').trigger('king-addons/form/submitted');

                                                $scope.find('button').attr('disabled', true);
                                                $scope.find('button').css('opacity', 0.6);

                                                if (anyError) {
                                                    var failedNotes = [];
                                                    responses.forEach(function (response) {
                                                        if (response && response.data && response.data.status === 'error' && response.data.message) {
                                                            failedNotes.push(String(response.data.message));
                                                        }
                                                    });
                                                    if (failedNotes.length) {
                                                        $scope.find('form').append(
                                                            $('<p class="king-addons-submit-warning"></p>').text(failedNotes.join(' '))
                                                        );
                                                    }
                                                }
                                            } else {
                                                $scope.find('form').append(
                                                    $('<p class="king-addons-submit-error"></p>').text(firstActionMessage())
                                                );
                                            }
                                        });
                                        
                                    })
                                    .catch((error) => {
                                        
                                        // console.error(error);
                                    })
                                    .then(() => {
                                        
                                        setTimeout(function () {
                                            
                                            $scope.find('.king-addons-button').find('.king-addons-double-bounce').addClass('king-addons-loader-hidden');
                                            $scope.find('.king-addons-button>span').removeClass('king-addons-loader-hidden');
                                            setTimeout(function () {
                                                // Paying comes first: a redirect
                                                // set on the form would otherwise
                                                // navigate away from the checkout.
                                                if (paymentRedirect && !responsesArray.includes('error')) {
                                                    $(location).prop('href', paymentRedirect);
                                                    return;
                                                }

                                                if (actions.includes('redirect') && responsesArray.includes('success')) {
                                                    
                                                    $(location).prop('href', $scope.find('.king-addons-form-field-type-submit').data('redirect-url'))
                                                }
                                            }, 500);
                                        }, 500);
                                    })
                                    .catch((error) => {
                                        
                                        // console.error(error);
                                    });
                            } else {
                                
                                return false;
                            }
                        });

                        function updateFormActionMeta(postId, actionName, status, message) {
                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: {
                                    action: 'king_addons_update_form_action_meta',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    post_id: postId,
                                    action_name: actionName,
                                    status: status,
                                    message: message
                                },
                            });
                        }

                        function deepCopy(obj) {
                            return JSON.parse(JSON.stringify(obj));
                        }

                        function sendEmail() {
                            var data = deepCopy(formContent);

                            for (let key in data) {
                                if (data[key][0] == 'radio' || data[key][0] == 'checkbox') {
                                    if (Array.isArray(data[key][1])) {
                                        let trueValues = data[key][1].filter(innerArray => innerArray[1] === true).map(innerArray => innerArray[0]);
                                        let trueValuesString = trueValues.join(', ');
                                        data[key][1] = trueValuesString;
                                    }
                                }
                            }

                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: {
                                    action: 'king_addons_form_builder_email',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_content: data,
                                    ...spamPayload(),
                                },
                                success: function (response) {
                                    // console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                },
                                error: function (error) {
                                    
                                    
                                    
                                }
                            });
                        }

                        /**
                         * Hand the submission to the Integrations action, which
                         * decides on the server which services to forward it to.
                         *
                         * @return {jqXHR} The request.
                         */
                        function sendIntegrations() {
                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: {
                                    ...spamPayload(),
                                    action: 'king_addons_form_builder_integrations',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_content: formContent
                                }
                            });
                        }

                        /**
                         * Ask the server to open a payment and note where to
                         * send the visitor once everything else has run.
                         *
                         * @param {number|string} submissionId Submission created by this submit, if any.
                         * @param {string} accessSecret Secret issued with that submission.
                         * @return {jqXHR} The request.
                         */
                        function startPayment(submissionId, accessSecret) {
                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: {
                                    ...spamPayload(),
                                    action: 'king_addons_form_builder_payment',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_content: formContent,
                                    king_addons_form_id: $scope.find('input[name="form_id"]').val(),
                                    form_id: $scope.find('input[name="form_id"]').val(),
                                    form_name: $scope.find('form').attr('name'),
                                    form_page: $scope.find('form').attr('page'),
                                    form_page_id: $scope.find('form').attr('page_id'),
                                    submission_id: submissionId || 0,
                                    access_secret: accessSecret || ''
                                },
                                success: function (response) {
                                    if (response && response.success && response.data && response.data.redirect) {
                                        paymentRedirect = response.data.redirect;
                                    }
                                }
                            });
                        }

                        function sendWebhook() {
                            var data = deepCopy(formContent);

                            for (let key in data) {
                                if (data[key][0] == 'radio' || data[key][0] == 'checkbox') {
                                    if (Array.isArray(data[key][1])) {
                                        let trueValues = data[key][1].filter(innerArray => innerArray[1] === true).map(innerArray => innerArray[0]);
                                        let trueValuesString = trueValues.join(', ');
                                        data[key][1] = trueValuesString;
                                    }
                                }
                            }

                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: {
                                    ...spamPayload(),
                                    action: 'king_addons_form_builder_webhook',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_content: data,
                                    king_addons_form_id: $scope.find('input[name="form_id"]').val(),
                                    form_name: $scope.find('form').attr('name')
                                },
                                success: function (response) {
                                    // console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                },
                                error: function (error) {
                                    // console.log(error);
                                    
                                    
                                    
                                }
                            });
                        }

                        function flattenChoiceFields(data) {
                            var copy = deepCopy(data);
                            for (var key in copy) {
                                if (!copy[key] || (copy[key][0] !== 'radio' && copy[key][0] !== 'checkbox')) {
                                    continue;
                                }
                                if (!Array.isArray(copy[key][1])) {
                                    continue;
                                }
                                var picked = copy[key][1]
                                    .filter(function (row) {
                                        return row && row[1] === true;
                                    })
                                    .map(function (row) {
                                        return row[0];
                                    });
                                copy[key][1] = picked.join(', ');
                            }
                            return copy;
                        }

                        function createPost() {

                            var data = {
                                ...spamPayload(),
                                action: 'king_addons_form_builder_submissions',
                                nonce: KingAddonsFormBuilderData.nonce,
                                form_content: flattenChoiceFields(formContent),
                                status: 'publish',
                                form_name: $scope.find('form').attr('name'),
                                form_id: $scope.find('input[name="form_id"]').val(),
                                form_page: $scope.find('form').attr('page'),
                                form_page_id: $scope.find('form').attr('page_id')
                            };

                            return $.ajax({
                                type: 'POST',
                                url: KingAddonsFormBuilderData.ajaxurl,
                                data: data,
                                success: function (response) {
                                    // console.log(response);
                                    
                                    
                                    
                                },
                                error: function (error) {
                                    // console.log(error)
                                    
                                    
                                    
                                }
                            });
                        }

                        function subscribeMailchimp() {

                            const submitButton = $scope.find('.king-addons-form-field-type-submit');
                            const mailchimpFields = JSON.parse(submitButton.attr('data-mailchimp-fields'));

                            let formData = {};

                            Object.keys(mailchimpFields).forEach(function (fieldId) {
                                if (fieldId == 'group_id') {

                                    var fieldValue = Array.isArray(mailchimpFields[fieldId]) ? mailchimpFields[fieldId].join(',') : mailchimpFields[fieldId];
                                } else {
                                    var fieldValue = $scope.find('#form-field-' + mailchimpFields[fieldId]).val();
                                }
                                if (fieldValue) {
                                    if (fieldId == 'birthday_field') {
                                        formData[fieldId] = convertToMailchimpBirthdayFormat(fieldValue);
                                    } else {
                                        formData[fieldId] = fieldValue;
                                    }
                                }
                            });

                            return $.ajax({
                                url: KingAddonsFormBuilderData.ajaxurl,
                                method: 'POST',
                                data: {
                                    ...spamPayload(),
                                    action: 'king_addons_form_builder_mailchimp',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_data: formData,
                                    listId: submitButton.data('list-id')
                                    
                                },
                                beforeSend: function () {
                                    submitButton.prop('disabled', true);
                                },
                                success: function (response) {
                                    // console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                    
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    // console.log(errorThrown);
                                    
                                    
                                    
                                },
                                complete: function () {
                                    submitButton.prop('disabled', false);
                                }
                            });
                        }
                    });


    /**
     * Wire up the field types that are more than a plain input: range, rating,
     * signature and acceptance.
     *
     * @param {jQuery} $scope Widget scope.
     */
    function initExtraFields($scope) {
        var root = $scope && $scope[0] ? $scope[0] : document;

        // Range: keep the printed value in step with the handle.
        root.querySelectorAll('.king-addons-form-range').forEach(function (wrap) {
            var input = wrap.querySelector('input[type="range"]');
            var output = wrap.querySelector('.king-addons-form-range__value');
            if (!input) {
                return;
            }

            var sync = function () {
                if (output) {
                    output.textContent = input.value;
                }
            };

            input.addEventListener('input', sync);
            input.addEventListener('change', sync);
            sync();
        });

        // Rating: buttons drive a hidden input so the collector sees one value.
        root.querySelectorAll('[data-ka-rating]').forEach(function (wrap) {
            if (wrap.dataset.kaRatingReady === '1') {
                return;
            }
            wrap.dataset.kaRatingReady = '1';

            var input = wrap.querySelector('input');
            var items = wrap.querySelectorAll('.king-addons-form-rating__item');

            var paint = function (upto) {
                items.forEach(function (item, index) {
                    item.classList.toggle('is-on', index < upto);
                });
            };

            items.forEach(function (item) {
                item.addEventListener('click', function () {
                    var value = parseInt(item.dataset.value || '0', 10);
                    // Clicking the current value again clears it.
                    if (input && String(value) === input.value) {
                        value = 0;
                    }
                    if (input) {
                        input.value = value ? String(value) : '';
                    }
                    paint(value);
                    wrap.classList.remove('king-addons-form-error');
                });

                item.addEventListener('mouseenter', function () {
                    paint(parseInt(item.dataset.value || '0', 10));
                });
            });

            wrap.addEventListener('mouseleave', function () {
                paint(input && input.value ? parseInt(input.value, 10) : 0);
            });
        });

        // Signature: draw on a canvas, store a PNG data URL.
        root.querySelectorAll('[data-ka-signature]').forEach(function (wrap) {
            if (wrap.dataset.kaSignatureReady === '1') {
                return;
            }
            wrap.dataset.kaSignatureReady = '1';

            var canvas = wrap.querySelector('canvas');
            var input = wrap.querySelector('input');
            var clear = wrap.querySelector('.king-addons-form-signature__clear');
            if (!canvas || !input) {
                return;
            }

            var ctx = canvas.getContext('2d');
            var drawing = false;
            var dirty = false;

            var resize = function () {
                // Re-sizing wipes the canvas, so only do it while it is empty.
                if (dirty) {
                    return;
                }
                var width = wrap.clientWidth || canvas.clientWidth;
                if (width > 0) {
                    canvas.width = width;
                }
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = getComputedStyle(canvas).color || '#000';
            };

            var point = function (event) {
                var rect = canvas.getBoundingClientRect();
                var source = event.touches && event.touches[0] ? event.touches[0] : event;
                return {
                    x: (source.clientX - rect.left) * (canvas.width / rect.width),
                    y: (source.clientY - rect.top) * (canvas.height / rect.height)
                };
            };

            var start = function (event) {
                event.preventDefault();
                drawing = true;
                var p = point(event);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            };

            var move = function (event) {
                if (!drawing) {
                    return;
                }
                event.preventDefault();
                var p = point(event);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                dirty = true;
            };

            var stop = function () {
                if (!drawing) {
                    return;
                }
                drawing = false;
                if (dirty) {
                    input.value = canvas.toDataURL('image/png');
                    wrap.classList.remove('king-addons-form-error');
                }
            };

            resize();
            window.addEventListener('resize', resize);

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            document.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', stop);

            if (clear) {
                clear.addEventListener('click', function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    input.value = '';
                    dirty = false;
                    resize();
                });
            }
        });
    }


    /**
     * Show or hide fields according to their conditional logic rules.
     *
     * @param {jQuery} $scope Widget scope.
     */
    function initConditionalLogic($scope) {
        var root = $scope && $scope[0] ? $scope[0] : document;
        var form = root.querySelector('form');
        var groups = [];

        root.querySelectorAll('[data-ka-cond]').forEach(function (group) {
            var config;
            try {
                config = JSON.parse(group.getAttribute('data-ka-cond'));
            } catch (e) {
                return;
            }

            if (config && config.rules && config.rules.length) {
                groups.push({ el: group, config: config });
            }
        });

        if (!groups.length || !form) {
            return;
        }

        /**
         * Current value of the watched field, as a string.
         *
         * @param {string} fieldId Field ID as set in the panel.
         * @return {string} Value.
         */
        function readValue(fieldId) {
            var byId = form.querySelector('#form-field-' + CSS.escape(fieldId));
            if (byId) {
                if (byId.type === 'checkbox' || byId.type === 'radio') {
                    return byId.checked ? (byId.value || '1') : '';
                }
                return byId.value || '';
            }

            // Radio and checkbox groups share a name rather than an id.
            var named = form.querySelectorAll('[name="form_fields[' + fieldId + ']"], [name="form_fields[' + fieldId + '][]"]');
            var values = [];
            Array.prototype.forEach.call(named, function (input) {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.checked) {
                        values.push(input.value);
                    }
                } else if (input.value) {
                    values.push(input.value);
                }
            });

            return values.join(', ');
        }

        /**
         * Test one rule.
         *
         * @param {Object} rule Rule.
         * @return {boolean} Whether it matches.
         */
        function test(rule) {
            var actual = String(readValue(rule.field));
            var expected = String(rule.value === undefined ? '' : rule.value);

            switch (rule.op) {
                case 'is':
                    return actual === expected;
                case 'is_not':
                    return actual !== expected;
                case 'contains':
                    return actual.toLowerCase().indexOf(expected.toLowerCase()) !== -1;
                case 'not_contains':
                    return actual.toLowerCase().indexOf(expected.toLowerCase()) === -1;
                case 'is_empty':
                    return actual.trim() === '';
                case 'is_not_empty':
                    return actual.trim() !== '';
                case 'gt':
                    return parseFloat(actual) > parseFloat(expected);
                case 'lt':
                    return parseFloat(actual) < parseFloat(expected);
            }

            return false;
        }

        /**
         * Re-evaluate every conditional field.
         */
        function apply() {
            groups.forEach(function (entry) {
                var results = entry.config.rules.map(test);
                var matched = 'any' === entry.config.relation
                    ? results.some(Boolean)
                    : results.every(Boolean);

                var visible = 'hide' === entry.config.action ? !matched : matched;

                entry.el.classList.toggle('king-addons-cond-hidden', !visible);
                if (!visible) {
                    entry.el.querySelectorAll('.king-addons-form-error').forEach(function (el) {
                        el.classList.remove('king-addons-form-error');
                    });
                    entry.el.querySelectorAll('.king-addons-submit-error').forEach(function (el) {
                        el.remove();
                    });
                }
            });
        }

        form.addEventListener('input', apply);
        form.addEventListener('change', apply);
        apply();
    }


    /**
     * Evaluate an arithmetic expression without eval().
     *
     * Shunting-yard into RPN, then a stack machine. Anything the tokenizer does
     * not recognise makes the whole expression fail rather than fall through to
     * something surprising.
     *
     * @param {string} expression Expression with numbers and + - * / ( ).
     * @return {number|null} Result, or null when the expression is not valid.
     */
    function evaluateExpression(expression) {
        var tokens = String(expression).match(/\d+\.?\d*|[-+*/()]/g);
        if (!tokens || !tokens.length) {
            return null;
        }

        // Reject anything the tokenizer skipped over: letters, symbols, stray
        // characters. Only then is what is left safe to fold.
        if (tokens.join('').length !== String(expression).replace(/\s+/g, '').length) {
            return null;
        }

        var precedence = { '+': 1, '-': 1, '*': 2, '/': 2 };
        var output = [];
        var operators = [];
        var previous = null;

        for (var i = 0; i < tokens.length; i++) {
            var token = tokens[i];

            if (/^\d/.test(token)) {
                output.push(parseFloat(token));
            } else if ('(' === token) {
                operators.push(token);
            } else if (')' === token) {
                while (operators.length && operators[operators.length - 1] !== '(') {
                    output.push(operators.pop());
                }
                if (!operators.length) {
                    return null;
                }
                operators.pop();
            } else {
                // A minus with nothing usable in front of it is a sign, not an
                // operator: turn "-5" into "0 - 5".
                if ('-' === token && (null === previous || '(' === previous || precedence[previous])) {
                    output.push(0);
                }

                while (
                    operators.length &&
                    operators[operators.length - 1] !== '(' &&
                    precedence[operators[operators.length - 1]] >= precedence[token]
                ) {
                    output.push(operators.pop());
                }
                operators.push(token);
            }

            previous = token;
        }

        while (operators.length) {
            var last = operators.pop();
            if ('(' === last) {
                return null;
            }
            output.push(last);
        }

        var stack = [];
        for (var j = 0; j < output.length; j++) {
            var item = output[j];

            if ('number' === typeof item) {
                stack.push(item);
                continue;
            }

            if (stack.length < 2) {
                return null;
            }

            var right = stack.pop();
            var left = stack.pop();

            switch (item) {
                case '+': stack.push(left + right); break;
                case '-': stack.push(left - right); break;
                case '*': stack.push(left * right); break;
                case '/':
                    if (0 === right) {
                        return null;
                    }
                    stack.push(left / right);
                    break;
                default: return null;
            }
        }

        if (1 !== stack.length || !isFinite(stack[0])) {
            return null;
        }

        return stack[0];
    }

    /**
     * Keep calculation fields in step with the fields they reference.
     *
     * @param {jQuery} $scope Widget scope.
     */
    function initCalculations($scope) {
        var root = $scope && $scope[0] ? $scope[0] : document;
        var form = root.querySelector('form');
        var fields = root.querySelectorAll('[data-ka-calc]');

        if (!form || !fields.length) {
            return;
        }

        /**
         * Numeric value of one referenced field.
         *
         * @param {string} fieldId Field ID.
         * @return {number} Value, 0 when there is nothing usable.
         */
        function numberOf(fieldId) {
            var input = form.querySelector('#form-field-' + CSS.escape(fieldId));
            var raw = '';

            if (input) {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    raw = input.checked ? input.value : '0';
                } else {
                    raw = input.value;
                }
            } else {
                var checked = form.querySelector('[name="form_fields[' + fieldId + ']"]:checked, [name="form_fields[' + fieldId + '][]"]:checked');
                if (checked) {
                    raw = checked.value;
                }
            }

            var value = parseFloat(String(raw).replace(',', '.'));

            return isFinite(value) ? value : 0;
        }

        function apply() {
            Array.prototype.forEach.call(fields, function (wrap) {
                var input = wrap.querySelector('input');
                var formula = wrap.getAttribute('data-formula') || '';

                if (!input || '' === formula.trim()) {
                    return;
                }

                var resolved = formula.replace(/\[([^\]]+)\]/g, function (match, fieldId) {
                    return '(' + numberOf(fieldId.trim()) + ')';
                });

                var result = evaluateExpression(resolved);

                if (null === result) {
                    input.value = '';
                    return;
                }

                var decimals = parseInt(wrap.getAttribute('data-decimals') || '2', 10);
                var text = result.toFixed(isFinite(decimals) ? decimals : 2);

                input.value = (wrap.getAttribute('data-prefix') || '') + text + (wrap.getAttribute('data-suffix') || '');
            });
        }

        form.addEventListener('input', apply);
        form.addEventListener('change', apply);
        apply();
    }


                    /**
                     * Honeypot value and captcha token for this form.
                     *
                     * Every submit action is its own request, and each one is
                     * checked on the server, so the challenge has to travel with
                     * all of them.
                     *
                     * @return {Object} Extra request fields.
                     */
                    function spamPayload() {
                        var extra = {
                            king_addons_form_id: $scope.find('input[name="form_id"]').val()
                        };

                        var honeypot = $scope.find('.king-addons-form-honeypot__input');
                        if (honeypot.length) {
                            extra[honeypot.attr('name')] = honeypot.val();
                        }

                        var captcha = $scope.find('[data-ka-captcha]');
                        if (captcha.length) {
                            var token = captcha.find('[name="cf-turnstile-response"], [name="h-captcha-response"], [name="g-recaptcha-response"]').val();
                            extra.ka_captcha_token = token || '';
                        }

                        return extra;
                    }


    /**
     * Save what the visitor has typed, before they submit.
     *
     * @param {jQuery} $scope Widget scope.
     */
    function initPartialEntries($scope) {
        var submitGroup = $scope.find('.king-addons-form-field-type-submit');

        if ('1' !== String(submitGroup.data('partial-entries') || '')) {
            return;
        }

        var form = $scope.find('form');
        if (!form.length) {
            return;
        }

        var formId = $scope.find('input[name="form_id"]').val();
        var storageKey = 'kaPartial:' + formId;
        var entryKey = '';

        try {
            entryKey = window.sessionStorage.getItem(storageKey) || '';
        } catch (e) {
            entryKey = '';
        }

        if (!entryKey) {
            // One key per visit, so a returning visitor updates their own row
            // instead of starting another.
            entryKey = 'ka' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
            try {
                window.sessionStorage.setItem(storageKey, entryKey);
            } catch (e) {
                // Private browsing: the entry simply will not be reused.
            }
        }

        var timer = null;
        var lastSent = '';

        /**
         * Send the current values, at most once every couple of seconds.
         */
        function save() {
            createFormContent();

            var snapshot = JSON.stringify(formContent);
            if (snapshot === lastSent || '{}' === snapshot) {
                return;
            }
            lastSent = snapshot;

            // Send a copy: formContent is shared with the submit path and is
            // rebuilt from scratch there.
            var payload = JSON.parse(snapshot);

            $.ajax({
                type: 'POST',
                url: KingAddonsFormBuilderData.ajaxurl,
                data: {
                    action: 'king_addons_form_builder_partial',
                    nonce: KingAddonsFormBuilderData.nonce,
                    king_addons_form_id: formId,
                    form_page_id: form.attr('page_id'),
                    entry_key: entryKey,
                    form_content: payload
                }
            });
        }

        form.on('input change', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(save, 2000);
        });

        // A finished submission is the record; the draft is no longer wanted.
        form.on('king-addons/form/submitted', function () {
            try {
                window.sessionStorage.removeItem(storageKey);
            } catch (e) {
                // Nothing to clean up.
            }

            $.ajax({
                type: 'POST',
                url: KingAddonsFormBuilderData.ajaxurl,
                data: {
                    action: 'king_addons_form_builder_partial',
                    nonce: KingAddonsFormBuilderData.nonce,
                    king_addons_form_id: formId,
                    form_page_id: form.attr('page_id'),
                    entry_key: entryKey,
                    finalise: 1
                }
            });
        });
    }

                    function createFormContent() {
                        $scope.find('.king-addons-form-field, .king-addons-form-field-type-radio, .king-addons-form-field-type-checkbox, .king-addons-fb-step-input').each(function () {

                            // A field hidden by conditional logic is not part of
                            // the submission.
                            if ($(this).closest('.king-addons-cond-hidden').length) {
                                return;
                            }

                            // prev('label') always returns a jQuery object, so the
                            // old else branch never ran; and the newer field types
                            // wrap their input, which puts the label out of reach
                            // of prev(). Fall back to the field group's own label.
                            var label = $(this).prev('label').text().trim();
                            if ('' === label) {
                                label = $(this).closest('.king-addons-field-group')
                                    .children('.king-addons-form-field-label')
                                    .first().text().trim();
                            }

                            if ('textarea' !== $(this).prop('tagName').toLowerCase()) {
                                if ($(this).hasClass('king-addons-select-wrap')) {
                                    var selectValue = $(this).find('select').val();
                                    if (Array.isArray($(this).find('select').val())) {
                                        selectValue = $(this).find('select').val().join(', ');
                                    } else {
                                        selectValue = $(this).find('select').val();
                                    }
                                    formContent[$(this).find('select').attr('id').replace('-', '_')] = ['select', selectValue, label];
                                } else if ($(this).hasClass('king-addons-form-field-type-radio') || $(this).hasClass('king-addons-form-field-type-checkbox')) {
                                    var valuesArray = [];
                                    var checkedField = $(this).find('input');
                                    var type;
                                    checkedField.each(function () {
                                        valuesArray.push([$(this).val(), $(this).is(':checked'), $(this).attr('name'), $(this).attr('id')]);
                                    });

                                    if ($(this).hasClass('king-addons-form-field-type-radio')) {
                                        type = 'radio'
                                    } else {
                                        type = 'checkbox';
                                    }

                                    var inputLabel = $(this).find('.king-addons-form-field-label').text().trim();

                                    if (checkedField.length > 0) {
                                        formContent[$(this).find('.king-addons-form-field-option').data('key').replace('-', '_')] = [type, valuesArray, inputLabel];
                                    }
                                } else if ($(this).attr('type') === 'checkbox' && $(this).closest('.king-addons-field-group').hasClass('king-addons-form-field-type-acceptance')) {
                                    // A lone consent checkbox: val() would report
                                    // its value attribute whether it is ticked or not.
                                    formContent[$(this).attr('id').replace('-', '_')] = ['acceptance', $(this).is(':checked') ? $(this).val() : '', label];
                                } else if ($(this).hasClass('king-addons-fb-step-input')) {
                                    formContent[$(this).attr('id').replace('-', '_')] = [$(this).attr('type'), '', $(this).val(), label];
                                } else {
                                    if ($(this).attr('type') == 'file') {
                                        formContent[$(this).attr('id').replace('-', '_')] = [$(this).attr('type'), fileUrl[$(this).attr('id')], label];
                                    } else {
                                        formContent[$(this).attr('id').replace('-', '_')] = [$(this).attr('type'), $(this).val(), label];
                                    }
                                }
                            } else {
                                formContent[$(this).attr('id').replace('-', '_')] = [$(this).prop('tagName').toLowerCase(), $(this).val(), label];
                            }

                        });
                    }

                    function handleFileValidityAndUpload(thisInput, files, eventType) {
                        var thisId = thisInput.attr('id');

                        if (0 < thisInput.closest('.king-addons-field-group').find('.king-addons-submit-error').length) {
                            thisInput.closest('.king-addons-field-group').find('.king-addons-submit-error').remove();
                        }

                        
                        var maxFileSize = thisInput.data('maxfs') ? thisInput.data('maxfs') : 0;
                        var allowedFileTypes = thisInput.data('allft') ? thisInput.data('allft') : 0;

                        
                        let uploadPromises = [];

                        for (let i = 0; i < files.length; i++) {
                            var fileInput = files[i];

                            
                            var formDataForFile = new FormData();
                            formDataForFile.append('action', 'king_addons_upload_file');
                            formDataForFile.append('uploaded_file', fileInput);
                            formDataForFile.append('max_file_size', maxFileSize);
                            formDataForFile.append('allowed_file_types', allowedFileTypes);
                            formDataForFile.append('triggering_event', eventType);
                            formDataForFile.append('king_addons_fb_nonce', KingAddonsFormBuilderData.nonce);

                            if ('click' == eventType) {
                                if (!fileUrl[thisId]) {
                                    fileUrl[thisId] = [];
                                }
                            }

                            
                            uploadPromises.push(
                                new Promise((resolve, reject) => {
                                    $.ajax({
                                        url: KingAddonsFormBuilderData.ajaxurl,
                                        type: 'POST',
                                        data: formDataForFile,
                                        processData: false,
                                        contentType: false,
                                        success: function (response) {
                                            if (response.success) {
                                                
                                                // console.log(response);
                                                if (eventType == 'click') {
                                                    fileUrl[thisId][i] = response.data.url;
                                                }
                                                resolve(response);
                                            } else {
                                                // console.error('Error:', response);
                                                if (response.data) {
                                                    if ('filesize' === response.data.cause) {
                                                        let maxFileNotice = thisInput.data('maxfs-notice') ? thisInput.data('maxfs-notice') : response.data.message;
                                                        thisInput.closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + maxFileNotice + '</p>');
                                                    }

                                                    if ('filetype' == response.data.cause) {
                                                        thisInput.closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + response.data.message + '</p>');
                                                    }
                                                }

                                                reject(response);
                                            }
                                        },
                                        error: function (error) {
                                            if ('filesize' === error.cause) {
                                                let maxFileNotice = thisInput.data('maxfs-notice') ? thisInput.data('maxfs-notice') : error.message;
                                                thisInput.closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + maxFileNotice + '</p>');
                                            }

                                            if ('filetype' == error.cause) {
                                                thisInput.closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + error.message + '</p>');
                                            }
                                            // console.log(error);
                                            reject(error);
                                        },
                                    });
                                }),
                            );
                        }

                        
                        return Promise.all(uploadPromises);
                    }

                    function convertToMailchimpBirthdayFormat(dateString) {
                        const date = new Date(dateString);
                        const month = (date.getMonth() + 1).toString().padStart(2, '0');
                        const day = date.getDate().toString().padStart(2, '0');
                        return `${month}/${day}`;
                    }

                    function showTab(n) {
                        
                        var $stepTab = $scope.find(".king-addons-fb-step-tab");
                        $stepTab.eq(n).removeClass('king-addons-fb-step-tab-hidden');
                        
                        if (n === 0) {
                            $scope.find(".king-addons-fb-step-prev").hide();
                        } else {
                            $scope.find(".king-addons-fb-step-prev").show();
                        }
                        
                        fixStepIndicator(n);
                    }

                    function nextPrev(n) {
                        
                        var $stepTab = $scope.find(".king-addons-fb-step-tab");

                        
                        if (n === 1 && !validateForm()) {
                            return false;
                        }
                        
                        $stepTab.eq(currentTab).addClass('king-addons-fb-step-tab-hidden');
                        
                        currentTab = currentTab + n;
                        
                        if (currentTab >= $stepTab.length) {
                            
                            $scope.find("form").submit();
                            return false;
                        }
                        
                        showTab(currentTab);
                    }

                    function validateForm() {
                        var valid = true;
                        var $stepTab = $scope.find(".king-addons-fb-step-tab");
                        if (!($stepTab.length > 0)) {
                            $stepTab = $scope.find('.king-addons-form-fields-wrap');
                            currentTab = 0;
                        }
                        var $types = ['text', 'email', 'password', 'file', 'url', 'tel', 'number', 'date', 'datetime-local', 'time', 'week', 'month', 'color']; 

                        $stepTab.eq(currentTab).find('input, select, textarea').each(function () {
                            if ($(this).closest('.king-addons-cond-hidden').length) {
                                return;
                            }

                            const type = $(this).attr('type');

                            var requiredField = $(this).closest('.king-addons-field-group').find('.king-addons-form-field').attr('required') === 'required' || $(this).closest('.king-addons-field-group').find('.king-addons-form-field-textual').attr('required') === 'required';

                            
                            
                            

                            if (type !== undefined && $.inArray(type, $types) !== -1 && $(this).val() === '' && requiredField) {
                                
                                $(this).addClass("king-addons-form-error");
                                
                                valid = false;
                            } else if (type === 'radio' || type === 'checkbox') {
                                let requiredOption = $(this).closest('.king-addons-field-group').find('.king-addons-form-field-option input').attr('required') === 'required';

                                if (requiredOption && $stepTab.eq(currentTab).find('input[type="' + type + '"]:checked').length === 0) {
                                    
                                    $(this).addClass("king-addons-form-error");
                                    
                                    valid = false;
                                }
                            } else if (requiredField && this.tagName === 'SELECT' && $(this).val().trim() === '') {
                                
                                $(this).closest('.king-addons-select-wrap').addClass('king-addons-form-error-wrap');
                                
                                $(this).addClass("king-addons-form-error");
                                
                                valid = false;
                            } else if (requiredField && this.tagName === 'TEXTAREA' && $(this).val().trim() === '') {
                                
                                $(this).addClass("king-addons-form-error");
                                
                                valid = false;
                            }
                        });

                        // Rating, signature and acceptance carry their value in a
                        // hidden input or a lone checkbox, so the loop above never
                        // sees them as required.
                        $stepTab.eq(currentTab).find('[data-ka-rating][data-ka-required], [data-ka-signature][data-ka-required]').not('.king-addons-cond-hidden *').each(function () {
                            var holder = $(this).find('input');
                            if (!holder.length || '' === String(holder.val() || '').trim()) {
                                $(this).addClass('king-addons-form-error');
                                valid = false;
                            } else {
                                $(this).removeClass('king-addons-form-error');
                            }
                        });

                        $stepTab.eq(currentTab).find('.king-addons-form-field-type-acceptance:not(.king-addons-cond-hidden) input[type="checkbox"][required]').each(function () {
                            if (!$(this).is(':checked')) {
                                $(this).addClass('king-addons-form-error');
                                valid = false;
                            } else {
                                $(this).removeClass('king-addons-form-error');
                            }
                        });

                        if (!valid) {
                            $stepTab.eq(currentTab).find('.king-addons-form-error, .king-addons-form-error-wrap').each(function () {
                                if (!($(this).closest('.king-addons-field-group').find('.king-addons-submit-error').length > 0)) {
                                    if ($(this).attr('type') == 'file') {
                                        $(this).closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.file_empty + '</p>');
                                    } else if ($(this).is('select') || $(this).attr('type') === 'radio' || $(this).attr('type') === 'checkbox') {
                                        $(this).closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.select_empty + '</p>');
                                    } else {
                                        $(this).closest('.king-addons-field-group').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.input_empty + '</p>');
                                    }
                                }
                            });
                        }

                        if (valid) {
                            $scope.find(".king-addons-fb-step").eq(currentTab).addClass("king-addons-fb-step-finish");
                        } else {
                            if ($scope.find(".king-addons-fb-step").eq(currentTab).hasClass('king-addons-fb-step-finish')) {
                                $scope.find(".king-addons-fb-step").eq(currentTab).removeClass('king-addons-fb-step-finish');
                            }
                        }

                        return valid;
                    }

                    function fixStepIndicator(n) {
                        
                        var $step = $scope.find(".king-addons-fb-step");
                        $step.removeClass("king-addons-fb-step-active");
                        
                        $step.eq(n).addClass("king-addons-fb-step-active");

                        if ($scope.find('.king-addons-fb-step-active').hasClass('king-addons-fb-step-finish')) {
                            $scope.find('.king-addons-fb-step-active').removeClass('king-addons-fb-step-finish');
                        }

                        const stepTabs = $scope.find('.king-addons-fb-step-tab');
                        const progressBarFill = $scope.find('.king-addons-fb-step-progress-fill');

                        let currentStep = n + 1;

                        updateProgressBar()

                        function updateProgressBar() {
                            const totalSteps = stepTabs.length;
                            const progressPercentage = (currentStep / totalSteps) * 100;

                            progressBarFill.css('width', progressPercentage + '%');
                            setTimeout(function () {
                                progressBarFill.text(Math.round(progressPercentage) + '%');
                            }, 500);
                        }
                    }


                    
                },
            }), {
                $element: $scope
            });
        });
    });
})(jQuery);