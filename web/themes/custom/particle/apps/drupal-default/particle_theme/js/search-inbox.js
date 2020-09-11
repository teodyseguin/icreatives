(function searchInbox($) {
  const { currentQuery } = drupalSettings.path;

  if (currentQuery) {
    const { body_value } = currentQuery;

    if (body_value !== '') {
      $('#search-inbox-keywords').val(body_value);
    }
  }

  $('#search-inbox').click(function search() {
    const $keywordsField = $('#search-inbox-keywords');
    const keywords = $keywordsField.val();

    $('.views-exposed-form #edit-body-value').val(keywords);
    $('.views-exposed-form .input--submit').trigger('click');
  })
})(jQuery);
