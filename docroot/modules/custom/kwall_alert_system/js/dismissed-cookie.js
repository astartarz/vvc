function alertsMovetoTop() {
    var alert_height = jQuery('#kwall-alerts-wrapper').height() + 'px';
    if(jQuery(document).innerWidth() >747) {
        jQuery('.region-top-navigation').css({'margin-top': alert_height});
        jQuery('.middle-nav-wrap').css({'margin-top':  '0px'});
    } else {
        jQuery('.middle-nav-wrap').css({'margin-top': alert_height});
        jQuery('.region-top-navigation').css({'margin-top':  '0px'});
    }
}


/* get alert node id */
function getNodeId(className) {
    if (className) {
        var elemClasses = className.split(/\s+/);
        for (i in elemClasses) {
            var c = elemClasses[i];
            if (c.length > 16 && c.substring(0, 16) === "kwall-site-alert")
                return c.substring(16);
        }
    }
    return false;
}

/*get alert level value */
function getLevel(className) {
    if (className) {
        var elemClasses = className.split(/\s+/);
        for (i in elemClasses) {
            var c = elemClasses[i];
            if (c.length > 12 && c.substring(0, 12) === "alert-level-")
                return c.substring(12);
        }
    }
    return false;
}



(function ($, Drupal) {

    Drupal.behaviors.kwallAlertSystem = {
        attach: function (context, drupalSettings) {


            if ($('#kwall-alerts .alert').length > 0) {
                $('#kwall-alerts .alert').each(function () {
                    var nid = getNodeId($(this).attr('class'));
                    if (nid) {
                        var keynum = "key" + nid;
                        var cookieName = 'Drupal.visitor.kwall_alert_system_dismissed' + nid;
                        var key = drupalSettings.kwall_alert_system.dismissedCookie[keynum];
                        // Only show the alert if dismiss button has not been clicked. The
                        // element is hidden by default in order to prevent it from momentarily
                        // flickering onscreen. We are not working with Bootstrap's 'hide' class
                        // since we don't want a dependency on Bootstrap.
                        // actually we are removing altogether so it doesn't mess with slideshow
                        if ($.cookie(cookieName) == key) {
                            $('.kwall-site-alert' + nid).remove();
                        }

                        // Set the cookie value when dismiss button is clicked.
                        if (!$(this).hasClass('dismissed-processed') && !$(this).hasClass('not-dismissible-on')) {
                            $(this).addClass('dismissed-processed').attr('data-key', key).attr('data-cookie', cookieName);
                            $(this).on('close.bs.alert', function () {
                                $.cookie($(this).attr('data-cookie'), $(this).attr('data-key'), {path: drupalSettings.path.baseUrl});
                            });
                        }
                    }
                });




                if($('body').hasClass('alerts-processed')) {
                    //megamenu fix (remove duplicates after ajax refresh)
                    jQuery('#kwall-alerts').show();
                    alertsMovetoTop();
                    // $('body').removeClass('alerts-processed');

                }
            }
        }
    }
    jQuery(window).on('load', function() {
        if(drupalSettings.views && drupalSettings.views.ajaxViews) {
            jQuery.each(drupalSettings.views.ajaxViews, function (i, view) {
                var selector = '.js-view-dom-id-' + view.view_dom_id;
                if (view.view_name == "alerts") {
                    jQuery('body').addClass('alerts-processed');
                    if($('body').hasClass('alerts-processed')) {
                        jQuery(selector).triggerHandler('RefreshView');


                    }
                }
                jQuery(selector).unbind();
            });
            $('body').delegate('.alert-close', 'click',  function(){
                var alert_height = $(this).parents('.alert').height();
                var nav_padding = parseInt(jQuery('.region-top-navigation').css('padding-top'));
                $('.region-top-navigation').css({'padding-top': nav_padding - alert_height + 'px'});

            });

        }
    });
    jQuery(window).on('resize', function() {
        alertsMovetoTop();
    });


})(jQuery, Drupal);


