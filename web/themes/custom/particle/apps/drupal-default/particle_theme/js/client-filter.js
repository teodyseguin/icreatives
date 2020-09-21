(function clientFilterModule($) {
  const { currentQuery, currentPath } = drupalSettings.path;

  if (currentQuery) {
    const { field_client_target_id, client } = currentQuery;

    if (field_client_target_id !== '') {
      $('.filter .selectbox select').val(field_client_target_id);
    } else if (client !== '') {
      $('.filter .selectbox select').val(client);
    } else {
      $('.filter .selectbox select').val('all');
    }
  }

  // Event for the client filter button.
  $('.filter button').click(function filterClient() {
    const selectedValue = $('.filter .selectbox select').val();

    if (selectedValue === 'all') {
      window.location = `/${currentPath}`;

      return;
    }

    if (currentPath && currentPath === 'dashboard') {
      window.location = `/${currentPath}?client=${selectedValue}`;
    } else {
      $('.views-exposed-form #edit-field-client-target-id').val(selectedValue);
      $('.views-exposed-form .input--submit').trigger('click');
    }
  });
})(jQuery);
