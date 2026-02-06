"use strict";

(function ($) {
    const FLAG = "kingAddonsSubmissionsBound";

    /**
     * Bind admin submissions handlers once.
     *
     * @returns {void}
     */
    const bindOnce = () => {
        if (window[FLAG]) {
            return;
        }
        window[FLAG] = true;

        $(document).ready(function () {
            // Remove default "Add New" button on submissions list page.
            $(".page-title-action").remove();

            /* -------------------- LIST TABLE (read/unread toggle) -------------------- */
            $("body").on("click.kingAddonsSubmissions", ".column-read_status", function () {
                const $row = $(this).parent();
                const rowId = $row.attr("id") || "";
                const postId = rowId.replace("post-", "");
                const currentText = ($(this).text() || "").trim();
                const readStatus = currentText === "Read" ? "0" : "1";

                const nonce = window.KingAddonsSubmissions ? window.KingAddonsSubmissions.nonce : "";
                const ajaxurl = window.KingAddonsSubmissions ? window.KingAddonsSubmissions.ajaxurl : "";
                if (!ajaxurl || !nonce || !postId) {
                    return;
                }

                $.post(ajaxurl, {
                    action: "king_addons_submissions_update_read_status",
                    post_id: postId,
                    read_status: readStatus,
                    nonce,
                });
            });

            /* -------------------- SINGLE SUBMISSION SCREEN -------------------- */
            const $postForm = $("#post");
            if ($postForm.length) {
                // Add hidden input to store changes (only once).
                if (!$("#king_addons_submission_changes").length) {
                    $("<input>", {
                        type: "hidden",
                        id: "king_addons_submission_changes",
                        name: "king_addons_submission_changes",
                    }).appendTo($postForm);
                }

                let changes = {};

                // Initially lock inputs.
                $postForm
                    .find(".king-addons-submissions-wrap input, .king-addons-submissions-wrap textarea")
                    .each(function () {
                        const $el = $(this);
                        if ($el.is('[type="checkbox"],[type="radio"]')) {
                            $el.prop("disabled", true);
                        } else {
                            $el.prop("readonly", true);
                        }
                    });

                // Track edits (scoped to the submissions UI).
                $postForm.off("change.kingAddonsSubmissions");
                $postForm.on(
                    "change.kingAddonsSubmissions",
                    ".king-addons-submissions-wrap input, .king-addons-submissions-wrap textarea",
                    function () {
                        const $field = $(this);
                        let key = $field.attr("id") || "";
                        const value = [];

                        if ($field.is('[type="checkbox"],[type="radio"]')) {
                            value[0] = $field.attr("type");
                            value[1] = [];
                            value[2] = $field
                                .closest(".king-addons-submissions-wrap")
                                .find("label:first-of-type")
                                .text();
                            key = $field
                                .closest(".king-addons-submissions-wrap")
                                .find("label:first-of-type")
                                .attr("for");

                            $field
                                .closest(".king-addons-submissions-wrap")
                                .find("input")
                                .each(function () {
                                    const $input = $(this);
                                    value[1].push([
                                        $input.val(),
                                        $input.is(":checked"),
                                        $input.attr("name"),
                                        $input.attr("id"),
                                    ]);
                                });
                        } else {
                            value[0] = $field.attr("type");
                            value[1] = $field.val();
                            value[2] = $field.prev("label").text();
                        }

                        changes[key] = value;
                        $("#king_addons_submission_changes").val(JSON.stringify(changes));
                    }
                );

                // Toggle edit mode (scoped).
                $postForm.off("click.kingAddonsSubmissions");
                $postForm.on("click.kingAddonsSubmissions", ".king-addons-edit-submissions", function (e) {
                    e.preventDefault();
                    const $btn = $(this);
                    $("#king_addons_submission_changes").val("");

                    $postForm
                        .find(".king-addons-submissions-wrap input, .king-addons-submissions-wrap textarea")
                        .each(function () {
                            const $el = $(this);
                            const isLocked = $el.prop("readonly") || $el.prop("disabled");
                            if (isLocked) {
                                $el.prop("readonly", false).prop("disabled", false);
                                $btn.text("Cancel");
                                return;
                            }
                            if ($el.is('[type="checkbox"],[type="radio"]')) {
                                $el.prop("disabled", true);
                            } else {
                                $el.prop("readonly", true);
                            }
                            $btn.text("Edit");
                        });
                });
            }

            // Highlight unread rows on list screen.
            $(".king-addons-submission-unread")
                .closest("tr")
                .addClass("king-addons-submission-unread-column");

            /* --------------- Sidebar meta on single submission --------------- */
            if ($("#postbox-container-1").find("#submitdiv").length && window.KingAddonsSubmissions) {
                const s = window.KingAddonsSubmissions;
                $("#minor-publishing").remove();
                $("#submitdiv .postbox-header h2").text("Extra Info");

                const info = [
                    ["Form", `<a href="${s.form_page_editor}" target="_blank">${s.form_name} (${s.form_id})</a>`],
                    ["Page", `<a href="${s.form_page_url}" target="_blank">${s.form_page}</a>`],
                    ["Created at", s.post_created],
                    ["Updated at", s.post_updated],
                    ["User IP", s.agent_ip],
                    ["User Agent", s.form_agent],
                ];

                info.forEach(function (row) {
                    $("<div>", {
                        class: "misc-pub-section",
                        html: `${row[0]}: <span class="king-addons-submissions-meta">${row[1]}</span>`,
                    }).insertBefore("#major-publishing-actions");
                });

                // Reveal meta boxes.
                $("#postbox-container-1, #postbox-container-2").css("opacity", 1);
            }
        });
    };

    bindOnce();
})(jQuery);