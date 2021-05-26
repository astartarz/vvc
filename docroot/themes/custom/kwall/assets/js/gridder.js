(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Common tweaks for the theme.
   *
   * @type {{attach: Drupal.behaviors.gridderInit.attach}}
   */
  Drupal.behaviors.gridderInit = {
    attach: function (context, settings) {
      $('.paragraph--type--gridder', context).once('gridderInit').each(function () {
        var $gridder = $(this).find('.gridder');
        $gridder.gridderExpander({
          scroll: true,
          scrollOffset: 30,
          scrollTo: "panel", // panel or listitem
          animationSpeed: 400,
          animationEasing: "easeInOutExpo",
          showNav: true, // Show Navigation
          nextText: "", // Next button text
          prevText: "", // Previous button text
          closeText: "", // Close button text
          onStart: function () {
            // Gridder Inititialized.
            console.log('On Gridder Initialized...');
          },
          onContent: function () {
            // Gridder Content Loaded.
            console.log('On Gridder Expand...');
          },
          onClosed: function () {
            // Gridder Closed.
            console.log('On Gridder Closed...');
          }
        });

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
