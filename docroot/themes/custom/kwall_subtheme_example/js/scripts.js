(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Equal height.
   *
   * @param container
   */
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
   * Common tweaks for the theme.
   *
   * @type {{attach: Drupal.behaviors.commonTweaks.attach}}
   */
  Drupal.behaviors.commonTweaks = {
    attach: function (context, settings) {
      // Article Slide Show.
      $('.article-slideshow').once('flexSliderInit').each(function () {
        $(this).flexslider({
          touch: true,
          slideshow: false,
          slideshowSpeed: 4000,
          controlNav: false,
          customDirectionNav: jQuery(this).find('.controls a'),
          start: function (slider) {
            $('.total-slides').text(slider.count);
          },
          after: function (slider) {
            $('.current-slide').text(slider.currentSlide + 1);
          }
        });
      });

      // Background image Parallax effect.
      $(window).on('load', function () {
        $('.full-width-img-section', context).once('backgroundParallax').each(function () {
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

            // Build a scene.
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
      });

      // Change links to for alpha block on directory page.
      $('.view-directory.view-display-id-block_1 .alpaha-item a').each(function () {
        var href = $(this).attr('href'),
          index = href.indexOf('y/'),
          index2 = href.indexOf('?');

        if (index && index2 === -1) {
          var letter = href.substr(index + 2);

          href = href.substr(0, index + 1) + '?field_name_family=' + letter;
          $(this).attr('href', href);
        }
      });

      // FIXME: This is performance issue to use $(document).ready()
      //  inside Drupal.behaviors and not accurate.
      //  Stop copy-paste code from the internet.
      $(document).ready(function () {
        // Hero Banner.
        $('.hero-banner-img-section', context).once('setHeroBannerImg').each(function () {
          $(this).css({
            'background-image': 'url(' + $(this).data('bg-img') + ')',
            'background-size': 'cover',
            'background-position': 'center top'
          })
        });

        // TODO: Bellow the duplicates of the similar code. Maybe we can adjust.

        // Featured Article Background Image for articles.
        $('.view-id-article_view.view-display-id-attachment_1 .featured-post-wrap', context).once('setFeaturedArticleImage').each(function () {
          $(this).parents('.featured-news-grid.view-display-id-block_1 .attachment').css({
            'background-image': 'url(' + $(this).data('featured-article') + ')',
            'background-size': 'cover',
            'background-position': 'center center'
          });
        });

        // Featured Article Background Image for news.
        $('.featured-news-grid > .view-content .featured-post-wrap', context).once('setFeaturedArticleImage').each(function () {
          $(this).parents('.view-content > div').css({
            'background-image': 'url(' + $(this).data('featured-article') + ')',
            'background-size': 'cover',
            'background-position': 'center center'
          });
        });


        // Featured Article Background Image for events.
        $('.view-id-event_view.view-display-id-attachment_1 .featured-event-wrap', context).once('setFeaturedArticleImage').each(function () {
          $(this).parents('.featured-events-grid.view-display-id-block_3 .attachment').css({
            'background-image': 'url(' + $(this).data('featured-event') + ')',
            'background-size': 'cover',
            'background-position': 'center center'
          });
        });

      });

      $('.paragraph--type--accordion', context).once('accordionInit').each(function () {
        var $toggle_all = $(this).find('.toggle-all'),
          $collapse_show = $('.paragraph--type--accordion .collapse.show'),
          $header = $(this).find('.card-header');

        if ($collapse_show.length > 0) {
          $collapse_show.prev().find('a').attr('aria-expanded', 'true');
        }

        $header.on('click', function () {
          var $toggler = $(this).find('svg');
          if ($toggler.data('icon') === 'minus') {
            $toggler.attr('data-icon', 'plus');
          }
          else {
            $toggler.attr('data-icon', 'minus');
          }
        });

        if ($toggle_all.length > 0) {
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
    }
  };

  /**
   * Common tweaks for the theme.
   *
   * @type {{attach: Drupal.behaviors.sidebarAccordionInit.attach}}
   */
  Drupal.behaviors.sidebarAccordionInit = {
    attach: function (context, settings) {
      // Sidebar Accordion Toggle.
      var $sidebar_menu = $('.sidebar-menu-block', context);

      // FIXME: This is performance issue to use $(document).ready()
      //  inside Drupal.behaviors and not accurate.
      //  Stop copy-paste code from the internet.
      $(document).ready(function () {
        $sidebar_menu.once('sidebarAccordionInit').each(function () {
          // Open active submenu.
          $(this).find('.expanded.dropdown.active').addClass('open');
          var $toggler = $(this).find('.expanded.dropdown-item .fai');

          $toggler.on('click', function () {
            $(this).toggleClass('fa-angle-down fa-angle-up');
            $(this).parent('li').toggleClass('toggled');
            $(this).siblings($(this).attr('data-toggle')).slideToggle();
          });
          //  Toggle on 'Enter' keypress.
          $toggler.keypress(function (e) {
            if (e.keyCode == 13) {
              $(this).toggleClass('fa-angle-down fa-angle-up');
              $(this).parent('li').toggleClass('toggled');
              $(this).siblings($(this).attr('data-toggle')).slideToggle();
            }
          });
        });

        $sidebar_menu.find('.is-active').once('sidebarAccordionInit').each(function () {
          var $parents = $(this).parents('.dropdown-menu-list'),
            $is_active = $(this).parent('.dropdown-item');

          $parents.siblings('.fa-angle-down').toggleClass('fa-angle-down fa-angle-up');
          $parents.slideDown();
          $is_active.children('.fa-angle-down').toggleClass('fa-angle-down fa-angle-up');
          $is_active.addClass('toggled').children('.dropdown-menu-list').slideDown();
        });


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
      // Sidebar Accordion Toggle.
      // FIXME: This is performance issue to use $(document).ready()
      //  inside Drupal.behaviors and not accurate.
      //  Stop copy-paste code from the internet.
      $(document).ready(function () {
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
      });
    }
  };

  /**
   * Click, mouse leave functionality for utility menu.
   *
   * @type {{attach: Drupal.behaviors.utilityMenu.attach}}
   */
  Drupal.behaviors.utilityMenu = {
    attach: function (context, settings) {
      var count = 0,
        $link = $('.utility-nav-block .menu-item--expanded > a');

      $link.unbind("click");
      $link.on('click touch', function (e) {
        e.preventDefault();
        var parent = $(this).parent(),
          menu = parent.find('ul');

        // TODO: Replace to jQuery toggle.
        if (parent.hasClass('open')) {
          parent.removeClass('open');
          menu.slideUp();
        }
        else {
          parent.addClass('open');
          menu.slideDown();
        }
      });

      $(document).on('mouseenter', '.utility-nav-block .menu-item--expanded, .utility-nav-block .menu-item--expanded ul', function () {
        count++;
      }).on('mouseleave', '.utility-nav-block .menu-item--expanded, .utility-nav-block .menu-item--expanded ul', function () {
        count--;
        if (!count) {
          $('.utility-nav-block .menu-item--expanded.open ul').slideUp();
          $('.utility-nav-block .menu-item--expanded.open').removeClass('open');
        }
      });

      $('.tb-megamenu-submenu', context).once('mega_submenus').each(function () {
        var height = $(this).actual('height');
        $(this).css('display', 'block');
        $(this).addClass('processed');
        $(this).css('height', height + 'px');
      });
    }
  };

  /**
   * Equal height for homepage Column Section paragraph.
   *
   * @type {{attach: Drupal.behaviors.equalHeight.attach}}
   */
  Drupal.behaviors.equalHeight = {
    attach: function (context, settings) {
      var columns = $('.paragraph--type--column-section > .row > .col-md-4 .field--name-field-view-reference, .paragraph--type--column-section > .row > .col-md-4 .column-content-inner', context),
        columns_articles_events = $('.paragraph--type--recent-articles-upcoming-events .view-article-view, .paragraph--type--recent-articles-upcoming-events .view-event-view', context),
        column_titles = $('.paragraph--type--column-section .field--name-field-title', context),
        column_titles_articles_events = $('.paragraph--type--recent-articles-upcoming-events .field--name-field-title, .paragraph--type--recent-articles-upcoming-events .field--name-field-title-2', context);

      if ($(window).width() > 767) {
        $.equalHeight(columns);
        $.equalHeight(columns_articles_events);
        $.equalHeight(column_titles);
        $.equalHeight(column_titles_articles_events);
      }
      $(window).on('resize', function () {
        if ($(window).width() > 767) {
          $.equalHeight(columns);
          $.equalHeight(columns_articles_events);
          $.equalHeight(column_titles);
          $.equalHeight(column_titles_articles_events);
        }
        else {
          columns.css('height', 'auto');
          column_titles.css('height', 'auto');
          columns_articles_events.css('height', 'auto');
          column_titles_articles_events.css('height', 'auto');
        }
      });
    }
  };

  /**
   * A custom functionality for Ajax.
   *
   * @type {{attach: Drupal.behaviors.customAjaxComplete.attach}}
   */
  Drupal.behaviors.customAjaxComplete = {
    attach: function (context, settings) {
      var eventContent = $('.view-events-calendar .single-day');

      if (eventContent.length > 0) {
        eventContent.find('.item').click(function () {
          eventContent.find('.item').removeClass('active');
          eventContent.find('.event-popup').removeClass('active');
          $(this).addClass('active');
          var dataId = eventContent.find('.item.active .event-content').data("id");

          eventContent.find('.event-popup[data-id=' + dataId + ']').addClass('active');
          eventContent.find('.event-popup.active').each(function () {
            var height = $(this).outerHeight() + $(this).parents('.item.active').outerHeight() - 10;
            $(this).parents('ul.popup').css('top', -height);
          });
        });

        $(document).ajaxComplete(function (event, xhr, settings) {
          if (eventContent.find('.item.active .event-content').length > 0) {
            var dataId = eventContent.find('.item.active .event-content').data("id");

            eventContent.find('.event-popup[data-id=' + dataId + ']').addClass('active');
            eventContent.find('.event-popup.active').each(function () {
              var height = $(this).outerHeight() + $(this).parents('.item.active').outerHeight() - 10;
              $(this).parents('ul.popup').css('top', -height);
            });
          }
        });

        $(document).on('click', '.view-events-calendar .single-day span.fa-close', function () {
          eventContent.find('.item').removeClass('active');
          eventContent.find('.event-popup').removeClass('active');
        });
      }
    }
  };

  /**
   * Academic filters.
   *
   * @type {{attach: Drupal.behaviors.academicFilters.attach}}
   */
  Drupal.behaviors.academicFilters = {
    attach: function (context, settings) {
      $('.view-academic-filter-taxonomy-terms .view-content .filter-button .filter-toggle').each(function () {
        var $class = $(this).data('tid');
        if (!$('.view-academics ' + $class).length > 0) {
          $(this).parent().addClass('inactive');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
