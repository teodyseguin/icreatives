(function clientFilterModule($) {
  const selectedClient = localStorage.getItem('selectedClient');

  // If selectedClient is available from local storage,
  // then we set that value as a default for the client filter.
  if (selectedClient) {
    $('.filter .selectbox select').val(selectedClient);
  }

  // Event for the client filter button.
  $('.filter button').click(function filterClient() {
    const selectedValue = $('.filter .selectbox select').val();

    $('.views-exposed-form .input--text').val(selectedValue);
    $('.views-exposed-form .input--submit').trigger('click');
    localStorage.setItem('selectedClient', selectedValue);
  });
})(jQuery);
