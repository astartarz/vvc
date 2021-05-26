//Initialize Flixslider
$(window).load(function() {
  $('#flexslider-1.flexslider').flexslider({
        animation: 'fade',
        animationLoop: true,
        controlNav: true
  });
  $('#flexslider-2.flexslider').flexslider({
        animation: 'fade',
        animationLoop: true,
        controlNav: true
  });
  $('#flexslider-3.flexslider').flexslider({
        animation: 'fade',
        animationLoop: true,
        controlNav: true,
        controlsContainer:"#flexslider-3 .field-type-image",
  });
  
  //Initialize Slick Slider
  var $object = $('.slick-carousel-alert');
  if($object.length) {
    // there's at least one matching element
    $('.slick-carousel-alert').slick({
  speed: 300,
  slidesToShow: 1,
  slidesToScroll: 1,
  centerMode: true,
  centerPadding: '0px',
	infinite: true,
	prevArrow: $('.slick-prev'),
  nextArrow: $('.slick-next'),
});

  }

});

//equal height

equalheight = function(container){

var currentTallest = 0,
     currentRowStart = 0,
     rowDivs = new Array(),
     $el,
     topPosition = 0;
 $(container).each(function() {

   $el = $(this);
   $($el).height('auto')
   topPostion = $el.position().top;

   if (currentRowStart != topPostion) {
     for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
       rowDivs[currentDiv].height(currentTallest);
     }
     rowDivs.length = 0; // empty the array
     currentRowStart = topPostion;
     currentTallest = $el.height();
     rowDivs.push($el);
   } else {
     rowDivs.push($el);
     currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
  }
   for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
     rowDivs[currentDiv].height(currentTallest);
   }
 });
}



$(window).load(function() {
  equalheight('.equalHeight-parent .equalHeight-child');
});


$(window).resize(function(){
  equalheight('.equalHeight-parent .equalHeight-child');
});

//Color popup click event
$(document).ready(
function(){
    $("div.mobile-tap").click(function (e) {
        e.preventDefault();
        $("div.color-popup").css({"opacity":"1","right":"1rem","top":"-2rem"});
    });

});
