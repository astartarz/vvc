(function ($, Drupal, drupalSettings, window) {

  "use strict";

  // Windows vars
  let win = $(window);

  $.equalHeight = function (container) {
    var max_height = 0,
      height = 0;
    container.css('height', 'auto');

    $(container).each(function () {
      height = $(this).height();
      max_height = (height > max_height) ? height : max_height;
    });
    $(container).css('height', max_height + 'px');
  };

  /**
   * Drupal Ajax behaviours and ajax prototypes
   * @type {{attach: attach, detach: detach}}
   */
  Drupal.behaviors.theme = {
    attach: function (context, settings) {

      $('.view-story-cards.view-display-id-default .student-story-card').hover(function() {
        $('.student-story-card .video-teaser').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .video-full').addClass('hide-item');
        $('.student-story-card .open-video').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .close-video').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .card-img').removeClass('hide-item');
        $('.student-story-card').removeClass('opened-teaser');
        $('.student-story-card:not(".opened-video")').removeClass('opened-video');

        $(this).not('.opened-video').addClass('opened-teaser');
        $(this).find('.card-img').addClass('hide-item');
        $(this).not('.opened-video').find('.video-teaser').removeClass('hide-item');
        $(this).find('.open-video').removeClass('hide-item');
        var media = $(this).find('.video-teaser').find('video').get(0);
        media.pause();
        media.currentTime = 0;
        media.play();
      });

      $('.view-story-cards.view-display-id-default .student-story-card').mouseleave(function() {
        $('.student-story-card .video-teaser').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .video-full').addClass('hide-item');
        $('.student-story-card .open-video').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .close-video').addClass('hide-item');
        $('.student-story-card:not(".opened-video") .card-img').removeClass('hide-item');
        $('.student-story-card').removeClass('opened-teaser');
        $('.student-story-card:not(".opened-video")').removeClass('opened-video');
        $('.student-story-card:not(".opened-video") .card-content').removeClass('hide-item');
      });

      $('.view-story-cards.view-display-id-default .student-story-card').bind('click', function(event){
        event.preventDefault();
        $('.student-story-card-wrapper').find('.close-video').addClass('hide-item');
        $('.student-story-card .video-teaser').addClass('hide-item');
        $('.student-story-card .video-full').addClass('hide-item');
        $(this).parents('.student-story-card-wrapper').find('.close-video').removeClass('hide-item');
        $('.student-story-card .card-img').removeClass('hide-item');
        $('.student-story-card').removeClass('opened-teaser').removeClass('opened-video');
        $('.student-story-card').find('.card-content').removeClass('hide-item');

        let studentStoryCard = $(this).addClass('opened-video');
        studentStoryCard.find('.video-full').removeClass('hide-item');
        studentStoryCard.find('.video-teaser').addClass('hide-item');
        studentStoryCard.find('.card-img').addClass('hide-item');
        studentStoryCard.find('.card-content').addClass('hide-item');

        $("video").each(function() {
          $(this).get(0).pause();
          // media_full.currentTime = 0;
        });

        $(this).parent().find(".close-video").removeClass("hide-item");
        var media_full = $(this).find(".video-full").find("video").get(0);
        media_full.pause();
        media_full.muted = false;
        media_full.currentTime = 0;
        media_full.play();

      });

      $('.view-story-cards.view-display-id-default .close-video').bind('click', function(event){
        event.preventDefault();
        let studentStoryCard = $(this).parent().find('.student-story-card');
        $(this).addClass('hide-item');
        studentStoryCard.removeClass('opened-video');
        studentStoryCard.removeClass('opened-teaser');
        studentStoryCard.find('.video-full').addClass('hide-item');
        studentStoryCard.find('.video-teaser').removeClass('hide-item');
        studentStoryCard.find('.card-img').removeClass('hide-item');
        studentStoryCard.find('.card-content').removeClass('hide-item');
        var media_full = $(this).parent().find(".video-full").find("video").get(0);
        media_full.pause();
        media_full.currentTime = 0;
      });
    },
    detach: function (context) {
    }
  };

  /**
   * Equal height for homepage Column Section paragraph.
   *
   * @type {{attach: Drupal.behaviors.equalHeight.attach}}
   */
  Drupal.behaviors.equalHeight = {
    attach: function (context, settings) {
      var social_grid_row = $('.paragraph--type--social-grid .views-row', context);

      if ($(window).width() > 767) {
        $.equalHeight(social_grid_row);
      }
      $(window).on('resize', function () {
        if ($(window).width() > 767) {
          $.equalHeight(social_grid_row);
        }
        else {
          social_grid_row.css('height', 'auto');
        }
      });
    }
  };

  /**
   * Common tweaks for the theme.
   *
   * @type {{attach: Drupal.behaviors.gridderInit.attach}}
   */
  Drupal.behaviors.gridderInit = {
    attach: function (context, settings) {

      if ( $('.gridder') && !$('body').hasClass('gridder-init') ) {
        // Call Gridder

        $(document).ready(function() {
          $('.gridder').once('gridderInit').gridderExpander({
            scroll: true,
            scrollOffset: 30,
            scrollTo: "listitem",                  // panel or listitem
            animationSpeed: 600,
            animationEasing: "easeInOutExpo",
            showNav: true,                      // Show Navigation
            nextText: "Next",                   // Next button text
            prevText: "Previous",               // Previous button text
            closeText: "Close",                 // Close button text
            onStart: function(){
              // Gridder Inititialized
              // console.log('gridder init');
              //$('.gridder-list').matchHeight();
            },
            onContent: function(){
              // Gridder Content Loaded
              // console.log('gridder opened');
            },
            onClosed: function(){
              // Gridder Closed
              // console.log('gridder closed');
            }
          });
        });

        $(document).ajaxStop(function() {
          $('.gridder').once('gridderInit').gridderExpander({
            scroll: false,
            scrollOffset: 30,
            scrollTo: "panel",                  // panel or listitem
            animationSpeed: 600,
            animationEasing: "easeInOutExpo",
            showNav: true,                      // Show Navigation
            nextText: "Next",                   // Next button text
            prevText: "Previous",               // Previous button text
            closeText: "Close",                 // Close button text
            onStart: function(){
              // Gridder Inititialized
              // console.log('gridder init');
            },
            onContent: function(){
              // Gridder Content Loaded
              // console.log('gridder opened');
            },
            onClosed: function(){
              // Gridder Closed
              // console.log('gridder closed');
            }
          });
        });

        $('body').addClass('gridder-init');
      }

    }
  };

  /**
   * Sidebar toggle menu function
   */
  Drupal.behaviors.sidebarMenus = {
    attach: function (context, settings) {
      // toggle h2 tree
      $('.sidebar-menu-block > h2').once('sideMenu').on('click', function (el) {
        el.preventDefault();
        $(this).toggleClass('open');
        $(this).parent().find('>ul.menu').slideToggle('slow');
      });

      // toggle menu
      $('.sidebar-menu-block ul').once('sideSubMenu').on('click','li.dropdown-item.expanded svg', function (e) {
        e.preventDefault();
        if ($(this).parent().hasClass("active")) {
          $(this).parent().toggleClass('menu-closed');
        }
        else {
          $(this).parent().toggleClass('menu-opened');
        }
        $(this).parent().find('.dropdown-menu-list').slideToggle('slow');
      });

      // check sidebar length
      if ($("aside.layout-sidebar-right .region-sidebar-right #block-vvc-sidebarcontent").length > 0) {
        $('aside.layout-sidebar-right .region-sidebar-right').addClass('sidebar-content');
      }
    }
  };


  /**
   * We still need document ready event.
   */
  $(document).ready(function () {
    // statistic counter

    /**
     * Adjustable media wrap play button
     */

    if($(document).find('.paragraph--type--adjustable-media-and-content .field--name-field-media-video-file #play_button').length == 0) {
      $(document).find('.paragraph--type--adjustable-media-and-content .field--name-field-media-video-file video').removeAttr('controls').trigger('pause');
        $(document).find('.paragraph--type--adjustable-media-and-content .field--name-field-media-video-file').append('<button type="button" class="play" id="play_button">Play</button>');
    }
    $(document).on('click', '#play_button', function () {
      if($(this).hasClass('play')){
        $(this).parent().find('video').trigger('play');
        $(this).removeClass('play').addClass('pause');
      } else{
        $(this).parent().find('video').trigger('pause');
        $(this).removeClass('pause').addClass('play');
      }
    });

    /**
     * Carousel
     */
    $('.paragraph--type--image-carousel .image-carousel').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: true,
      prevArrow: $('.paragraph--type--image-carousel .slide-arrows .slide-prev'),
      nextArrow: $('.paragraph--type--image-carousel .slide-arrows .slide-next'),
      asNavFor: '.image-nav',
      fade: true,
    });
    $('.image-nav').slick({
      slidesToShow: 5,
      slidesToScroll: 1,
      asNavFor: '.paragraph--type--image-carousel .image-carousel',
      dots: false,
      centerMode: true,
      arrows:false,
      focusOnSelect: true
    });

    $('.paragraph--type--news-banner .articles-carousel .field--name-field-articles-banner-nodes').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: true,
      dots: true,
      autoplay: true,
      prevArrow: $('.paragraph--type--news-banner .articles-carousel .slide-arrows .slide-prev'),
      nextArrow: $('.paragraph--type--news-banner .articles-carousel .slide-arrows .slide-next'),
    });
    $('.paragraph--type--news-banner .articles-carousel .custom-controls .btnPlay').on('click', function() {
      if($(this).hasClass('pause')) {
        $(this).addClass('play').removeClass('pause');
        $('.paragraph--type--news-banner .articles-carousel .field--name-field-articles-banner-nodes').slick('slickPlay');
      }
      else {
        $(this).addClass('pause').removeClass('play');
        $('.paragraph--type--news-banner .articles-carousel .field--name-field-articles-banner-nodes').slick('slickPause');
      }
    });
    $('.paragraph--type--news-banner .articles-carousel .slide-arrows .slick-arrow a').on('click', function (e){
      e.preventDefault();
    });

    $('.paragraph--type--info-block-item').hover(function () {
        $(this).find('.field-caption').addClass('active');
      },
      function () {
        $(this).find('.field-caption').removeClass('active');
      });

    /**
     * Sticky Header
     */
    $(window).scroll(function () {
      var height = $(window).scrollTop();
      var header_height = $('.header-container').height() + $('#site-branding').height() + $('.layout-alerts').height();
      if (height > header_height) {
        $(".header-container").addClass('sticky');
        $("body").addClass('sticky-head');
        $("body").find('#back_to_top').addClass('show');
      }
      else {
        $(".header-container").removeClass('sticky');
        $("body").removeClass('sticky-head');
        $("body").find('#back_to_top').removeClass('show');
      }
    });

    /**
     * Scroll To Top
     */
    var btn = $('#back_to_top');

    btn.on('click', function(e) {
      e.preventDefault();
      $('html').animate({scrollTop:0}, '300');
    });

    /**
     * Toggle Menu
     */
    $(document).on('click', '.hamburger-menu .menu-bar', function (e) {
      e.preventDefault();
      $(this).toggleClass('active');
      if($(this).hasClass('active')){
        $(this).text('Close');
      } else{
        $(this).text('Menu');
      }
      $(document).find('.slide-in-nav').toggle();
    });

    /**
     * Toggle mobile menu
     */
    $(document).on('click', '.slide-in-nav .push-nav-menu .expanded.dropdown-item svg', function(e) {
      $(this).parent().toggleClass('menu-opened');
      $(this).next().toggle();
    });

    // Toggle Search form in header

    $('.utility-nav-block a.search-item').click(function (e) {
      e.preventDefault();
      $('.region-utility .desktop-search').toggleClass('active');
      $('.region-utility .desktop-search.active').find('.form-type-textfield input').focus();
    });

    /**
     * Image carousel arrows
     */
    $(document).find('.paragraph--type--image-carousel .slide-arrows a, .paragraph--type--image-carousel .image-nav a').on('click', function (e) {
      e.preventDefault();
    });

    setTimeout(function () {
      $(document).find('.paragraph--type--image-carousel .image-nav .slick-slide').each(function(){
        jQuery(this).attr('tabindex', '0');
      });
    }, 500);

    /**
     * Table
     */
    $('table th').each(function(i,elem) {
      var num = i + 1;
      $('table td:nth-child(' + num + ')').attr('data-label', $(elem).text());
    });

    // multiselect
    $('#edit-multi-select').multiselect();

    /*jQuery(".view-programs-gridder .view-filters .view-title::after").on("mouseover", function () {
      jQuery('.view-programs-gridder .view-header .container .information').css("display","block")
    }).on("mouseout", function (){
      jQuery('.view-programs-gridder .view-header .container .information').css("display","none");
    });*/

    // Program gridder Exposed form info icon
    $(".view-programs-gridder .view-filters .exposed-form-title .info-icon").hover(function () {
        $('.view-programs-gridder .view-header .container .information').css("display", "block");
      },
      function () {
        $('.view-programs-gridder .view-header .container .information').css("display", "none");
      });

    // Accessibility for elements that render after page load
    var checkExist = setInterval(function() {
      if ($('.block-kwall-site-alert .alert-icon > img').length) {
        console.log("Exists!");
        $('.block-kwall-site-alert .alert-icon > img').attr('alt', '');
        clearInterval(checkExist);
      }
    }, 100); // check every 100ms
  });

}(jQuery, Drupal, drupalSettings, window));
