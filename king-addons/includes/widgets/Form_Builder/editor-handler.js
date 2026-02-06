"use strict";
(function ($) {
    const FLAG = "kingAddonsFormBuilderEditorBound";

    /**
     * Bind Elementor editor handlers once.
     *
     * @returns {void}
     */
    const bindOnce = () => {
        if (window[FLAG]) {
            return;
        }
        window[FLAG] = true;

        $(window).on("elementor:init", () => {

            elementor.hooks.addAction(
                "panel/open_editor/widget/king-addons-form-builder",
                function (panel, model, view) {
                    if (view && view._kingAddonsFormBuilderBound) {
                        return;
                    }
                    if (view) {
                        view._kingAddonsFormBuilderBound = true;
                    }

                    const $panelRoot = $("#elementor-panel");
                    const $elements = panel.$el.find(".elementor-repeater-fields");

                    if (!$panelRoot.length) {
                        return;
                    }

                    if (view && view._kingAddonsStepObserver) {
                        view._kingAddonsStepObserver.disconnect();
                    }

                    const stepObserver = new MutationObserver(function (mutations) {
                        mutations.forEach((mutation) => {
                            if (mutation.type !== "childList") {
                                return;
                            }
                            mutation.addedNodes.forEach((node) => {
                                if (!(node instanceof Element)) {
                                    return;
                                }
                                const rowTools = node.querySelectorAll(".elementor-repeater-row-tools");
                                const rowControls = node.querySelectorAll(".elementor-repeater-row-controls");
                                if (!rowControls.length || !rowTools.length) {
                                    return;
                                }

                                const selects = [];
                                rowControls.forEach(function (item) {
                                    selects.push(item.querySelector("select"));
                                });

                                rowTools.forEach(function (item, i) {
                                    if (selects[i] && selects[i].value === "king-addons-fb-step") {
                                        item.classList.add("king-addons-fb-step-editor-bg");
                                    }
                                });
                            });
                        });
                    });

                    if (view) {
                        view._kingAddonsStepObserver = stepObserver;
                    }

                    stepObserver.observe($panelRoot[0], {
                        childList: true,
                        subtree: true,
                    });

                    changeStepBackground();

                    elementor.channels.editor.off("section:activated.kingAddonsFormBuilder");
                    elementor.channels.editor.on("section:activated.kingAddonsFormBuilder", function () {
                        updateDynamicOptions(view);
                    });

                    const formFieldsCollection = view.model.get("settings").get("form_fields");
                    view.stopListening(formFieldsCollection, "add change remove");
                    view.listenTo(formFieldsCollection, "add change remove", function () {
                        updateDynamicOptions(view);
                    });

                    function changeStepBackground() {
                        $elements.each(function () {
                            if ($(this).find("select").val() === "king-addons-fb-step") {
                                $(this)
                                    .find(".elementor-repeater-row-tools")
                                    .addClass("king-addons-fb-step-editor-bg");
                            }
                        });

                        $panelRoot.off("change.kingAddonsFormBuilder", "select");
                        $panelRoot.on("change.kingAddonsFormBuilder", "select", function () {
                            const $select = $(this);
                            const $tools = $select
                                .closest(".elementor-repeater-row-controls")
                                .prev(".elementor-repeater-row-tools");

                            if ($select.val() === "king-addons-fb-step") {
                                $tools.addClass("king-addons-fb-step-editor-bg");
                                return;
                            }
                            if ($tools.hasClass("king-addons-fb-step-editor-bg")) {
                                $tools.removeClass("king-addons-fb-step-editor-bg");
                            }
                        });
                    }

                    function updateDynamicOptions() {
                        const formFieldsModel = view.model.get("settings").get("form_fields");

                        const emailField = view.model.get("settings").get("email_field");
                        const firstNameField = view.model.get("settings").get("first_name_field");
                        const lastNameField = view.model.get("settings").get("last_name_field");
                        const phoneField = view.model.get("settings").get("phone_field");
                        const birthdayField = view.model.get("settings").get("birthday_field");
                        const addressField = view.model.get("settings").get("address_field");
                        const countryField = view.model.get("settings").get("country_field");
                        const cityField = view.model.get("settings").get("city_field");
                        const stateField = view.model.get("settings").get("state_field");
                        const zipField = view.model.get("settings").get("zip_field");

                        const emailSelectControl = panel.$el
                            .find(".elementor-control-email_field")
                            .find('select[data-setting="email_field"]');
                        const firstNameSelectControl = panel.$el
                            .find(".elementor-control-first_name_field")
                            .find('select[data-setting="first_name_field"]');
                        const lastNameSelectControl = panel.$el
                            .find(".elementor-control-last_name_field")
                            .find('select[data-setting="last_name_field"]');
                        const phoneSelectControl = panel.$el
                            .find(".elementor-control-phone_field")
                            .find('select[data-setting="phone_field"]');
                        const birthdaySelectControl = panel.$el
                            .find(".elementor-control-birthday_field")
                            .find('select[data-setting="birthday_field"]');
                        const addressSelectControl = panel.$el
                            .find(".elementor-control-address_field")
                            .find('select[data-setting="address_field"]');
                        const countrySelectControl = panel.$el
                            .find(".elementor-control-country_field")
                            .find('select[data-setting="country_field"]');
                        const citySelectControl = panel.$el
                            .find(".elementor-control-city_field")
                            .find('select[data-setting="city_field"]');
                        const stateSelectControl = panel.$el
                            .find(".elementor-control-state_field")
                            .find('select[data-setting="state_field"]');
                        const zipSelectControl = panel.$el
                            .find(".elementor-control-zip_field")
                            .find('select[data-setting="zip_field"]');

                        const selectControls = [
                            emailSelectControl,
                            firstNameSelectControl,
                            lastNameSelectControl,
                            phoneSelectControl,
                            birthdaySelectControl,
                            addressSelectControl,
                            countrySelectControl,
                            citySelectControl,
                            stateSelectControl,
                            zipSelectControl,
                        ];
                        const fieldValues = [
                            emailField,
                            firstNameField,
                            lastNameField,
                            phoneField,
                            birthdayField,
                            addressField,
                            countryField,
                            cityField,
                            stateField,
                            zipField,
                        ];

                        const options = {none: "None"};
                        let prevFieldId;

                        formFieldsModel.each(function (field) {
                            const fieldLabel = field.get("field_label");
                            const fieldId = field.get("field_id");

                            if (prevFieldId === fieldId) {
                                $panelRoot
                                    .find(":input[value=" + field.attributes._id + "]")
                                    .closest(".elementor-repeater-fields")
                                    .find(':input[data-setting="field_id"]')
                                    .val(field.attributes._id);
                                $panelRoot
                                    .find(":input[value=" + field.attributes._id + "]")
                                    .closest(".elementor-repeater-fields")
                                    .find(".king-addons-form-field-shortcode")
                                    .val('id=["' + field.attributes._id + '"]');
                                field.attributes.field_id = field.attributes._id;
                            }

                            prevFieldId = fieldId;

                            if (!fieldId) {
                                $panelRoot
                                    .find(":input[value=" + field.attributes._id + "]")
                                    .closest(".elementor-repeater-fields")
                                    .find(':input[data-setting="field_id"]')
                                    .val(field.attributes._id);
                                $panelRoot
                                    .find(":input[value=" + field.attributes._id + "]")
                                    .closest(".elementor-repeater-fields")
                                    .find(".king-addons-form-field-shortcode")
                                    .val('id=["' + field.attributes._id + '"]');
                                field.attributes.field_id = field.attributes._id;
                            }

                            options[fieldId] = fieldLabel;
                        });

                        view.model.setSetting("email_field", _.extend(emailField, {options}));
                        view.model.setSetting("first_name_field", _.extend(firstNameField, {options}));
                        view.model.setSetting("last_name_field", _.extend(lastNameField, {options}));
                        view.model.setSetting("phone_field", _.extend(phoneField, {options}));
                        view.model.setSetting("birthday_field", _.extend(birthdayField, {options}));
                        view.model.setSetting("address_field", _.extend(addressField, {options}));
                        view.model.setSetting("country_field", _.extend(countryField, {options}));
                        view.model.setSetting("city_field", _.extend(cityField, {options}));
                        view.model.setSetting("state_field", _.extend(stateField, {options}));
                        view.model.setSetting("zip_field", _.extend(zipField, {options}));

                        _.each(selectControls, function (control) {
                            control.empty();
                        });

                        _.each(options, function (label, value) {
                            _.each(selectControls, function (control, i) {
                                const isSelected = fieldValues[i] === value ? " selected" : "";
                                control.append(
                                    '<option value="' + value + '"' + isSelected + ">" + label + "</option>"
                                );
                            });
                        });
                    }

                    // Change the selector below to match the field_id field in your repeater.
                    const customIdFieldSelector = 'input[data-setting="field_id"]';

                    // Change the selector below to match the shortcode field in your repeater.
                    const shortcodeFieldSelector = ".king-addons-form-field-shortcode";

                    // Listen for changes in the field_id field (namespaced).
                    $panelRoot.off("input.kingAddonsFormBuilder", customIdFieldSelector);
                    $panelRoot.on("input.kingAddonsFormBuilder", customIdFieldSelector, function () {
                        const newCustomId = $(this).val();

                        const $shortcodeField = $(this)
                            .closest(".elementor-repeater-fields")
                            .find(shortcodeFieldSelector);

                        let updatedShortcode;
                        const currentShortcode = $shortcodeField.val();
                        const match = currentShortcode.match(/\[id="[^"]*"\]/);

                        if (match) {
                            updatedShortcode = currentShortcode.replace(match[0], `[id="${newCustomId}"]`);
                        } else {
                            updatedShortcode = `[id="${newCustomId}"]`;
                        }

                        $shortcodeField.val(updatedShortcode);
                    });
                });
            ;
        });
    };


    bindOnce();
}(jQuery));