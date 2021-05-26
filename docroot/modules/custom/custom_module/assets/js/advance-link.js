/**
 * @file
 * custom_theme functionality.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Switch the position (above/below) for some elements for mobile devices.
   *
   * @type {{attach: Drupal.behaviors.sideBar.attach}}
   */
  Drupal.behaviors.addModalContent = {
    attach: function (context, settings) {
      var $advance_link = $('.advanced-link.play', context);

      $advance_link.each(function () {
        var id = $(this).data('toggle');
        // Append each popup.
        $('body').append('<div id="' + id + '" class="al-popup hidden"><div class="al-wrapper"><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' + $(this).data("src") + '" id="video-link"  allowscriptaccess="always"></iframe></div></div></div>');
      });

      $advance_link.on('click', function (e) {
        e.preventDefault();
        var id = $(this).data('toggle');
        $('#' + id).toggleClass('active hidden');

        var player = $('.al-popup.active').find("iframe").get(0),
            src = $(player).attr('src');

        if (src.indexOf("youtube") >= 0) {
          postMessageToPlayer(player, {
            event: "command",
            func: "playVideo"
          });
        }
        else {
          postMessageToPlayer(player, {
            event: "command",
            func: "pauseVideo"
          });
        }

      });

      $(document).on('click touchstart', function (e) {
        if ($('.al-popup').is(e.target)) {
          var player = $('.al-popup.active').find("iframe").get(0),
              src = $(player).attr('src');
          if (src.indexOf("youtube") >= 0) {
            postMessageToPlayer(player, {
              event: "command",
              func: "pauseVideo"
            });
          }
          else {
            postMessageToPlayer(player, {
              method: "pause",
              value: 1
            });
          }
          $('.al-popup.active').toggleClass('active hidden');
        }
      });

      /**
       *
       * @param player
       * @param command
       */
      function postMessageToPlayer(player, command) {
        if (player == null || command == null) {
          return;
        }
        player.contentWindow.postMessage(JSON.stringify(command), "*");
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
