/**
 * Woo Checkout Form widget behavior.
 *
 * WooCommerce address-i18n.js overwrites labels, required flags, placeholders
 * and field order from country locale after the form is rendered. Re-apply
 * the widget's field-customization settings after those updates.
 */
(function ($) {
  "use strict";

  const safeParseJson = (value) => {
    if (!value) return [];
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  };

  const setRequired = ($row, isRequired) => {
    const $label = $row.find("label").first();
    if (isRequired) {
      $row.addClass("validate-required");
      $label.find(".optional").remove();
      if ($label.find('.required[aria-hidden="true"]').length === 0) {
        $label.append('&nbsp;<span class="required" aria-hidden="true">*</span>');
      }
      $row.find(":input").attr("aria-required", "true");
    } else {
      $row.removeClass("validate-required woocommerce-invalid woocommerce-invalid-required-field");
      $label.find(".required").remove();
      if ($label.find(".optional").length === 0) {
        const optionalText =
          (typeof wc_address_i18n_params !== "undefined" && wc_address_i18n_params.i18n_optional_text)
            ? wc_address_i18n_params.i18n_optional_text
            : "optional";
        $label.append("&nbsp;<span class=\"optional\">(" + optionalText + ")</span>");
      }
      $row.find(":input").attr("aria-required", "false");
    }
  };

  const setLabel = ($row, text) => {
    const $label = $row.find("label").first();
    if (!$label.length || !text) {
      return;
    }
    const $marks = $label.find(".required, .optional").detach();
    $label.contents().filter(function () {
      return this.nodeType === 3;
    }).remove();
    $label.prepend(document.createTextNode(text));
    $label.append($marks);
  };

  const sortFieldRows = ($wrapper) => {
    const $rows = $wrapper.children(".form-row");
    if ($rows.length < 2) {
      return;
    }
    $rows.sort(function (a, b) {
      const pa = parseInt($(a).data("priority"), 10) || 0;
      const pb = parseInt($(b).data("priority"), 10) || 0;
      return pa - pb;
    }).appendTo($wrapper);
  };

  const applyConfig = (root) => {
    if (!root || !root.dataset) {
      return;
    }
    const items = safeParseJson(root.dataset.kaFieldsConfig);
    if (!items.length) {
      return;
    }

    const $root = $(root);
    const wrappers = [];

    items.forEach((item) => {
      const key = item && item.field_key ? String(item.field_key) : "";
      if (!key) {
        return;
      }
      const $row = $root.find("#" + key + "_field");
      if (!$row.length) {
        return;
      }
      if (item.hide === "yes") {
        $row.hide();
        return;
      }
      if (item.label) {
        setLabel($row, item.label);
      }
      if (item.placeholder) {
        $row.find(":input").attr("placeholder", item.placeholder);
      }
      if (typeof item.required !== "undefined" && item.required !== "") {
        setRequired($row, item.required === "yes");
      }
      if (typeof item.priority !== "undefined" && item.priority !== "") {
        const priority = parseInt(item.priority, 10);
        if (!isNaN(priority)) {
          $row.attr("data-priority", priority).data("priority", priority);
          const parent = $row.parent().get(0);
          if (parent && wrappers.indexOf(parent) === -1) {
            wrappers.push(parent);
          }
        }
      }
    });

    wrappers.forEach((el) => {
      sortFieldRows($(el));
    });
  };

  const applyAll = () => {
    document.querySelectorAll(".elementor-widget-woo_checkout_form").forEach(applyConfig);
  };

  $(applyAll);

  $(document.body).on("country_to_state_changing updated_checkout", function () {
    window.setTimeout(applyAll, 0);
  });

  $(window).on("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !elementorFrontend.hooks) {
      return;
    }
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_checkout_form.default",
      function ($scope) {
        if ($scope && $scope[0]) {
          applyConfig($scope[0]);
        }
      }
    );
  });
})(jQuery);
