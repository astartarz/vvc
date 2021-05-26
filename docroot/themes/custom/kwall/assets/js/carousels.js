(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Slick Slider.
   *
   * @type {{attach: Drupal.behaviors.slickSliderInit.attach}}
   */
  Drupal.behaviors.slickSliderInit = {
    attach: function (context, settings) {

      // Person Carousel.
      $('.view-id-carousels.view-display-id-block_1 > .view-content', context).once('slickSliderInit').each(function () {
        var carouselSelector = '.paragraph--type--person-carousel',
          autoplay = $(this).parents(carouselSelector).data('autoplay'),
          display_count = $(this).parents(carouselSelector).data('display'),
          scroll_count = $(this).parents(carouselSelector).data('scroll');

        if (display_count >= '3') {
          $(this).slick({
            autoplay: autoplay,
            autoplaySpeed: 4000,
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: display_count,
            slidesToScroll: scroll_count,
            nextArrow: '<button type="button" data-role="none" class="slick-next" aria-label="Next" role="button"><i class="fas fa-chevron-right"></i></button>',
            prevArrow: '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" role="button"><i class="fas fa-chevron-left"></i></button>',
            responsive: [
              {
                breakpoint: 991,
                settings: {
                  slidesToShow: 3,
                  slidesToScroll: 3,
                  infinite: true,
                  dots: true
                }
              },
              {
                breakpoint: 767,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2
                }
              },
              {
                breakpoint: 480,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1
                }
              }
              // You can unslick at a given breakpoint now by adding:
              // settings: "unslick"
              // instead of a settings object.
            ]
          });
        }
        if (display_count === '2') {
          $(this).slick({
            autoplay: autoplay,
            autoplaySpeed: 4000,
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: display_count,
            slidesToScroll: scroll_count,
            responsive: [
              {
                breakpoint: 767,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2
                }
              },
              {
                breakpoint: 480,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1
                }
              }
              // You can unslick at a given breakpoint now by adding:
              // settings: "unslick"
              // instead of a settings object.
            ]
          });
        }
        if (display_count === '1') {
          $(this).slick({
            autoplay: autoplay,
            autoplaySpeed: 4000,
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 1,
            slidesToScroll: 1
          });
        }
      });

      // Video Tab Carousel.
      $('.paragraph--type--video-tab-carousel .video-carousel', context).once('flexSliderInit').each(function () {
        var $video_carousel = $(this),
          $videos = $(this).find('.tab-video-wrap'),
          $tabs = $(this).find('.tab-title-wrap');

        // Desktop tab toggle - window resize.
        if ($(window).width() >= 768) {
          $video_carousel.find('.tab-title').on('click', function () {
            var video_tab = $(this).data('tab-target');

            $('.tab-item-content').each(function () {
              $(this).removeClass('active');
            });
            $(video_tab).addClass('active');
          });
        }
        // Mobile Slider Display - initial load.
        if ($(window).width() <= 767) {
          $videos.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            asNavFor: '.tab-title-wrap'
          });
          $tabs.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            asNavFor: '.tab-video-wrap',
            arrows: true,
            dots: true,
            focusOnSelect: true
          });
        }

        // Slider/Tab Display Toggle.
        $(window).on('resize', function () {

          // Desktop tab toggle - window resize.
          if ($(window).width() >= 768 && $video_carousel.hasClass('mobile-display')) {
            $video_carousel.removeClass('mobile-display');
            $tabs.slick('unslick');
            $videos.slick('unslick');
            $video_carousel.find('.tab-title').on('click', function () {
              var video_tab = $(this).data('tab-target');
              $('.tab-item-content').each(function () {
                $(this).removeClass('active');
              });
              $(video_tab).addClass('active');
            });
          }

          // Mobile Slider Display - window resize.
          if ($(window).width() <= 767 && !$video_carousel.hasClass('mobile-display')) {
            $videos.slick({
              slidesToShow: 1,
              slidesToScroll: 1,
              arrows: false,
              fade: true,
              asNavFor: '.tab-title-wrap'
            });
            $tabs.slick({
              slidesToShow: 1,
              slidesToScroll: 1,
              asNavFor: '.tab-video-wrap',
              arrows: true,
              dots: true,
              focusOnSelect: true
            });
            $video_carousel.addClass('mobile-display');
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
