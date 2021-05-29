jQuery(document).ready(function () {
  /**
   * Carousel
   */
  jQuery('.paragraph--type--image-carousel .image-carousel').slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    arrows: false,
    fade: true,
    asNavFor: '.slider-nav'
  });
  jQuery('.slider-nav').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    asNavFor: '.paragraph--type--image-carousel .image-carousel',
    dots: true,
    centerMode: true,
    focusOnSelect: true
  });
});


jQuery(document).ready(function() {
  jQuery('.paragraph--type--info-block-item .content-wrapper .link-content a').hover(function(){
      jQuery(this).parent().closest('.paragraph--type--info-block-item').find('.field-caption').addClass('active');
    },
    function(){
      jQuery(this).parent().closest('.paragraph--type--info-block-item').find('.field-caption').removeClass('active');
    });

  setTimeout(function (){
    var geoItem = jQuery('.geolocation-common-map-locations .geolocation .geolocation');
    if(geoItem.length){
      geoItem.first().trigger('click');
    }
  }, 1000);
});
