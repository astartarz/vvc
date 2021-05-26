(function ($, Drupal) {
  "use strict";

  /**
   * Check if element is in viewport.
   */
  $.fn.isOnScreen = function () {
    var win = $(window);

    var viewport = {
      top: win.scrollTop(),
      left: win.scrollLeft(),
    };
    viewport.right = viewport.left + win.width();
    viewport.bottom = viewport.top + win.height();

    var bounds = this.offset();
    bounds.right = bounds.left + this.outerWidth();
    bounds.bottom = bounds.top + this.outerHeight();

    return !(
      viewport.right < bounds.left ||
      viewport.left > bounds.right ||
      viewport.bottom < bounds.top ||
      viewport.top > bounds.bottom
    );
  };

  /**
   * The common functionality for the module.
   *
   * @type {{attach: Drupal.behaviors.kwall_map.attach}}
   */
  Drupal.behaviors.kwall_map = {
    attach: function (context, drupalSettings) {
      function locationsSidebarHeight() {
        var mapHeight = $(".geolocation-common-map-container").height();
        $(".geolocation-common-map-locations").height(mapHeight);
      }

      $(window).bind("load", function () {
        if (
          Drupal.geolocation != undefined &&
          !$("body").hasClass("kwall-map-processed")
        ) {
          $.each(drupalSettings.kwall_map.locations, function (i, el) {
            var KwallMapSettings = drupalSettings.kwall_map.locations[i],
              imgOverlay = KwallMapSettings["overlay_" + i],
              neLat = KwallMapSettings["neLat_" + i],
              neLon = KwallMapSettings["neLon_" + i],
              swLat = KwallMapSettings["swLat_" + i],
              swLon = KwallMapSettings["swLon_" + i];

            if (imgOverlay != "") {
              var southWest = new google.maps.LatLng(swLat, swLon);
              var northEast = new google.maps.LatLng(neLat, neLon);
              var overlayBounds = new google.maps.LatLngBounds(
                southWest,
                northEast
              );

              kwallOverlay.prototype = new google.maps.OverlayView();

              function kwallOverlay(bounds, image, map) {
                // Initialize all properties.
                this.bounds_ = bounds;
                this.image_ = image;
                this.map_ = map;
                // Define a property to hold the image's div. We'll
                // actually create this div upon receipt of the onAdd()
                // method so we'll leave it null for now.
                this.div_ = null;
                // Explicitly call setMap on this overlay.
                this.setMap(map);
              }

              /**
               * onAdd is called when the map's panes are ready and the overlay
               * has been added to the map.
               */
              kwallOverlay.prototype.onAdd = function () {
                var div = document.createElement("div");
                div.style.borderStyle = "none";
                div.style.borderWidth = "0px";
                div.style.position = "absolute";
                // Create the img element and attach it to the div.
                var img = document.createElement("img");
                img.src = this.image_;
                img.style.width = "100%";
                img.style.height = "100%";
                img.style.position = "absolute";
                div.appendChild(img);
                this.div_ = div;
                // Add the element to the "overlayLayer" pane.
                var panes = this.getPanes();
                panes.overlayLayer.appendChild(div).style["zIndex"] = 1001;
              };
              kwallOverlay.prototype.draw = function () {
                // We use the south-west and north-east
                // coordinates of the overlay to peg it to the correct position and size.
                // To do this, we need to retrieve the projection from the overlay.
                var overlayProjection = this.getProjection();

                // Retrieve the south-west and north-east coordinates of this overlay
                // in LatLngs and convert them to pixel coordinates.
                // We'll use these coordinates to resize the div.
                var sw = overlayProjection.fromLatLngToDivPixel(
                  this.bounds_.getSouthWest()
                );
                var ne = overlayProjection.fromLatLngToDivPixel(
                  this.bounds_.getNorthEast()
                );

                // Resize the image's div to fit the indicated dimensions.
                var div = this.div_;
                div.style.left = sw.x + "px";
                div.style.top = ne.y + "px";
                div.style.width = ne.x - sw.x + "px";
                div.style.height = sw.y - ne.y + "px";
              };

              setTimeout(function () {
                new kwallOverlay(
                  overlayBounds,
                  imgOverlay,
                  Drupal.geolocation.maps[0].googleMap
                );

                $('.geolocation-common-map-container div[title*="href"]').each(
                  function () {
                    var pin_title = $(this).attr("title");
                    var pin_title_cleaned = pin_title.replace(
                      /(<([^>]+)>)/gi,
                      ""
                    );
                    var pin_title_cleaned = pin_title_cleaned.replace(
                      /&amp;/g,
                      "&"
                    );
                    var pin_title_cleaned = pin_title_cleaned.trim();
                    $(this).attr("title", pin_title_cleaned);
                  }
                );
              }, 250);
            }
          });

          var styles = drupalSettings.kwall_map["style"];
          if (styles != "") {
            // add custom map styles
            setTimeout(function () {
              Drupal.geolocation.maps[0].googleMap.setOptions({
                styles: JSON.parse(styles),
              });
            }, 250);
          }

          setTimeout(function () {
            locationsSidebarHeight();
          }, 250);

          $("body").addClass("kwall-map-processed");
        }
      });
      $(window).resize(function () {
        locationsSidebarHeight();
      });
    },
  };

  /**
   * Toogle map sidebar POI content
   *
   * @type {{attach: Drupal.behaviors.mapInfoToggle.attach}}
   */
  Drupal.behaviors.mapInfoToggle = {
    attach: function (context, settings) {
      $(document).ready(function () {
        $(".geolocation-common-map-locations")
          .once("mapInfoToggle")
          .each(function () {
            var content_toggle = $(
                ".geolocation-common-map-locations .location-title span",
                context
              ),
              map_content = $(
                ".geolocation-common-map-locations .location-content .more-info",
                context
              ),
              map_select = $(".geolocation-map-select select", context);

            $(".geolocation")
              .unbind()
              .click(function () {
                var toggle_me =
                  ".geolocation-common-map-locations .map-content-" +
                  $(this)
                    .children(".location-title")
                    .children("span")
                    .data("toggle");
                $(".location-title span").removeClass('active');
                var current_target = $(this).find(".location-title span");
                // add chevron toggle display
                content_toggle.removeClass("active").alert("removed");
                current_target.addClass("active");

                // toggle map content accordion
                if ($(toggle_me).hasClass("active")) {
                  $(map_content).each(function () {
                    $(map_content).removeClass("active").slideUp();
                  });
                } else {
                  $(map_content).each(function () {
                    $(map_content).removeClass("active").slideUp();
                  });
                  $(toggle_me).addClass("active").slideToggle();
                }

                map_select.val(current_target.data("toggle"));

                for (
                  var i = 0;
                  i < Drupal.geolocation.maps[0].mapMarkers.length;
                  i++
                ) {
                  var marker = Drupal.geolocation.maps[0].mapMarkers[i];
                  // html = $.parseHTML(marker.infoWindowContent),
                  // lat = $(this)[0].dataset.lat,
                  // long_1 = $(this)[0].dataset.lng;

                  if ($(this).find(".poi-no").text() === marker.title) {
                    google.maps.event.trigger(marker, "click");
                    return;
                  }
                }
              }); // end geolocation click event

            map_select.on("change", function () {
              var val = this.value;
              content_toggle.each(function () {
                if ($(this).data("toggle") == val) {
                  $(this).closest(".geolocation").click();
                }
              });
            });
          });
      }); //end doc ready
    },
  };
})(jQuery, Drupal);
