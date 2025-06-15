jQuery(document).ready(function($) {
    var currentSlide = 0;
    var totalSlides = floatingSliderData.totalSlides;
    var slideInterval;
    var animationType = floatingSliderData.animationType;
    var $sliderContainer = $('#floating-slider-pro-container');
    var $sliderImages = $sliderContainer.find('.fs-slider-image');

    // Show the slider after a delay
    setTimeout(function() {
        $sliderContainer.fadeIn(500);
        startSlideshow();
    }, floatingSliderData.delayShow);

    function startSlideshow() {
        if (totalSlides <= 1) return; // No need for slideshow if 1 or less images

        slideInterval = setInterval(function() {
            nextSlide();
        }, floatingSliderData.slideDuration);
    }

    function nextSlide() {
        var $currentImg = $sliderImages.eq(currentSlide);
        currentSlide = (currentSlide + 1) % totalSlides;
        var $nextImg = $sliderImages.eq(currentSlide);

        if ($currentImg.length === 0 || $nextImg.length === 0) return; // Ensure elements exist

        // Reset all images to hidden and not active
        $sliderImages.removeClass('active').hide();

        switch(animationType) {
            case 'fade':
                $currentImg.css('opacity', 1).fadeOut(300, function() {
                    $nextImg.css('opacity', 0).fadeIn(300).addClass('active');
                });
                break;

            case 'slide':
                // Reset positions for all images before animation
                $sliderImages.css({
                    'left': '0',
                    'transform': 'translateX(100%)'
                }).hide();

                // Current image slides out
                $currentImg.css({
                    'position': 'absolute',
                    'transform': 'translateX(0)',
                    'left': '0'
                }).show().animate({
                    'transform': 'translateX(-100%)'
                }, 500, 'easeOutExpo', function() {
                    $(this).hide();
                    // Next image slides in
                    $nextImg.css({
                        'position': 'absolute',
                        'transform': 'translateX(100%)',
                        'left': '0'
                    }).show().animate({
                        'transform': 'translateX(0)'
                    }, 500, 'easeOutExpo', function() {
                        $(this).addClass('active');
                    });
                });
                break;

            case 'zoom':
                $currentImg.removeClass('active').animate({
                    width: '0%',
                    height: '0%',
                    top: '50%',
                    left: '50%',
                    opacity: 0
                }, 300, function() {
                    $(this).hide().css({
                        width: '100%',
                        height: '100%',
                        top: '0',
                        left: '0',
                        opacity: 1
                    });
                    $nextImg.css({
                        width: '0%',
                        height: '0%',
                        top: '50%',
                        left: '50%',
                        opacity: 0
                    }).show().animate({
                        width: '100%',
                        height: '100%',
                        top: '0',
                        left: '0',
                        opacity: 1
                    }, 300).addClass('active');
                });
                break;

            default: // Fallback to fade if animationType is unknown
                $currentImg.fadeOut(300, function() {
                    $nextImg.fadeIn(300).addClass('active');
                });
                break;
        }
    }

    // Pause slideshow on hover
    $sliderContainer.hover(
        function() {
            clearInterval(slideInterval);
        },
        function() {
            startSlideshow();
        }
    );
});

// Global function for close button
function closeFloatingSlider() {
    jQuery('#floating-slider-pro-container').fadeOut(300);
}