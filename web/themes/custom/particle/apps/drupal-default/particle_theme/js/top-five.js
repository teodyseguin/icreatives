(($) => {
  $(document).ready(() => {
    $('.top-contents-icon .fa-facebook-square').click(() => {
      $('.fb-top-contents').addClass('flex').removeClass('hidden');
      $('.ig-top-contents').addClass('hidden').removeClass('flex');
    });

    $('.top-contents-icon .fa-instagram-square').click(() => {
      $('.fb-top-contents').addClass('hidden').removeClass('flex');
      $('.ig-top-contents').addClass('flex').removeClass('hidden');
    });
  });
})(jQuery);
