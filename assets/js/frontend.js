jQuery(document).ready(function($) {
  $('#aiohm-send-btn').on('click', function() {
    const userInput = $('#aiohm-user-input').val().trim();
    if (!userInput) return;

    // Show user message
    $('#aiohm-chat-box').append(`<div class="msg user">${userInput}</div>`);
    $('#aiohm-user-input').val('');
    $('#aiohm-chat-box').append(`<div class="msg bot thinking">${aiohm_frontend.thinking}</div>`);
    const $thinking = $('#aiohm-chat-box .thinking').last();

    $.post(aiohm_frontend.ajax_url, {
      action: 'aiohm_query_kb',
      prompt: userInput,
      nonce: aiohm_frontend.nonce
    })
    .done(function(res) {
      $thinking.remove();
      if (res.success && res.data.response) {
        $('#aiohm-chat-box').append(`<div class="msg bot">${res.data.response}</div>`);
      } else {
        $('#aiohm-chat-box').append(`<div class="msg bot error">${res.data || aiohm_frontend.error}</div>`);
      }
    })
    .fail(function(xhr, status, error) {
      $thinking.remove();
      $('#aiohm-chat-box').append(`<div class="msg bot error">${aiohm_frontend.error}</div>`);
      console.error('AJAX Error:', status, error, xhr.responseText);
    });
  });
});
