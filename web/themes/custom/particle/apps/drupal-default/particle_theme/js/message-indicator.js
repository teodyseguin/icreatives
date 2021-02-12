(function messageIndicator($) {
  const getMessageCount = () => {
    const { messages } = drupalSettings.ic;
    let messageCount = 0;

    if (!messages) {
      return;
    }

    if (Object.keys(messages).length) {
      Object.keys(messages).forEach(function iterate(value) {
        if (messages[value]) {
          messageCount += messages[value];
        }
      });
    }

    return messageCount;
  };

  const indicateOnSidebar = () => {
    const $blockNavigation = $('#block-mainnavigation');
    const messageCount = getMessageCount();

    // If block main navigation is not present on the page,
    // then we don't need to do anything below.
    if (!$blockNavigation.length) {
      return;
    }

    // Same goes when the message count is zero.
    if (!messageCount) {
      return;
    }

    $blockNavigation.find('li a').each(function findMenuItem() {
      const $self = $(this);
      const path = $self.attr('data-drupal-link-system-path');

      if (path === 'inbox') {
        $('.navbar-inbox-icon .badge')
          .removeClass('hidden')
          .addClass('inline-block')
          .html(`${messageCount}`);
      }
    });
  };

  const indicateOnPage = () => {
    const { currentPath } = drupalSettings.path;
    const { messages } = drupalSettings.ic;

    if (!currentPath || currentPath !== 'inbox') {
      return;
    }

    if (!messages) {
      return;
    }

    if (Object.keys(messages).length) {
      Object.keys(messages).forEach(function iterate(value) {
        if ($(`[data-id="${value}"]`).length && messages[value]) {
          $(`[data-id="${value}"]`).addClass('font-bold');
        }
      });
    }
  };

  indicateOnSidebar();
  indicateOnPage();

})(jQuery);
