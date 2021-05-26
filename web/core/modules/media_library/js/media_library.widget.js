/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, Sortable) {
  Drupal.behaviors.MediaLibraryWidgetSortable = {
    attach: function attach(context) {
      var selection = context.querySelectorAll('.js-media-library-selection');
      selection.forEach(function (widget) {
        Sortable.create(widget, {
          draggable: '.js-media-library-item',
          handle: '.js-media-library-item-preview',
          onEnd: function onEnd() {
            $(widget).children().each(function (index, child) {
              $(child).find('.js-media-library-item-weight').val(index);
            });
          }
        });
      });
    }
  };

  Drupal.behaviors.MediaLibraryWidgetToggleWeight = {
    attach: function attach(context) {
      var strings = {
        show: Drupal.t('Show media item weights'),
        hide: Drupal.t('Hide media item weights')
      };
      $('.js-media-library-widget-toggle-weight', context).once('media-library-toggle').on('click', function (e) {
        e.preventDefault();
        $(e.currentTarget).toggleClass('active').text($(e.currentTarget).hasClass('active') ? strings.hide : strings.show).closest('.js-media-library-widget').find('.js-media-library-item-weight').parent().toggle();
      }).text(strings.show);
      $('.js-media-library-item-weight', context).once('media-library-toggle').parent().hide();
    }
  };

  Drupal.behaviors.MediaLibraryWidgetEditItem = {
    attach: function attach() {
      $('.media-library-widget .js-media-library-item a[href]')
        .once('media-library-edit')
        .each(function() {
          var mediaEntity = $(this).closest(
            '.media[data-drupal-selector]',
          );
          var elementSettings = {
            progress: { type: 'throbber' },
            dialogType: 'modal',
            dialog: { width: '80%' },
            dialogRenderer: null,
            base: $(this).attr('id'),
            element: this,
            url: $(this).attr('href').concat("?selector=").concat(mediaEntity.attr('data-drupal-selector')),
            event: 'click',
          };
          Drupal.ajax(elementSettings);
        });
    },
  };

  Drupal.behaviors.MediaLibraryWidgetDisableButton = {
    attach: function attach(context) {
      $('.js-media-library-open-button[data-disabled-focus="true"]', context).once('media-library-disable').each(function () {
        var _this = this;

        $(this).focus();

        setTimeout(function () {
          $(_this).attr('disabled', 'disabled');
        }, 50);
      });
    }
  };
})(jQuery, Drupal, Sortable);