/*Bootstrap grid plugin - DEFINE THIS FIRST */
/*Bootstrap grid plugin */
/*Bootstrap grid plugin */
CKEDITOR.plugins.add("bootstrapgrid", {
    init: function (editor) {
        // Add CSS to editor's document
        editor.on('instanceReady', function() {
            // Add Bootstrap grid CSS if not already present
            if (editor.document) {
                var style = editor.document.createElement('style');
                style.setAttribute('type', 'text/css');
                style.setText(`
                    .bootstrap-grid-helper {
                        background: #f8f9fa !important;
                        padding: 15px !important;
                        border: 2px dashed #007bff !important;
                        text-align: center !important;
                        margin: 5px 0 !important;
                        min-height: 50px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-right: -15px;
                        margin-left: -15px;
                    }
                    .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, 
                    .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12 {
                        position: relative;
                        width: 100%;
                        padding-right: 15px;
                        padding-left: 15px;
                    }
                    @media (min-width: 768px) {
                        .col-md-1 { flex: 0 0 8.333333%; max-width: 8.333333%; }
                        .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
                        .col-md-3 { flex: 0 0 25%; max-width: 25%; }
                        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
                        .col-md-5 { flex: 0 0 41.666667%; max-width: 41.666667%; }
                        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
                        .col-md-7 { flex: 0 0 58.333333%; max-width: 58.333333%; }
                        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
                        .col-md-9 { flex: 0 0 75%; max-width: 75%; }
                        .col-md-10 { flex: 0 0 83.333333%; max-width: 83.333333%; }
                        .col-md-11 { flex: 0 0 91.666667%; max-width: 91.666667%; }
                        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
                    }
                `);
                editor.document.getHead().append(style);
            }
        });

        editor.ui.addRichCombo("BootstrapGrid", {
            label: "Bootstrap Grid",
            title: "Insert Bootstrap Grid",
            toolbar: "insert",
            panel: {
                css: [CKEDITOR.skin.getPath("editor")].concat(editor.config.contentsCss),
                multiSelect: false,
                attributes: { "aria-label": "Bootstrap Grid options" },
            },

            init: function () {
                this.add("2cols", "2 Columns (50/50)", "2 equal columns");
                this.add("3cols", "3 Columns (33/33/33)", "3 equal columns");
                this.add("4cols", "4 Columns (25/25/25/25)", "4 equal columns");
                this.add("main-sidebar", "Main + Sidebar (8/4)", "Main content with sidebar");
                this.add("sidebar-main", "Sidebar + Main (4/8)", "Sidebar with main content");
                this.add("main-sidebar-9-3", "Main + Sidebar (9/3)", "Main content with small sidebar");
            },

            onClick: function (value) {
                var html = "";
                
                switch (value) {
                    case "2cols":
                        html = '<div class="row"><div class="col-md-6"><div class="bootstrap-grid-helper">Column 1 (6)</div></div><div class="col-md-6"><div class="bootstrap-grid-helper">Column 2 (6)</div></div></div><p>&nbsp;</p>';
                        break;
                    case "3cols":
                        html = '<div class="row"><div class="col-md-4"><div class="bootstrap-grid-helper">Column 1 (4)</div></div><div class="col-md-4"><div class="bootstrap-grid-helper">Column 2 (4)</div></div><div class="col-md-4"><div class="bootstrap-grid-helper">Column 3 (4)</div></div></div><p>&nbsp;</p>';
                        break;
                    case "4cols":
                        html = '<div class="row"><div class="col-md-3"><div class="bootstrap-grid-helper">Column 1 (3)</div></div><div class="col-md-3"><div class="bootstrap-grid-helper">Column 2 (3)</div></div><div class="col-md-3"><div class="bootstrap-grid-helper">Column 3 (3)</div></div><div class="col-md-3"><div class="bootstrap-grid-helper">Column 4 (3)</div></div></div><p>&nbsp;</p>';
                        break;
                    case "main-sidebar":
                        html = '<div class="row"><div class="col-md-8"><div class="bootstrap-grid-helper">Main Content (8)</div></div><div class="col-md-4"><div class="bootstrap-grid-helper">Sidebar (4)</div></div></div><p>&nbsp;</p>';
                        break;
                    case "sidebar-main":
                        html = '<div class="row"><div class="col-md-4"><div class="bootstrap-grid-helper">Sidebar (4)</div></div><div class="col-md-8"><div class="bootstrap-grid-helper">Main Content (8)</div></div></div><p>&nbsp;</p>';
                        break;
                    case "main-sidebar-9-3":
                        html = '<div class="row"><div class="col-md-9"><div class="bootstrap-grid-helper">Main Content (9)</div></div><div class="col-md-3"><div class="bootstrap-grid-helper">Sidebar (3)</div></div></div><p>&nbsp;</p>';
                        break;
                }
                editor.insertHtml(html);
            },
        });
    },
});
/*Bootstrap grid plugin */
/*Bootstrap grid plugin - END */
document.querySelectorAll(".ckeditor4").forEach(function (el) {
    CKEDITOR.replace(el, {
        removePlugins: "exportpdf",
        allowedContent: true,
        extraAllowedContent: "*(*);*{*}",
        extraPlugins: "uploadimage, sourcearea, justify, div, bootstrapgrid",
        filebrowserUploadUrl: window.CKEDITOR_ROUTES.upload,
        filebrowserImageUploadUrl: window.CKEDITOR_ROUTES.upload,
        filebrowserUploadMethod: "form",
        imageUploadUrl: window.CKEDITOR_ROUTES.upload,
        baseHref: "{{ url('/') }}/",
        contentsCss: [
            CKEDITOR.basePath + 'contents.css',
            'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'
        ],
    });
});

