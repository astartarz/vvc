(function ($, Drupal, drupalSettings, window) {

  "use strict";

  /**
   * Prepare alert template.
   *
   * @param item
   * @returns {string}
   */
  var getAlertTemplate = function (item) {
    var template = '';
      template = `<div class="alerts-slide-wrapper">
                  <div class="alert ${item.level}" data-id="${item.id}" data-level="${item.level}">
                   <div class="alert-icon">
                     <img src="/themes/custom/vvc/assets/images/alert-icon.svg" />
                    </div>
                    <div class="alert-desc">
                      <a href="#" class="close alert-close" data-dismiss="alert" aria-label="close" title="close"><i class="fas fa-times"></i></a>
                      <div class="alert-headline">${item.title}</div>
                      <div class="alert-caption">${item.message} ${item.field_link}</div>
                    </div>
                  </div>
                </div>`;
    return template;
  };

  var alreadyDismissed = function (nid) {
    if (nid) {
      var cookieName = "Drupal.visitor.kwall_alert_system_dismissed" + nid;
      console.log(cookieName);
      if ($.cookie(cookieName) === "true") {
        return true;
      }
    }
    return false;
  };

  $(document).ready(function () {
    $.getJSON("/api/oit/v1/alert/all", function (data) {
      var items = [];
      var carouselAlert = $(".alerts-slick-carousel-alert");
      if (carouselAlert.length) {

        var parentWrapper = $(carouselAlert).parents(".block-kwall-site-alert");

        // Add alert slides.
        $.each(data, function (key, item) {
          if (!alreadyDismissed(item.id)) {
            items.push(getAlertTemplate(item));
          }
        });

        $(items.join("")).appendTo(carouselAlert);
        parentWrapper.show();

        // var alertParams = {
        //   speed: 300,
        //   slidesToShow: 1,
        //   slidesToScroll: 1,
        //   centerMode: true,
        //   centerPadding: "0px",
        //   infinite: true,
        //   prevArrow: parentWrapper.find(".slick-prev"),
        //   nextArrow: parentWrapper.find(".slick-next"),
        // };
        //
        // // Initialize slider
        // carouselAlert.slick(alertParams);

        // On alert dismiss
        carouselAlert.find(".alert").on("close.bs.alert", function () {
          var nid = $(this).data("id");
          if(nid){
            var cookieName = "Drupal.visitor.kwall_alert_system_dismissed" + nid;
            $.cookie(cookieName, "true", {path: drupalSettings.path.baseUrl});
          }
          // var index = $(this).parents(".slick-slide").data("slick-index");
          // carouselAlert.slick("slickRemove", index);
          // carouselAlert.slick("unslick").slick(alertParams);
        });
      }
    });
  });

  /**
   * Drupal Ajax behaviours and ajax prototypes
   * @type {{attach: attach, detach: detach}}
   */
  Drupal.behaviors.oitAlerts = {
    attach: function (context, settings) {
    },
    detach: function (context) {
    }
  };

}(jQuery, Drupal, drupalSettings, window));
