jQuery(document).ready(function($) {
    var currentSlide = 0;
    var totalSlides = floatingSliderData.totalSlides;
    var slideInterval;
    var animationType = floatingSliderData.animationType;
    var $sliderContainer = $('#floating-slider-pro-container');
    var $sliderImages = $sliderContainer.find('.fs-slider-image');

    // Only proceed if the slider container and images exist
    if (!$sliderContainer.length || !$sliderImages.length) {
        return;
    }

    // Initialize display state for all images: only the first one is 'active'
    $sliderImages.removeClass('active').hide(); // Hide all initially
    $sliderImages.eq(0).addClass('active').show(); // Show only the first one

    // Show the slider after a delay
    setTimeout(function() {
        $sliderContainer.fadeIn(500, function() {
            if (totalSlides > 1) {
                startSlideshow();
            }
        });
    }, floatingSliderData.delayShow);

    // Click handler for images to open link
    $sliderImages.on('click', function() {
        var link = $(this).data('link');
        if (link) {
            window.open(link, '_blank');
        }
    });

    function startSlideshow() {
        if (totalSlides <= 1) return;

        clearInterval(slideInterval);
        slideInterval = setInterval(function() {
            nextSlide();
        }, floatingSliderData.slideDuration);
    }

    function nextSlide() {
        var $currentImg = $sliderImages.eq(currentSlide);
        currentSlide = (currentSlide + 1) % totalSlides;
        var $nextImg = $sliderImages.eq(currentSlide);

        if (!$currentImg.length || !$nextImg.length) {
            console.error('Slider error: Cannot find current or next image for transition.');
            clearInterval(slideInterval);
            return;
        }

        switch(animationType) {
            case 'fade':
                $currentImg.fadeOut(300, function() {
                    $(this).removeClass('active'); // Remove active after fadeOut
                });
                $nextImg.fadeIn(300, function() {
                    $(this).addClass('active'); // Add active after fadeIn
                });
                break;

            case 'slide':
                // Set initial positions for current and next image
                $currentImg.css({ 'position': 'absolute', 'left': '0%', 'top': '0', 'display': 'block' });
                $nextImg.css({ 'position': 'absolute', 'left': '100%', 'top': '0', 'display': 'block' });

                // Animate current image out
                $currentImg.animate({ left: '-100%' }, 500, 'swing', function() {
                    $(this).removeClass('active').hide().css('left', '0%'); // Reset for next cycle
                });

                // Animate next image in
                $nextImg.animate({ left: '0%' }, 500, 'swing', function() {
                    $(this).addClass('active');
                });
                break;

            case 'zoom':
                $currentImg.animate({
                    width: '0%',
                    height: '0%',
                    top: '50%',
                    left: '50%',
                    opacity: 0
                }, 300, function() {
                    $(this).removeClass('active').hide().css({ // Reset to original state
                        width: '100%',
                        height: '100%',
                        top: '0%',
                        left: '0%',
                        opacity: 1
                    });
                    $nextImg.css({
                        width: '0%',
                        height: '0%',
                        top: '50%',
                        left: '50%',
                        opacity: 0,
                        display: 'block' // Show to start animation
                    }).animate({
                        width: '100%',
                        height: '100%',
                        top: '0%',
                        left: '0%',
                        opacity: 1
                    }, 300, function() {
                        $(this).addClass('active');
                    });
                });
                break;

            default: // Fallback to fade
                $currentImg.fadeOut(300, function() {
                    $(this).removeClass('active');
                });
                $nextImg.fadeIn(300, function() {
                    $(this).addClass('active');
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
            if (totalSlides > 1) {
                startSlideshow();
            }
        }
    );

    // Close button functionality
    $('#fs-slider-close').on('click', function() {
        $sliderContainer.fadeOut(300, function() {
            clearInterval(slideInterval); // Stop slideshow when closed
        });
    });
});