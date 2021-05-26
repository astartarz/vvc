(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Common tweaks for the theme.
   *
   * @type {{attach: Drupal.behaviors.commonTweaks.attach}}
   */
  Drupal.behaviors.commonTweaks = {
    attach: function (context, settings) {
      $(window).on('load', function () {
        // Background image Parallax effect.
        $('.full-width-img-section', context).once('commnTweaks').each(function () {
          var $img_section = $(this);
          if (!$img_section.hasClass('init')) {
            $img_section.css({
              'background-position': 'center center'
            })
          }
          else {
            var controller = new ScrollMagic.Controller(),
              $scroll_duration = $(window).height() + $img_section.outerHeight(),
              paragraph_id = '#' + $img_section.attr('id'),
              scroll_speed = ($img_section.data('parallax-speed') === '') ? '5' : $img_section.data('parallax-speed');

            // Build scene.
            var scene = new ScrollMagic.Scene({
              triggerElement: paragraph_id,
              duration: $scroll_duration,
              triggerHook: 'onEnter'
            }).addTo(controller)
              .on("progress", function (e) {
                $img_section.css('background-position', 'center ' + (e.progress.toFixed(2) * (100 / scroll_speed)) + '%');
              });
          }
        });

        // Add tabfocus class to links when tab focused
        setTimeout(function () {
          var $all_links_inputs = $('.skip-link a, .dialog-off-canvas-main-canvas a, input[type=search], input[type=text], button');
          var mousedown = false;
          $all_links_inputs.on('mousedown', function () {
            mousedown = true;
          });
          $all_links_inputs.on('focusin', function () {
            if (!mousedown) {
              $(this).addClass("tabfocus");
            }
            mousedown = false;
          });
          $all_links_inputs.on('focusout', function () {
            $(this).removeClass('tabfocus');
          });
          // Remove aria-describedby attribute
          // if no matching element
          $('.slick-slider .slick-slide').each(function() {
            var describedById = jQuery(this).attr('aria-describedby');
            if ( $('#'+describedById).length == 0 ) $(this).removeAttr('aria-describedby');
          });

        }, 150);
      });

      /* flexslider */
      setTimeout(function() {
        $('.flexslider .controls .flex-prev').html('<span style="display: none;">Previous</span>');
        $('.flexslider .controls .flex-next').html('<span style="display: none;">Next</span>');
      }, 3000);

      // Hero Banner.
      $('.hero-banner-img-section', context).once('setHeroBannerImg').each(function () {
        $(this).css({
          'background-image': 'url(' + $(this).data('bg-img') + ')',
          'background-size': 'cover',
          'background-position': 'center top'
        })
      });

      // Featured Article Background Image.
      $('.view-id-article_view.view-display-id-attachment_1 .featured-post-wrap', context).once('setFeaturedArticleImage').each(function () {
        $(this).parents('.featured-news-grid.view-display-id-block_1 .attachment').css({
          'background-image': 'url(' + $(this).data('featured-article') + ')',
          'background-size': 'cover',
          'background-position': 'center center'
        });
      });

      // Featured Article Background Image.
      $('.view-id-event_view.view-display-id-attachment_1 .featured-event-wrap', context).once('setFeaturedArticleImage').each(function () {
        var bg_img = $(this).data('featured-event');
        $(this).parents('.featured-events-grid.view-display-id-block_3 .attachment').css({
          'background-image': 'url(' + $(this).data('featured-event') + ')',
          'background-size': 'cover',
          'background-position': 'center center'
        });
      });

      $('.paragraph--type--accordion', context).once('accordionInit').each(function () {
        var $toggle_all = $(this).find('.toggle-all');

        if ($toggle_all.length) {
          var $accordion_parent = $(this),
            $accordion_item = $(this).find('.collapse');

          $toggle_all.on('click', function () {
            if (!$accordion_parent.hasClass('show-all')) {
              $accordion_item.each(function () {
                $(this).collapse('show');
              });
              $accordion_parent.addClass('show-all');
            }
            else {
              $accordion_item.each(function () {
                $(this).collapse('hide');
              });
              $accordion_parent.removeClass('show-all');
            }
          });
        }
      });

      // add arrow keys to tabbing
      $('.tb-megamenu-main').once('megamenuaccessible').each(function () {
        $(this).find('.tb-megamenu-item.level-1 a').focus(function () {
          var submenu = $(this).parent().find('.tb-megamenu-submenu');
          if (submenu.length && $(this).parent().offset().left + submenu.outerWidth() > $(window).width()) {
            submenu.css({'left':' unset', 'right': 0});
          }
        });
        $(this).find('.tb-megamenu-item.level-1 a').hover(function () {
          var submenu = $(this).parent().find('.tb-megamenu-submenu');
          if (submenu.length && $(this).parent().offset().left + submenu.outerWidth() > $(window).width()) {
            submenu.css({'left':' unset', 'right': 0});
          }
        });
        $(this).find('.tb-megamenu-item.level-1').hover(function () {
          var submenu = $(this).find('.tb-megamenu-submenu');
          if (submenu.length && $(this).offset().left + submenu.outerWidth() > $(window).width()) {
            submenu.css({'left':' unset', 'right': 0});
          }
        });

        $(this).find('.tb-megamenu-item.level-1 a').keydown(function (e) {
          switch(e.which) {
            case 37: // left
              e.preventDefault();
              var lastLink = $(this).parent().prev();
              if (lastLink.length > 0) {
                lastLink.find('a').focus();

                var submenu = lastLink.find('.tb-megamenu-submenu');
                if (submenu.length && lastLink.offset().left + submenu.outerWidth() > $(window).width()) {
                  submenu.css({'left':' unset', 'right': 0});
                }
              }
            break;

            case 38: // up
              e.preventDefault();
              var lastLink = $(this).parent().prev();
              if (lastLink.length > 0) {
                lastLink.find('a').focus();

                var submenu = lastLink.find('.tb-megamenu-submenu');
                if (submenu.length && lastLink.offset().left + submenu.outerWidth() > $(window).width()) {
                  submenu.css({'left':' unset', 'right': 0});
                }
              }
            break;

            case 39: // right
              e.preventDefault();
              var nextLink = $(this).parent().next();
              if (nextLink.length > 0) {
                nextLink.find('a').focus();

                var submenu = nextLink.find('.tb-megamenu-submenu');
                if (submenu.length && nextLink.offset().left + submenu.outerWidth() > $(window).width()) {
                  submenu.css({'left':' unset', 'right': 0});
                }
              }
            break;

            case 40: // down
              e.preventDefault();
              var nextLink = $(this).parent().find('.tb-megamenu-item.level-2:first > a');
              if (nextLink.length > 0) {
                nextLink.focus();
              }
              else {
                nextLink = $(this).parent().find('.tb-megamenu-item.level-2:nth-child(2) > a');
                if (nextLink.length > 0) {
                  nextLink[0].focus();
                }
              }
            break;
          }
        });
        $(this).find('.tb-megamenu-item.level-2 a').keydown(function (e) {
          switch(e.which) {
            case 37: // left
              e.preventDefault();
              var lastColumn = $(this).closest('.tb-megamenu-column').prev();
              if (lastColumn.length > 0) {
                // go back
                var lastLink = lastColumn.find('.tb-megamenu-item:first a');
                if (lastLink.length > 0) {
                  lastLink.focus();
                }
                else {
                  // sometimes there's a header
                  lastLink = lastColumn.find('.tb-megamenu-item:nth-child(2) a');
                  lastLink.focus();
                }
              }
              else {
                // go out
                $(this).closest('.tb-megamenu-item.level-1').children('a').focus();
              }
            break;

            case 38: // up
              e.preventDefault();
              var lastList = $(this).parent().prev();
              if (lastList.length > 0) {
                var lastLink = lastList.find('a');
                if (lastLink.length > 0) {
                  lastLink.focus();
                }
                else {
                  // go out
                  $(this).closest('.tb-megamenu-item.level-1').children('a').focus();
                }
              }
              else {
                // go out
                $(this).closest('.tb-megamenu-item.level-1').children('a').focus();
              }
            break;

            case 39: // right
              e.preventDefault();
              var nextColumn = $(this).closest('.tb-megamenu-column').next();
              if (nextColumn.length > 0) {
                // go back
                var nextLink = nextColumn.find('.tb-megamenu-item:first a');
                if (nextLink.length > 0) {
                  nextLink.focus();
                }
                else {
                  // sometimes there's a header
                  nextLink = nextColumn.find('.tb-megamenu-item:nth-child(2) a');
                  nextLink.focus();
                }
              }
              else {
                // go out
                $(this).closest('.tb-megamenu-item.level-1').children('a').focus();
              }
            break;

            case 40: // down
              e.preventDefault();
              var nextLink = $(this).parent().next();
              if (nextLink.length > 0) {
                nextLink.find('a').focus();
              }
              else {
                // go out
                $(this).closest('.tb-megamenu-item.level-1').children('a').focus();
              }
            break;
          }
        });
      });
    }
  };

  /**
   * Sidebar Accordion.
   *
   * @type {{attach: Drupal.behaviors.sidebarAccordionInit.attach}}
   */
  Drupal.behaviors.sidebarAccordionInit = {
    attach: function (context, settings) {
      // Sidebar Accordion Toggle.
      var $sidebar_menu = $('.sidebar-menu-block', context);

      // Sidebar navigation toggle.
      $sidebar_menu.once('sidebarAccordionInit1').each(function () {
        // Open active submenu.
        $(this).find('.expanded.dropdown.active').addClass('open');
        var $toggler = $(this).find('.expanded.dropdown-item .fa');

        $toggler.on('click', function () {
          $(this).toggleClass('fa-angle-down fa-angle-up');
          $(this).parent('li').toggleClass('toggled');
          $(this).siblings($(this).attr('data-toggle')).slideToggle();
        });
        // Toggle on 'Enter' keypress.
        $toggler.keypress(function (e) {
          if (e.keyCode === 13) {
            $(this).toggleClass('fa-angle-down fa-angle-up');
            $(this).parent('li').toggleClass('toggled');
            $(this).siblings($(this).attr('data-toggle')).slideToggle();
          }
        });
      });

      $sidebar_menu.find('.is-active').once('sidebarAccordionInit2').each(function () {
        var $parents = $(this).parents('.dropdown-menu-list'),
          $is_active = $(this).parent('.dropdown-item');

        $parents.siblings('.fa-angle-down').toggleClass('fa-angle-down fa-angle-up');
        $parents.slideDown();
        $is_active.children('.fa-angle-down').toggleClass('fa-angle-down fa-angle-up');
        $is_active.addClass('toggled').children('.dropdown-menu-list').slideDown();
      });
    }
  };

  /**
   * Calender Rest API.
   *
   * @type {{attach: Drupal.behaviors.calenderRestAPI.attach}}
   */
  Drupal.behaviors.calenderRestAPI = {
    attach: function (context, settings) {
      $('.calendar-month', context).once('calenderRestAPI').each(function () {
        $(this).find('.event-content').on('click', function () {
          var date = $(this).data('date-trigger'),
            parent = $(this).parent('.contents');

          if (!parent.hasClass('import')) {
            $.getJSON("/rest/events/day/" + date, function (data) {
              var items = [],
                count = data.length - 1;

              $.each(data, function (key, val) {
                if (key === 0) {
                  items.push("<li><span class='fa fa-close'></span></li>");
                }
                // FIXME: Unresolved variable val.nothing.
                items.push("<li id='event-" + date + '-' + key + "'>" + val.nothing + "</li>");
                if (key >= 2 && key === count) {
                  items.push("<li><a href='/events/day/" + date + "'>" + Drupal.t('View All') + " <i class='fa fa-chevron-right'></i></a></li>");
                }
              });

              $("<ul/>", {
                "class": "popup list-unstyled",
                html: items.join("")
              }).appendTo(parent);

            });
            parent.addClass('import open');
          }
          else {
            parent.toggleClass('open close');
          }
        });
      });
    }
  };

  /**
   * Events filters.
   *
   * @type {{attach: Drupal.behaviors.eventsCalendarFilters.attach}}
   */
  Drupal.behaviors.eventsCalendarFilters = {
    attach: function (context, settings) {
      // calender exposed form select category
      var $selectedCat = [];
      var $checkboxItem = $('.calendar-category input[type="checkbox"]');
      $($checkboxItem).each(function () {
        var optText = $.trim($(this).parent().find('label').text());
        var valueN = $(this).attr('value');
        if ($(this).is(':checked')) {
          $selectedCat.push('<span>' + optText + '<a class="close" data-target="' + valueN + '" href="#">×</a></span>');
          $('.calendar-category summary .title').html($selectedCat.join(' '));
        } else {
          $selectedCat = $.grep($selectedCat, function (value) {
            return value != '<span>' + optText + '<a class="close" data-target="' + valueN + '" href="#">×</a></span>';
          });
        }
      });

      $($checkboxItem).on('click', function () {
        var optText = $.trim($(this).parents('.form-type-checkbox').find('label').text());
        var valueN = $(this).attr('value');
        if ($(this).is(':checked')) {
          $selectedCat.push('<span>' + optText + '<a class="close" data-target="' + valueN + '" href="#">×</a></span>');
        } else {
          $selectedCat = $.grep($selectedCat, function (value) {
            return value != '<span>' + optText + '<a class="close" data-target="' + valueN + '" href="#">×</a></span>';
          });
        }
        $('.calendar-category summary .title').html($selectedCat.join(' '));
      });

      $('.calendar-category').on('click', '.close', function (e) {
        e.preventDefault();
        var val = $(this).attr('data-target');
        $(".calendar-category").find('input[value="' + val + '"]').click();
        // $(this).parent().remove();
      });
      $('.region-site-alerts').addClass('processed');
    }
  };

})(jQuery, Drupal, drupalSettings);
