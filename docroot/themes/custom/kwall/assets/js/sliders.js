(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Flex Slider.
   *
   * @type {{attach: Drupal.behaviors.flexSliderInit.attach}}
   */
  Drupal.behaviors.flexSliderInit = {
    attach: function (context, settings) {
      // Hero Slideshow.
      $('.hero-slideshow', context).once('heroSlideShowInit').each(function () {
        var $controls = $(this).parents('.view-hero-banner').find('.controls').insertAfter($(this).children('.slides')),
          $pause = $controls.find('.pause'),
          $play = $controls.find('.play'),
          autoplay = ($controls.data('autoplay') === '1'),
          mp4 = $(this).find('.hero-mp4').data('hero-mp4'),
          webm = $(this).find('.hero-webm').data('hero-webm'),
          video = $.parseHTML('<video height="auto" class="hero-video" muted="muted" loop="loop" autoplay="autoplay"></video>');

        if ($(window).width() > 768) {
          if (typeof mp4 !== undefined) {
            $(video).append('<source src="' + mp4 + '" type="video/mp4" />');
            $(this).find('.hero-mp4').prepend(video);
          }
          if (typeof webm !== undefined) {
            $(video).append('<source src="' + webm + '" type="video/webm" />');
            $(this).find('.hero-webm').append(video);
          }
        }

        $(this).flexslider({
          touch: true,
          slideshow: true,
          slideshowSpeed: 4000,
          controlNav: true,
          keyboard: true,
          multipleKeyboard: true,
          customDirectionNav: $(this).find('.controls a'),
          controlsContainer: $(this).find('.custom-controls-container'),
          start: function (slider) {
            if (autoplay === true) {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            }
            $pause.on('click', function () {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            });
            $play.on('click', function () {
              slider.play();
              $play.addClass('d-none');
              $pause.removeClass('d-none');
            });
          }
        });
      }); // END .hero-slideshow.

      // Interior Slideshow.
      $('.interior-slideshow', context).once('interiorSlideShowInit').each(function () {
        var $pause = $(this).find('.pause'),
          $play = $(this).find('.play'),
          autoplay = ($(this).data('autoplay') === '1');

        $(this).flexslider({
          touch: true,
          slideshow: true,
          slideshowSpeed: 4000,
          controlNav: true,
          customDirectionNav: $(this).find('.controls a'),
          controlsContainer: $(this).find('.custom-controls-container'),
          start: function (slider) {
            if (autoplay === true) {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            }
            $pause.on('click', function () {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            });
            $play.on('click', function () {
              slider.play();
              $play.addClass('d-none');
              $pause.removeClass('d-none');
            });
          }
        });
      }); // END Interior Slideshow.

      // Views Featured "articles" content slideshows.
      $('.featured-article-slider', context).once('featuredArticleSliderInit').each(function () {
        var $controls = $(this).parents('.featured-news-slider').find('.controls').insertAfter($(this).children('.slides')),
          $pause = $controls.find('.pause'),
          $play = $controls.find('.play');

        $(this).flexslider({
          touch: true,
          slideshow: true,
          slideshowSpeed: 4000,
          controlNav: true,
          customDirectionNav: $(this).find('.controls a'),
          controlsContainer: $(this).find('.custom-controls-container'),
          start: function (slider) {
            $pause.on('click', function () {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            });
            $play.on('click', function () {
              slider.play();
              $play.addClass('d-none');
              $pause.removeClass('d-none');
            });
          }
        });
      }); // END Views Featured content slideshows.

      // Views Featured "events" content slideshows.
      $('.featured-events-slider', context).once('featuredEventsSliderInit').each(function () {
        var $controls = $(this).parents('.featured-event-slider').find('.controls').insertAfter($(this).children('.slides')),
          $pause = $controls.find('.pause'),
          $play = $controls.find('.play');

        $(this).flexslider({
          touch: true,
          slideshow: true,
          slideshowSpeed: 4000,
          controlNav: true,
          customDirectionNav: $(this).find('.controls a'),
          controlsContainer: $(this).find('.custom-controls-container'),
          start: function (slider) {
            $pause.on('click', function () {
              slider.pause();
              $pause.addClass('d-none');
              $play.removeClass('d-none');
            });
            $play.on('click', function () {
              slider.play();
              $play.addClass('d-none');
              $pause.removeClass('d-none');
            });
          }
        });
      }); // END Views Featured content slideshows.
    }
  };

})(jQuery, Drupal, drupalSettings);
