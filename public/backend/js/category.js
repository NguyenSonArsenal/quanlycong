$(document).on('click', '.toggle-children, .parent-title', function () {
  var parentItem = $(this).closest('.parent-item');
  var childList = parentItem.find('.child-list').first();

  childList.toggleClass('d-none');
  parentItem.toggleClass('is-open');

  // đổi icon ▸ / ▾ (optional)
  var icon = parentItem.find('.toggle-icon').first();
  icon.text(parentItem.hasClass('is-open') ? '▾' : '▸');
});

$(function () {
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  var SAVE_URL = window.CATEGORY_REORDER_URL; // ✅ lấy từ blade inject
  if (!SAVE_URL) {
    console.error('Missing CATEGORY_REORDER_URL');
    return;
  }

  var saveTimer = null;
  var saving = false;
  var pending = false;

  function collectPayload() {
    var parent = $('#parent-list > .parent-item').map(function () {
      return Number($(this).data('id'));
    }).get();

    var children = {};
    $('.child-list').each(function () {
      var parentId = $(this).data('parent-id');
      children[parentId] = $(this).find('.child-row').map(function () {
        return Number($(this).data('id'));
      }).get();
    });

    return {parent: parent, children: children};
  }

  function setStatus(text, show) {
    var $st = $('#save-status');
    if (!$st.length) return;
    if (show === false) return $st.hide();
    $st.text(text).show();
  }

  function requestSave() {
    // debounce 400ms sau lần kéo cuối
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doSave, 400);
  }

  function doSave() {
    if (saving) {
      // đang save mà lại phát sinh thay đổi => đánh dấu pending
      pending = true;
      return;
    }

    saving = true;
    pending = false;

    var payload = collectPayload();
    setStatus('Đang lưu...', true);

    $.ajax({
      url: SAVE_URL,
      method: 'POST',
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      data: JSON.stringify(payload),
      success: function (res) {
        setStatus('Đã lưu ✓', true);
        setTimeout(function () {
          setStatus('', false);
        }, 800);
      },
      error: function (xhr) {
        console.log(xhr.status, xhr.responseText);
        setStatus('Lưu lỗi ✗', true);
        // fallback: vẫn cho người dùng biết để bấm nút lưu lại (nếu anh giữ nút)
        alert('Lưu thứ tự thất bại (' + xhr.status + '). Vui lòng thử lại.');
      },
      complete: function () {
        saving = false;
        // nếu trong lúc save user kéo tiếp -> save lần nữa
        if (pending) requestSave();
      }
    });
  }

  // ===== Sortable init (DIV list) =====
  new Sortable(document.getElementById('parent-list'), {
    draggable: '.parent-item',
    handle: '.handle-parent',
    animation: 150,
    onEnd: function () {
      requestSave();
    }
  });

  document.querySelectorAll('.child-list').forEach(function (el) {
    new Sortable(el, {
      draggable: '.child-row',
      handle: '.handle-child',
      animation: 150,
      onEnd: function () {
        requestSave();
      }
    });
  });

  // (Optional) vẫn giữ nút lưu làm fallback
  $('#btn-save-sort').on('click', function () {
    doSave();
  });
});
