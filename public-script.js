jQuery(document).ready(function($) {
    var currentSlide = 0;
    var totalSlides = floatingSliderData.totalSlides;
    var slideInterval;
    var animationType = floatingSliderData.animationType;
    var $sliderContainer = $('#floating-slider-pro-container');
    var $sliderImages = $sliderContainer.find('.fs-slider-image');

    // Show the slider after a delay
    setTimeout(function() {
        $sliderContainer.fadeIn(500, function() {
            // Ensure the first image is always active and visible initially
            $sliderImages.removeClass('active inactive').eq(0).addClass('active').show();
            // Only start slideshow if there's more than one slide
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

        // Ensure current and next images exist
        if ($currentImg.length === 0 || $nextImg.length === 0) {
            clearInterval(slideInterval);
            console.error('Slider error: Image not found for transition.');
            return;
        }

        switch(animationType) {
            case 'fade':
                $currentImg.fadeOut(300, function() {
                    $(this).removeClass('active inactive'); // Remove classes after fadeOut
                });
                $nextImg.fadeIn(300, function() {
                    $(this).addClass('active').removeClass('inactive'); // Add active after fadeIn
                });
                break;

            case 'slide':
                // Hide all and reset positions first
                $sliderImages.removeClass('active').css({'left': '0', 'top': '0', 'transform': 'translateX(0)'}).hide();

                // Current image moves out to the left
                $currentImg.css({'position': 'absolute', 'display': 'block'})
                    .animate({ left: '-100%' }, 500, 'swing', function() {
                        $(this).hide().css('left', '0'); // Reset position after hide
                    });

                // Next image slides in from the right
                $nextImg.css({'position': 'absolute', 'display': 'block', 'left': '100%'})
                    .animate({ left: '0%' }, 500, 'swing', function() {
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
                    $(this).removeClass('active inactive').hide().css({ // Reset to original state
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
                        $(this).addClass('active').removeClass('inactive');
                    });
                });
                break;

            default: // Fallback to fade
                $currentImg.fadeOut(300);
                $nextImg.fadeIn(300);
                break;
        }
    }

    // Pause slideshow on hover
    $sliderContainer.hover(
        function() {
            clearInterval(slideInterval);
        },
        function() {
            if (totalSlides > 1) { // Only restart if there's more than one slide
                startSlideshow();
            }
        }
    );

    // Close button functionality
    $('#fs-slider-close').on('click', function() {
        $('#floating-slider-pro-container').fadeOut(300, function() {
            clearInterval(slideInterval); // Stop slideshow when closed
        });
    });

    // Dynamic resizing for mobile
    function applyMobileStyles() {
        if (window.innerWidth <= 767) {
            $('#floating-slider-pro-container').css({
                'width': floatingSliderData.mobileWidth + 'px',
                'height': floatingSliderData.mobileHeight + 'px'
            });
        } else {
            // Revert to desktop sizes if screen gets larger
            $('#floating-slider-pro-container').css({
                'width': 'auto', // CSS will handle based on settings
                'height': 'auto'
            });
        }
    }

    // Apply styles on load and resize
    applyMobileStyles();
    $(window).on('resize', applyMobileStyles);
});