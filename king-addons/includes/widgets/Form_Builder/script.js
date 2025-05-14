"use strict";
(function ($) {
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-form-builder.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(elementorModules.frontend.handlers.Base.extend({
                onInit: function onInit() {
                    let $scope = this.$element;

                    console.log("FORM BUILDER");

                    var formContent = {};

                    var fileUrl = {};

                    if ($('body').find('.king-addons-form-field-type-recaptcha-v3').length > 0) {
                        var script = document.createElement('script');
                        script.src = 'https://www.google.com/recaptcha/api.js?render=' + $scope.find('#g-recaptcha-response').data('site-key') + '';
                        document.body.appendChild(script);
                    }

                    var currentTab = 0; 
                    if (0 < $scope.find('.king-addons-fb-step-tab').length) {
                        console.log(currentTab);
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
                                    
                                    console.error(error);
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

                        $scope.find('.king-addons-button>span').addClass('king-addons-loader-hidden');
                        $scope.find('.king-addons-button').find('.king-addons-double-bounce').removeClass('king-addons-loader-hidden');

                        if ($scope.find('.king-addons-submit-error')) {
                            $scope.find('.king-addons-submit-error').remove();
                        }

                        if ($scope.find('.king-addons-submit-success')) {
                            $scope.find('.king-addons-submit-success').remove();
                        }

                        function processRecaptcha(callback) {
                            if ($scope.find('#g-recaptcha-response').length > 0) {
                                grecaptcha.ready(function () {
                                    grecaptcha.execute(KingAddonsFormBuilderData.recaptcha_v3_site_key, {action: 'submit'}).then(function (token) {
                                        
                                        $scope.find('#g-recaptcha-response').val(token);

                                        
                                        $.ajax({
                                            type: 'POST',
                                            url: KingAddonsFormBuilderData.ajaxurl,
                                            data: {
                                                action: 'king_addons_verify_recaptcha',
                                                'g-recaptcha-response': token
                                            },
                                            success: function (response) {
                                                if (!response.success) {
                                                    console.log(response);
                                                    setTimeout(function () {
                                                        $scope.find('.king-addons-button').find('.king-addons-double-bounce').addClass('king-addons-loader-hidden');
                                                        $scope.find('.king-addons-button>span').removeClass('king-addons-loader-hidden');
                                                        $scope.find('form').append('<p class="king-addons-submit-error">' + KingAddonsFormBuilderData.recaptcha_error + '</p>');
                                                    }, 500);
                                                    callback(false); 
                                                } else {
                                                    console.log(response);
                                                    callback(true); 
                                                }
                                            },
                                            error: function (error) {
                                                console.log(error);
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
                                    webhookPromise: sendWebhook
                                }

                                
                                Promise.all(
                                    actions.map((action) => {
                                        try {
                                            if (actionsObject[action + 'Promise']) {
                                                return actionsObject[action + 'Promise']();
                                            }
                                        } catch (error) {
                                            console.error(error);
                                            return Promise.reject(error);
                                        }
                                    })
                                )
                                    .then((responses) => {
                                        console.log(responses);

                                        
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
                                            if (responsesArray.includes('error')) {
                                                var sanitizedErrorMessage = $('<div>').text($scope.data('settings').error_message).html();
                                                $scope.find('form').append('<p class="king-addons-submit-error">' + sanitizedErrorMessage + '</p>');
                                            } else {
                                                $scope.find('form').append(
                                                    $('<p class="king-addons-submit-success"></p>').text($scope.data('settings').success_message)
                                                );
                                                
                                                $scope.find('button').attr('disabled', true);
                                                $scope.find('button').css('opacity', 0.6);
                                            }
                                        });
                                        
                                    })
                                    .catch((error) => {
                                        
                                        console.error(error);
                                    })
                                    .then(() => {
                                        
                                        setTimeout(function () {
                                            
                                            $scope.find('.king-addons-button').find('.king-addons-double-bounce').addClass('king-addons-loader-hidden');
                                            $scope.find('.king-addons-button>span').removeClass('king-addons-loader-hidden');
                                            setTimeout(function () {
                                                if (actions.includes('redirect') && responsesArray.includes('success')) {
                                                    
                                                    $(location).prop('href', $scope.find('.king-addons-form-field-type-submit').data('redirect-url'))
                                                }
                                            }, 500);
                                        }, 500);
                                    })
                                    .catch((error) => {
                                        
                                        console.error(error);
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
                                    king_addons_form_id: $scope.find('input[name="form_id"]').val(),
                                },
                                success: function (response) {
                                    console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                },
                                error: function (error) {
                                    
                                    
                                    
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
                                    action: 'king_addons_form_builder_webhook',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_content: data,
                                    king_addons_form_id: $scope.find('input[name="form_id"]').val(),
                                    form_name: $scope.find('form').attr('name')
                                },
                                success: function (response) {
                                    console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                },
                                error: function (error) {
                                    console.log(error);
                                    
                                    
                                    
                                }
                            });
                        }

                        function createPost() {

                            var data = {
                                action: 'king_addons_form_builder_submissions',
                                nonce: KingAddonsFormBuilderData.nonce,
                                form_content: formContent,
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
                                    console.log(response);
                                    
                                    
                                    
                                },
                                error: function (error) {
                                    console.log(error)
                                    
                                    
                                    
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
                                    action: 'king_addons_form_builder_mailchimp',
                                    nonce: KingAddonsFormBuilderData.nonce,
                                    form_data: formData,
                                    listId: submitButton.data('list-id')
                                    
                                },
                                beforeSend: function () {
                                    submitButton.prop('disabled', true);
                                },
                                success: function (response) {
                                    console.log(response);
                                    if (!response.success) {
                                        
                                        
                                        
                                    } else {
                                        
                                        
                                        
                                    }
                                    
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.log(errorThrown);
                                    
                                    
                                    
                                },
                                complete: function () {
                                    submitButton.prop('disabled', false);
                                }
                            });
                        }
                    });

                    function createFormContent() {
                        $scope.find('.king-addons-form-field, .king-addons-form-field-type-radio, .king-addons-form-field-type-checkbox, .king-addons-fb-step-input').each(function () {

                            var label = '';
                            if ($(this).prev('label')) {
                                label = $(this).prev('label').text().trim();
                            } else {
                                label = '';
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
                                                
                                                console.log(response);
                                                if (eventType == 'click') {
                                                    fileUrl[thisId][i] = response.data.url;
                                                }
                                                resolve(response);
                                            } else {
                                                console.error('Error:', response);
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
                                            console.log(error);
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