"use strict";
(function ($) {
    $(window).on('elementor:init', () => {

        elementor.hooks.addAction('panel/open_editor/widget/king-addons-form-builder', function (panel, model, view) {
            var $parent = panel.$el.find('#elementor-panel');
            var $element = panel.$el.find('.elementor-repeater-fields');

            var stepObserver = new MutationObserver(function (mutations) {
                mutations.forEach((mutation) => {
                    // console.log('steps mutation', mutation);
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                // Find the child element with the specific class within the node
                                const repeaterFields = node.querySelectorAll('.elementor-repeater-fields');
                                const childElement = node.querySelectorAll('.elementor-repeater-row-tools');
                                const childElement2 = node.querySelectorAll('.elementor-repeater-row-controls');

                                if (childElement2) {
                                    let selectElement = [];

                                    if (selectElement.length == 0) {
                                        childElement2.forEach(function (item) {
                                            selectElement.push(item.querySelector('select'));
                                        });
                                    }

                                    // If the child element exists and the select value is 'step', add the certain class to it
                                    if (childElement && selectElement.length > 0) {
                                        childElement.forEach(function (item, i) {
                                            if (selectElement[i] && selectElement[i].value == 'king-addons-fb-step') {
                                                item.classList.add('king-addons-fb-step-editor-bg');
                                            }
                                        });
                                    }
                                }
                            }
                        });
                    }
                });
            });

            stepObserver.observe($('#elementor-panel')[0], {
                childList: true,
                subtree: true,
            });

            changeStepBackground();

            elementor.channels.editor.on('section:activated', function (sectionName, editor) {
                // model.get('settings').attributes.form_fields.models.forEach(function(thisModel, i) {
                // 	if ( thisModel.attributes.field_type == 'step' ) {
                // 		$('.elementor-repeater-fields').eq(i).find('.elementor-repeater-row-tools').addClass('king-addons-fb-step-editor-bg');
                // 	}
                // });
                updateDynamicOptions(view);
            });

            function changeStepBackground() {

                $element.each(function () {
                    if ($(this).find('select').val() == 'king-addons-fb-step') {
                        $(this).find('.elementor-repeater-row-tools').addClass('king-addons-fb-step-editor-bg');
                    }
                });

                $('#elementor-panel').off('change', 'select').on('change', 'select', function () {
                    if ($(this).val() == 'king-addons-fb-step') {
                        $(this).closest('.elementor-repeater-row-controls').prev('.elementor-repeater-row-tools').addClass('king-addons-fb-step-editor-bg');
                    } else if ($(this).closest('.elementor-repeater-row-controls').prev('.elementor-repeater-row-tools').hasClass('king-addons-fb-step-editor-bg')) {
                        $(this).closest('.elementor-repeater-row-controls').prev('.elementor-repeater-row-tools').removeClass('king-addons-fb-step-editor-bg');
                    }
                });
            }


            view.listenTo(view.model.get('settings').get('form_fields'), 'add change remove', function (e) {
                updateDynamicOptions(view);
            });

            function updateDynamicOptions() {
                var formFieldsModel = view.model.get('settings').get('form_fields');

                var emailField = view.model.get('settings').get('email_field');
                var firstNameField = view.model.get('settings').get('first_name_field');
                var lastNameField = view.model.get('settings').get('last_name_field');
                var phoneField = view.model.get('settings').get('phone_field');
                var birthdayField = view.model.get('settings').get('birthday_field');
                var addressField = view.model.get('settings').get('address_field');
                var countryField = view.model.get('settings').get('country_field');
                var cityField = view.model.get('settings').get('city_field');
                var stateField = view.model.get('settings').get('state_field');
                var zipField = view.model.get('settings').get('zip_field');

                var emailSelectControl = panel.$el.find('.elementor-control-email_field').find('select[data-setting="email_field"]');
                var firstNameSelectControl = panel.$el.find('.elementor-control-first_name_field').find('select[data-setting="first_name_field"]');
                var lastNameSelectControl = panel.$el.find('.elementor-control-last_name_field').find('select[data-setting="last_name_field"]');
                var phoneSelectControl = panel.$el.find('.elementor-control-phone_field').find('select[data-setting="phone_field"]');
                var birthdaySelectControl = panel.$el.find('.elementor-control-birthday_field').find('select[data-setting="birthday_field"]');
                var addressSelectControl = panel.$el.find('.elementor-control-address_field').find('select[data-setting="address_field"]');
                var countrySelectControl = panel.$el.find('.elementor-control-country_field').find('select[data-setting="country_field"]');
                var citySelectControl = panel.$el.find('.elementor-control-city_field').find('select[data-setting="city_field"]');
                var stateSelectControl = panel.$el.find('.elementor-control-state_field').find('select[data-setting="state_field"]');
                var zipSelectControl = panel.$el.find('.elementor-control-zip_field').find('select[data-setting="zip_field"]');

                var selectControls = [emailSelectControl, firstNameSelectControl, lastNameSelectControl, phoneSelectControl, birthdaySelectControl, addressSelectControl, countrySelectControl, citySelectControl, stateSelectControl, zipSelectControl];
                var fieldValues = [emailField, firstNameField, lastNameField, phoneField, birthdayField, addressField, countryField, cityField, stateField, zipField];

                var options = {
                    'none': 'None'
                };

                var prevFieldId;

                formFieldsModel.each(function (field, index) {
                    var fieldLabel = field.get('field_label');
                    var fieldId = field.get('field_id');

                    if (prevFieldId == fieldId) {
                        $('#elementor-panel').find(':input[value=' + field.attributes._id + ']').closest('.elementor-repeater-fields').find(':input[data-setting="field_id"]').val(field.attributes._id);
                        $('#elementor-panel').find(':input[value=' + field.attributes._id + ']').closest('.elementor-repeater-fields').find('.king-addons-form-field-shortcode').val('id=["' + field.attributes._id + '"]');
                        field.attributes.field_id = field.attributes._id;
                    }

                    prevFieldId = fieldId;

                    if (!fieldId) {
                        $('#elementor-panel').find(':input[value=' + field.attributes._id + ']').closest('.elementor-repeater-fields').find(':input[data-setting="field_id"]').val(field.attributes._id);
                        $('#elementor-panel').find(':input[value=' + field.attributes._id + ']').closest('.elementor-repeater-fields').find('.king-addons-form-field-shortcode').val('id=["' + field.attributes._id + '"]');
                        field.attributes.field_id = field.attributes._id;
                    }

                    options[fieldId] = fieldLabel;
                });

                view.model.setSetting('email_field', _.extend(emailField, {options}));
                view.model.setSetting('first_name_field', _.extend(firstNameField, {options}));
                view.model.setSetting('last_name_field', _.extend(lastNameField, {options}));
                view.model.setSetting('phone_field', _.extend(phoneField, {options}));
                view.model.setSetting('birthday_field', _.extend(birthdayField, {options}));
                view.model.setSetting('address_field', _.extend(addressField, {options}));
                view.model.setSetting('country_field', _.extend(countryField, {options}));
                view.model.setSetting('city_field', _.extend(cityField, {options}));
                view.model.setSetting('state_field', _.extend(stateField, {options}));
                view.model.setSetting('zip_field', _.extend(zipField, {options}));

                _.each(selectControls, function (control) {
                    control.empty();
                });

                _.each(options, function (label, value) {
                    _.each(selectControls, function (control, i) {
                        const isSelected = fieldValues[i] === value ? ' selected' : '';
                        control.append('<option value="' + value + '"' + isSelected + '>' + label + '</option>');
                    });
                });
            };

            // Change the selector below to match the field_id field in your repeater
            const customIdFieldSelector = 'input[data-setting="field_id"]';

            // Change the selector below to match the shortcode field in your repeater
            const shortcodeFieldSelector = '.king-addons-form-field-shortcode';

            // Listen for changes in the field_id field
            $('#elementor-panel').on('input', customIdFieldSelector, function () {

                // Get the new field_id value
                const newCustomId = $(this).val();

                // Find the corresponding shortcode field in the same repeater item
                const $shortcodeField = $(this).closest('.elementor-repeater-fields').find(shortcodeFieldSelector);

                // Update the shortcode with the new field_id value
                let updatedShortcode;
                const currentShortcode = $shortcodeField.val();
                const match = currentShortcode.match(/\[id="[^"]*"\]/);

                if (match) {
                    updatedShortcode = currentShortcode.replace(match[0], `[id="${newCustomId}"]`);
                } else {
                    updatedShortcode = `[id="${newCustomId}"]`;
                }

                // Set the updated shortcode in the shortcode field
                $shortcodeField.val(updatedShortcode);
            });
        });

    });
}(jQuery));