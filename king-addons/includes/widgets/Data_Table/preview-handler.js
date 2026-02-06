"use strict";
(function ($) {
    const FLAG = "kingAddonsDataTablePreviewBound";
    let activeView = null;

    /**
     * Export currently active table as CSV.
     *
     * @returns {void}
     */
    const exportActiveTable = () => {
        if (!activeView) {
            return;
        }

        const rows = activeView.$el.find(".king-addons-data-table .king-addons-table-row");
        const data = [];

        rows.each((_, row) => {
            const cols = row.querySelectorAll(".king-addons-table-text");
            const rowData = Array.from(cols, (col) => col.innerText).join(",");
            data.push(rowData);
        });

        const csvContent = data.join("\n");
        const blob = new Blob([csvContent], { type: "text/csv" });
        const url = URL.createObjectURL(blob);

        const downloadLink = document.createElement("a");
        downloadLink.download = "placeholder.csv";
        downloadLink.href = url;
        downloadLink.style.display = "none";

        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        URL.revokeObjectURL(url);
    };

    /**
     * Bind editor listeners once.
     *
     * @returns {void}
     */
    const bindOnce = () => {
        if (window[FLAG]) {
            return;
        }
        window[FLAG] = true;

        $(window).on("elementor:init", () => {
            elementor.channels.editor.off("king-addons-data-table-export.kingAddonsDataTable");
            elementor.channels.editor.on(
                "king-addons-data-table-export.kingAddonsDataTable",
                exportActiveTable
            );

            elementor.hooks.addAction(
                "panel/open_editor/widget/king-addons-data-table",
                (panel, model, view) => {
                    activeView = view;
                }
            );
        });
    };

    bindOnce();
})(jQuery);