CKEDITOR.on("dialogDefinition", function (ev) {
    if (ev.data.name === "image") {
        var dialogDefinition = ev.data.definition;
        var infoTab = dialogDefinition.getContents("info");
        infoTab.elements.unshift({
            type: "html",
            id: "imageGallery",
            html: `
                <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                    <h4 style="margin: 0 0 10px 0;">Image Gallery</h4>
                    <div id="simple-image-gallery" style="min-height: 100px; border: 1px dashed #ccc; padding: 10px; text-align: center;">
                        Loading images...
                    </div>
                </div>
            `,
        });

        var originalOnShow = dialogDefinition.onShow;
        dialogDefinition.onShow = function () {
            if (originalOnShow) {
                originalOnShow.call(this);
            }
            setTimeout(loadSimpleGallery, 100);
        };
    }
});

function loadSimpleGallery() {
    var container = document.getElementById("simple-image-gallery");
    if (!container) return;

    container.innerHTML = "Loading images...";

    fetch(window.CKEDITOR_ROUTES.imagelist)
        .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then((images) => {
            if (images && images.length > 0) {
                let html =
                    '<div style="display: flex; flex-wrap: wrap; gap: 5px; justify-content: center;">';
                images.slice(0, 8).forEach((image) => {
                    html += `
                        <img src="${image.url}" 
                             style="width: 60px; height: 45px; object-fit: cover; cursor: pointer; border: 2px solid transparent; border-radius: 3px;" 
                             onclick="setImageUrl('${image.url}')"
                             onmouseover="this.style.borderColor='#007bff'"
                             onmouseout="this.style.borderColor='transparent'"
                             title="${image.name}">
                    `;
                });
                html += "</div>";
                container.innerHTML = html;
            } else {
                container.innerHTML =
                    '<div style="color: #666; padding: 20px;">No images found. Upload images using the Upload tab.</div>';
            }
        })
        .catch((error) => {
            console.error("Error loading images:", error);
            container.innerHTML =
                '<div style="color: red; padding: 20px;">Error loading images. Please try again.</div>';
        });
}

function setImageUrl(url) {
    var dialog = CKEDITOR.dialog.getCurrent();
    if (dialog) {
        dialog.setValueOf("info", "txtUrl", url);
        try {
            var preview = dialog.getContentElement("info", "txtPreview");
            if (preview) {
                var previewElement = preview.getElement();
                var imgElement = previewElement.findOne("img");
                if (imgElement) {
                    imgElement.setAttribute("src", url);
                }
            }
        } catch (e) {}
    }
}
