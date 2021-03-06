/* global drupalSettings */
/* eslint no-undef: "error" */

/**
 * date-range
 */

import $ from 'jquery';
import datepicker from 'js-datepicker';
import '../../../../../node_modules/js-datepicker/dist/datepicker.min.css';

// Module dependencies
import 'protons';

// Module template
import './_date-range.twig';
import './index.css';

export const name = 'date-range';

export const defaults = {
  dummyClass: 'js-date-range-exists',
};

/**
 * Components may need to run clean-up tasks if they are removed from DOM.
 *
 * @param {jQuery} $context - A piece of DOM
 * @param {Object} settings - Pertinent settings
 */
// eslint-disable-next-line no-unused-vars
export function disable($context, settings) {}

/**
 * Each component has a chance to run when its enable function is called. It is
 * given a piece of DOM ($context) and a settings object. We destructure our
 * component key off the settings object and provide an empty object fallback.
 * Incoming settings override default settings via Object.assign().
 *
 * @param {jQuery} $context - A piece of DOM
 * @param {Object} settings - Settings object
 */
export function enable($context, { dateRange = {} }) {
  // Find our component within the DOM
  const $dateRange = $('.date-range', $context);
  // Bail if component does not exist
  if (!$dateRange.length) {
    return;
  }
  // Merge defaults with incoming settings
  const settings = {
    ...defaults,
    ...dateRange,
  };
  // An example of what could be done with this component
  $dateRange.addClass(settings.dummyClass);
}

export default enable;

/**
 * Format the given Date object into this format m/d/Y.
 * @param {Date} date
 */
const formatDate = (date) => {
  return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
};

(function dateRange() {
  const { currentQuery } = drupalSettings.path;
  const { origin, pathname } = window.location;
  let clientQuery;

  if (currentQuery) {
    const { from, to, client } = currentQuery;
    let since;
    let until;

    if (!client) {
      $('.date-from').attr('disabled', true);
      $('.date-to').attr('disabled', true);
      return;
    }

    clientQuery = client;

    /* eslint-disable no-param-reassign */
    datepicker('.date-from', {
      formatter: (input, date) => {
        const value = date;
        input.value = value;
      },
    });

    /* eslint-disable no-param-reassign */
    datepicker('.date-to', {
      formatter: (input, date) => {
        const value = date;
        input.value = value;
      },
    });

    if (from) {
      since = formatDate(new Date(from * 1000));
    }

    if (to) {
      until = formatDate(new Date(to * 1000));
    }

    $('.date-from').val(since);
    $('.date-to').val(until);
  }

  $('.submit-daterange button').click(() => {
    if (!clientQuery) {
      return;
    }

    if ($('.date-from').val() === '' && $('.date-to').val() === '') {
      return;
    }

    let fromTimestamp = new Date($('.date-from').val());
    let toTimestamp = new Date($('.date-to').val());

    fromTimestamp = Math.floor(fromTimestamp.getTime() / 1000);
    toTimestamp = Math.floor(toTimestamp.getTime() / 1000);

    window.location = `${origin}${pathname}?client=${clientQuery}&from=${fromTimestamp}&to=${toTimestamp}`;
  });
})();
