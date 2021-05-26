(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Academic Isotope.
   *
   * @type {{attach: Drupal.behaviors.academicIsotopeInit.attach}}
   */
  Drupal.behaviors.academicIsotopeInit = {
    attach: function (context, settings) {
      $('.isotope-academics-filters', context).once('academicIsotopeInit').each(function () {
        var $grid = $('.view-academics').children('.view-content'),
          $checkboxes = $(this).find('.filter-toggle'),
          $reset = $(this).find('.isotope-reset');

        $grid.isotope({
          itemSelector: '.grid-item',
          layoutMode: 'fitRows'
        });

        // Bind filter button click.
        $checkboxes.on('click', function () {
          var filters = [];

          $(this).toggleClass('active');
          // Get checked checkboxes values.
          $checkboxes.filter('.active').each(function () {
            filters.push($(this).data('tid'));
          });
          filters = filters.join(', ');
          $grid.isotope({filter: filters});
        });

        // Bind filter rest.
        $reset.click(function () {
          $checkboxes.each(function () {
            $(this).removeClass('active');
          });
          $grid.isotope({filter: '*'});
        });

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
