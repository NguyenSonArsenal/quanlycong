$(function () {
  if (typeof tinymce === "undefined") return;

  tinymce.init({
    selector: "#content",
    height: 500,
    menubar: false,
    statusbar: true,
    branding: false,
    elementpath: true,
    resize: true,

    // WordPress-like clean look
    skin: "oxide",
    content_css: [
      "https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700&family=Inter:wght@400;600;700&display=swap",
      window.EDITOR_CONTENT_CSS + "?v=" + Date.now()
    ],

    // Force CDN, no tiny.cloud
    base_url: "https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2",
    suffix: ".min",

    plugins: "lists link image table code autolink paste wordcount hr",

    // WordPress-like toolbar layout
    toolbar:
      "blocks | bold italic underline strikethrough | " +
      "blockquote hr | bullist numlist | " +
      "alignleft aligncenter alignright | " +
      "link image table | removeformat code",

    block_formats: "Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4",

    // WordPress-like defaults
    body_class: "wp-editor",
    end_container_on_empty_block: true,
    remove_trailing_brs: true,

    automatic_uploads: true,

    images_upload_handler: function (blobInfo, progress) {
      return new Promise(function (resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", window.TINYMCE_UPLOAD_URL);
        xhr.setRequestHeader("X-CSRF-TOKEN", window.TINYMCE_CSRF);
        xhr.setRequestHeader("Accept", "application/json");

        xhr.upload.onprogress = function (e) {
          progress((e.loaded / e.total) * 100);
        };

        xhr.onload = function () {
          if (xhr.status < 200 || xhr.status >= 300) {
            reject("HTTP Error: " + xhr.status);
            return;
          }
          var json;
          try { json = JSON.parse(xhr.responseText); } catch (e) { reject("Invalid JSON"); return; }
          if (!json || typeof json.location !== "string") {
            reject(json?.message || "Invalid response");
            return;
          }
          resolve(json.location);
        };

        xhr.onerror = function () { reject("Upload failed"); };

        var formData = new FormData();
        formData.append("file", blobInfo.blob(), blobInfo.filename());
        xhr.send(formData);
      });
    },
  });
});

/* ── Validate content on submit ── */
$(function () {
  var $form = $("form.store-update-entity");
  var $err = $("#contentError");

  $form.on("submit", function (e) {
    tinymce.triggerSave();

    var html = $("#content").val() || "";
    var textOnly = $("<div>").html(html).text().trim();
    var hasImage = html.includes("<img");
    var isEmpty = textOnly.length === 0 && !hasImage;

    if (isEmpty) {
      e.preventDefault();
      $err.removeClass("d-none");
      tinymce.get("content")?.focus();
      return false;
    }

    $err.addClass("d-none");
  });
});
