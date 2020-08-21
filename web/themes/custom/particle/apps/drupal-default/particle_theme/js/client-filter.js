(function clientFilterModule($) {
  if (drupalSettings.path.hasOwnProperty('currentQuery')) {
    const selectedClient = localStorage.getItem('selectedClient');

    // If selectedClient is available from local storage,
    // then we set that value as a default for the client filter.
    if (selectedClient) {
      $('.filter .selectbox select').val(selectedClient);
    }
  }

  // Event for the client filter button.
  $('.filter button').click(function filterClient() {
    const selectedValue = $('.filter .selectbox select').val();

    if (selectedValue === 'all') {
      window.location = `/${drupalSettings.path.currentPath}`;

      return;
    }

    $('.views-exposed-form .input--text').val(selectedValue);
    $('.views-exposed-form .input--submit').trigger('click');
    localStorage.setItem('selectedClient', selectedValue);
  });
})(jQuery);
