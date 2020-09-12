(function fbLoginButton($) {
  $('#edit-fb-simple-connect').click(function submitFbSimpleConnect() {
    window.location = '/user/simple-fb-connect';
    return false;
  });
})(jQuery);
