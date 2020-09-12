/**
 * hamburger-menu
 */

import $ from 'jquery';

// Module dependencies
import 'protons';

// Module template
import './_hamburger-menu.twig';
import './index.css';

export const name = 'hamburger-menu';

export const defaults = {
  dummyClass: 'js-hamburger-menu-exists',
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
export function enable($context, { hamburgerMenu = {} }) {
  // Find our component within the DOM
  const $hamburgerMenu = $('.hamburger-menu', $context);
  // Bail if component does not exist
  if (!$hamburgerMenu.length) {
    return;
  }
  // Merge defaults with incoming settings
  const settings = {
    ...defaults,
    ...hamburgerMenu,
  };
  // An example of what could be done with this component
  $hamburgerMenu.addClass(settings.dummyClass);
}

export default enable;

$(document).ready(function main() {
  $('#nav-icon1').click(function toggleHamburgerMenu() {
    // $(this).toggleClass('open');
    $('#page-left-sidebar')
      .addClass('fixed z-50 w-full')
      .removeClass('hidden w-0');
  });

  $('.fa-times').click(function closeAside() {
    $('#page-left-sidebar')
      .removeClass('fixed z-50 w-full')
      .addClass('hidden w-0');
  });
});
