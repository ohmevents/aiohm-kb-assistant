jQuery(document).ready(function ($) {
  $('#aiohm-scan-kb').on('click', function (e) {
    e.preventDefault();

    const $btn = $(this);
    const $result = $('#aiohm-kb-scan-result');
    $btn.prop('disabled', true).text('Scanning...');

    $.post(aiohm_ajax.ajax_url, {
      action: 'aiohm_scan_kb',
      nonce: aiohm_ajax.nonce,
    }, function (res) {
      if (res.success) {
        $result.html(res.data.html);
      } else {
        $result.html('<div class="error">' + res.data + '</div>');
      }
      $btn.prop('disabled', false).text('Scan Website');
    });
  });
});
