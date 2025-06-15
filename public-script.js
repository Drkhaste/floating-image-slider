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

        // Clear any existing interval to prevent duplicates
        clearInterval(slideInterval);
        slideInterval = setInterval(function() {
            nextSlide();
        }, floatingSliderData.slideDuration);
    }

    function nextSlide() {
        var $currentImg = $sliderImages.eq(currentSlide);
        currentSlide = (currentSlide + 1) % totalSlides;
        var $nextImg = $sliderImages.eq(currentSlide);

        if ($currentImg.length === 0 || $nextImg.length === 0) {
            // Handle case where images might be removed or not loaded correctly
            clearInterval(slideInterval); // Stop slideshow if issues
            return;
        }

        // Apply animations based on type
        switch(animationType) {
            case 'fade':
                $currentImg.fadeOut(300);
                $nextImg.fadeIn(300);
                break;

            case 'slide':
                // Initial positioning for slide animation
                $sliderImages.css({
                    'position': 'absolute',
                    'top': '0',
                    'left': '0',
                    'transform': 'translateX(0)' // Reset transform for all
                }).hide();

                // Prepare next image to slide in from right (or left, depending on direction)
                $nextImg.css({
                    'transform': 'translateX(100%)'
                }).show();

                // Animate current image out to the left and next image in from the right
                $currentImg.css({'position': 'absolute', 'left': '0'}).show().animate({
                    left: '-100%'
                }, 500, 'easeOutExpo', function() {
                    $(this).hide().css('left', '0'); // Reset current image position after it's hidden
                });

                $nextImg.css({'position': 'absolute', 'left': '0'}).show().animate({
                    transform: 'translateX(0%)'
                }, 500, 'easeOutExpo');
                break;

            case 'zoom':
                $currentImg.removeClass('active').animate({
                    width: '0%',
                    height: '0%',
                    top: '50%',
                    left: '50%',
                    opacity: 0
                }, 300, function() {
                    $(this).hide().css({ // Reset to original state after animation
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
                $currentImg.fadeOut(300);
                $nextImg.fadeIn(300);
                break;
        }
        // Update active class for current and next image (important for transitions)
        $currentImg.removeClass('active');
        $nextImg.addClass('active');
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

    // Close button functionality - now directly handles click
    $('#fs-slider-close').on('click', function() {
        $('#floating-slider-pro-container').fadeOut(300, function() {
            // Optional: clearInterval(slideInterval); if you want to stop it permanently after close
        });
    });
});