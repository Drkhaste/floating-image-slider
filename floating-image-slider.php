<?php
/**
 * Plugin Name: Floating Image Slider
 * Description: A customizable floating image slider widget that can be displayed on any page with full admin control
 * Version: 2.0.13
 * Author: BTC
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FloatingImageSlider {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_slider'));
        add_action('wp_ajax_save_slider_settings', array($this, 'save_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Initialize plugin (can be used for custom post types, taxonomies, etc.)
    }

    public function activate() {
        // Set default options only if they don't already exist
        if (get_option('floating_slider_options') === false) {
            update_option('floating_slider_options', $this->get_default_options());
        }
    }

    /**
     * Returns the default plugin options.
     * These values are set when the plugin is activated for the first time
     * or when options are missing.
     * @return array
     */
    private function get_default_options() {
        return array(
            'enabled' => true,
            'display_pages' => 'all',
            'specific_pages' => array(),
            'width' => 320,
            'height' => 220,
            'mobile_width' => 280,
            'mobile_height' => 200,
            'position_x' => 'right',
            'position_y' => 'bottom',
            'offset_x' => 25,
            'offset_y' => 25,
            'mobile_offset_x' => 10,
            'mobile_offset_y' => 10,
            'images' => array(),
            'close_button_enabled' => true,
            'close_button_size' => 28,
            'close_button_position' => 'top-right',
            'close_button_color' => '#ffffff',
            'close_button_bg' => '#e74c3c',
            'slide_animation' => 'fade',
            'slide_duration' => 4500, // milliseconds
            'animation_speed' => 700, // milliseconds
            'delay_before_first_slide' => 2000, // milliseconds for initial fade-in after page load
            'border_theme' => 'shadow',
            'border_radius' => 15,
            'border_color' => '#2980b9',
            'border_width' => 4,
            'shadow_blur' => 20,
            'shadow_color' => 'rgba(0,0,0,0.5)',
            'gradient_start' => '#4CAF50',
            'gradient_end' => '#2196F3',
            'overlay_text_enabled' => true,
            'overlay_font_size' => 16,
            'mobile_overlay_font_size' => 14,
            'overlay_text_color' => '#ffffff',
            'overlay_bg_enabled' => true,
            'overlay_bg_color' => 'rgba(0,0,0,0.7)',
            'overlay_text_position' => 'bottom',
            'overlay_text_alignment' => 'center',
            'overlay_padding' => 10,
            'overlay_border_radius' => 8,
            'image_fit' => 'cover',
        );
    }

    public function admin_menu() {
        add_options_page(
            'Floating Image Slider',
            'Floating Slider',
            'manage_options',
            'floating-slider',
            array($this, 'admin_page')
        );
    }

    public function enqueue_scripts() {
        if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
            wp_enqueue_script( 'jquery' );
        }
        
        if (!$this->should_display_slider() && !is_admin()) {
            return;
        }

        if (!is_admin()) {
            add_action('wp_head', array($this, 'inline_styles_scripts'));
        }
    }

    public function inline_styles_scripts() {
        $options = get_option('floating_slider_options', $this->get_default_options());
        ?>
        <style>
        #floating-slider {
            position: fixed;
            z-index: 9999;
            display: none; /* Initially hidden, shown by JS fadeIn */
            overflow: hidden;
            <?php echo $this->get_position_css($options, 'desktop'); ?>
            <?php echo $this->get_border_theme_css($options); ?>
            width: <?php echo esc_attr($options['width']); ?>px;
            height: <?php echo esc_attr($options['height']); ?>px;
            border-radius: <?php echo esc_attr($options['border_radius']); ?>px;
            box-sizing: border-box;
            background-color: #f0f0f0; /* Fallback for transparency */
            transition: all 0.5s ease;
        }

        #floating-slider .slide-item {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            opacity: 0;
            z-index: 1; /* Default z-index for all slides */
            transition: opacity <?php echo esc_attr($options['animation_speed']); ?>ms ease-in-out;
        }

        #floating-slider .slide-item.active {
            opacity: 1;
            z-index: 2; /* Active slide is above others */
            /* Initial state for active slide for slide animation (no transform applied on load for smooth fade-in) */
            <?php if ($options['slide_animation'] === 'slide'): ?>
            transform: translateX(0);
            <?php endif; ?>
        }

        #floating-slider .slide-item a {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            pointer-events: auto; /* Ensure the link is clickable */
        }

        #floating-slider img {
            width: 100%;
            height: 100%;
            object-fit: <?php echo esc_attr($options['image_fit']); ?>;
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        #floating-slider .overlay-text {
            position: absolute;
            width: 100%;
            box-sizing: border-box;
            z-index: 10;
            display: flex;
            padding: <?php echo esc_attr($options['overlay_padding']); ?>px;
            border-radius: <?php echo esc_attr($options['overlay_border_radius']); ?>px;
            pointer-events: none; /* Crucial: Clicks pass through to the <a> tag below */
            
            <?php 
            if ($options['overlay_text_position'] === 'top'): ?>
            top: 0; left: 0; align-items: flex-start;
            <?php elseif ($options['overlay_text_position'] === 'center'): ?>
            top: 0; left: 0; align-items: center; justify-content: center; height: 100%;
            <?php else: /* bottom */ ?>
            bottom: 0; left: 0; align-items: flex-end;
            <?php endif; ?>
        }

        #floating-slider .overlay-text span {
            background-color: <?php echo ($options['overlay_bg_enabled']) ? esc_attr($options['overlay_bg_color']) : 'transparent'; ?>;
            color: <?php echo esc_attr($options['overlay_text_color']); ?>;
            font-size: <?php echo esc_attr($options['overlay_font_size']); ?>px;
            text-align: <?php echo esc_attr($options['overlay_text_alignment']); ?>;
            padding: <?php echo esc_attr($options['overlay_padding']); ?>px;
            border-radius: <?php echo esc_attr($options['overlay_border_radius']); ?>px;
            max-width: 90%;
            box-sizing: border-box;
            line-height: 1.4;
            display: inline-block;
            <?php if ($options['overlay_text_alignment'] === 'left'): ?>
            margin-right: auto;
            <?php elseif ($options['overlay_text_alignment'] === 'right'): ?>
            margin-left: auto;
            <?php else: /* center */ ?>
            margin-left: auto; margin-right: auto;
            <?php endif; ?>
            word-wrap: break-word;
        }

        #floating-slider .close-btn {
            position: absolute;
            <?php echo $this->get_close_button_position_css($options); ?>
            width: <?php echo esc_attr($options['close_button_size']); ?>px;
            height: <?php echo esc_attr($options['close_button_size']); ?>px;
            background: <?php echo esc_attr($options['close_button_bg']); ?>;
            color: <?php echo esc_attr($options['close_button_color']); ?>;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: <?php echo esc_attr(round($options['close_button_size'] * 0.6)); ?>px;
            line-height: 1;
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        #floating-slider .close-btn:hover {
            transform: scale(1.1);
            opacity: 0.8;
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }

        <?php if ($options['slide_animation'] === 'slide'): ?>
        /* Initial state for slides that are not active */
        #floating-slider .slide-item:not(.active) {
            opacity: 0;
            transform: translateX(100%); /* Or -100% depending on direction */
            transition: opacity <?php echo esc_attr($options['animation_speed']); ?>ms ease-in-out, transform <?php echo esc_attr($options['animation_speed']); ?>ms ease-in-out;
        }

        /* Specific classes for slide transitions (applied by JS) */
        #floating-slider .slide-item.slide-out-left {
            transform: translateX(-100%) !important; /* Use !important to override .active if needed during transition */
            opacity: 0 !important;
            z-index: 1 !important;
        }
        #floating-slider .slide-item.slide-out-right {
            transform: translateX(100%) !important;
            opacity: 0 !important;
            z-index: 1 !important;
        }
        /* Applied once the slide should animate in */
        #floating-slider .slide-item.slide-in-active {
            transform: translateX(0) !important;
            opacity: 1 !important;
            z-index: 2 !important; /* Ensure it's on top when sliding in */
        }
        <?php endif; ?>

        @media (max-width: 768px) {
            #floating-slider {
                width: <?php echo esc_attr($options['mobile_width']); ?>px !important;
                height: <?php echo esc_attr($options['mobile_height']); ?>px !important;
                <?php echo $this->get_position_css($options, 'mobile'); ?>
            }
            #floating-slider .overlay-text span {
                font-size: <?php echo esc_attr($options['mobile_overlay_font_size']); ?>px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var slider = {
                container: null,
                slideItems: null,
                currentIndex: 0,
                interval: null,
                animationSpeed: <?php echo esc_attr($options['animation_speed']); ?>,
                slideDuration: <?php echo esc_attr($options['slide_duration']); ?>,
                slideAnimation: '<?php echo esc_attr($options['slide_animation']); ?>',
                delayBeforeFirstSlide: <?php echo esc_attr($options['delay_before_first_slide']); ?>,
                
                init: function() {
                    this.container = $('#floating-slider');
                    this.slideItems = this.container.find('.slide-item');

                    // If no slides, hide container and stop.
                    if (this.slideItems.length === 0) {
                         this.container.hide();
                         return;
                    }
                    
                    // If only one slide, just show it and exit. No need for slideshow.
                    if (this.slideItems.length === 1) {
                         this.slideItems.eq(0)
                             .css({opacity: 1, transform: 'translateX(0)', 'z-index': 2}) // Set z-index for single slide
                             .addClass('active');
                         setTimeout(function() {
                             slider.show();
                         }, this.delayBeforeFirstSlide);
                         return;
                    }

                    // Pre-set the first slide as active and visible without transition to avoid flicker
                    // and ensure it's on top initially.
                    this.slideItems.eq(0).addClass('active').css({opacity: 1, 'z-index': 2}); // Set z-index for active

                    // Set transitions for all items after initial load, so they animate from now on
                    var self = this;
                    this.slideItems.each(function() {
                        $(this).css('transition', 'opacity ' + self.animationSpeed + 'ms ease-in-out, transform ' + self.animationSpeed + 'ms ease-in-out, z-index 0s'); // Add z-index to transition for immediate change
                    });

                    // Show the slider after a delay and start slideshow
                    setTimeout(function() {
                        slider.show();
                        slider.startSlideshow();
                    }, this.delayBeforeFirstSlide);

                    // Attach close button event
                    this.container.find('.close-btn').on('click', function() {
                        slider.hide();
                    });
                },

                show: function() {
                    this.container.fadeIn(500);
                },

                hide: function() {
                    this.container.fadeOut(500);
                    this.stopSlideshow();
                },

                showSlide: function(index) {
                    if (this.slideItems.length <= 1 || index === this.currentIndex) {
                        return;
                    }

                    var currentSlide = this.slideItems.eq(this.currentIndex);
                    var nextSlide = this.slideItems.eq(index);

                    // Fade Animation
                    if (this.slideAnimation === 'fade') {
                        currentSlide.removeClass('active').css({'z-index': 1, 'opacity': 0}); // Fade out current, set lower z-index
                        nextSlide.addClass('active').css({'z-index': 2, 'opacity': 1});       // Fade in next, set higher z-index
                    }
                    // Slide Animation
                    else if (this.slideAnimation === 'slide') {
                        var isForward = (index > this.currentIndex || (index === 0 && this.currentIndex === this.slideItems.length - 1));
                        
                        // Set current slide to a lower z-index for smooth transition out
                        currentSlide.css('z-index', 1);

                        // Prepare the next slide for entry
                        nextSlide.removeClass('active slide-in-left-initial slide-in-right-initial slide-in-active'); // Clean slate
                        nextSlide.css({
                            opacity: 0,
                            transform: isForward ? 'translateX(100%)' : 'translateX(-100%)',
                            'z-index': 2 // New slide comes on top
                        });

                        // Force reflow to ensure initial state is applied before transition
                        nextSlide[0].offsetWidth; 

                        // Animate current slide out
                        currentSlide.removeClass('active').addClass(isForward ? 'slide-out-left' : 'slide-out-right');

                        // Animate next slide in
                        // Use a short delay for smooth overlapping slide effect
                        setTimeout(function() {
                            nextSlide.addClass('active slide-in-active');
                            // Ensure the transform and opacity are set for animation
                            nextSlide.css({
                                opacity: 1,
                                transform: 'translateX(0%)'
                            });
                        }, 50); // Small delay to ensure slide-out starts before slide-in

                        // Clean up current slide after its transition is complete
                        setTimeout(function() {
                            currentSlide.removeClass('slide-out-left slide-out-right').css({transform: '', opacity: 0, 'z-index': 1});
                        }, this.animationSpeed);
                    }

                    this.currentIndex = index;
                },

                nextImage: function() {
                    var nextIndex = (this.currentIndex + 1) % this.slideItems.length;
                    this.showSlide(nextIndex);
                },

                startSlideshow: function() {
                    if (this.slideItems.length > 1) {
                        this.stopSlideshow(); // Clear any existing interval
                        this.interval = setInterval(function() {
                            slider.nextImage();
                        }, this.slideDuration);
                    }
                },

                stopSlideshow: function() {
                    if (this.interval) {
                        clearInterval(this.interval);
                    }
                }
            };

            slider.init();
        });
        </script>
        <?php
    }

    /**
     * Generates CSS for slider positioning (desktop and mobile).
     * @param array $options Plugin options.
     * @param string $device 'desktop' or 'mobile'.
     * @return string CSS rules.
     */
    private function get_position_css($options, $device = 'desktop') {
        $css = '';
        $offsetX = ($device === 'mobile') ? $options['mobile_offset_x'] : $options['offset_x'];
        $offsetY = ($device === 'mobile') ? $options['mobile_offset_y'] : $options['offset_y'];

        if ($options['position_x'] === 'left') {
            $css .= 'left: ' . esc_attr($offsetX) . 'px; ';
            $css .= 'right: auto; ';
        } else {
            $css .= 'right: ' . esc_attr($offsetX) . 'px; ';
            $css .= 'left: auto; ';
        }

        if ($options['position_y'] === 'top') {
            $css .= 'top: ' . esc_attr($offsetY) . 'px; ';
            $css .= 'bottom: auto; ';
        } else {
            $css .= 'bottom: ' . esc_attr($offsetY) . 'px; ';
            $css .= 'top: auto; ';
        }

        return $css;
    }

    /**
     * Generates CSS for close button positioning.
     * Adjusted to keep button inside slider and use more precise offsets.
     * @param array $options Plugin options.
     * @return string CSS rules.
     */
    private function get_close_button_position_css($options) {
        $positions = explode('-', $options['close_button_position']);
        $css = '';
        $offset = 5; // Fixed small offset for positioning button

        if ($positions[0] === 'top') {
            $css .= 'top: ' . esc_attr($offset) . 'px; ';
        } else { // bottom
            $css .= 'bottom: ' . esc_attr($offset) . 'px; ';
        }

        if ($positions[1] === 'left') {
            $css .= 'left: ' . esc_attr($offset) . 'px; ';
        } else { // right
            $css .= 'right: ' . esc_attr($offset) . 'px; ';
        }

        return $css;
    }

    /**
     * Generates CSS based on the selected border theme.
     * @param array $options Plugin options.
     * @return string CSS rules.
     */
    private function get_border_theme_css($options) {
        $css = '';
        $borderRadius = esc_attr($options['border_radius']);
        $borderWidth = esc_attr($options['border_width']);
        $borderColor = esc_attr($options['border_color']);
        $shadowBlur = esc_attr($options['shadow_blur']);
        $shadowColor = esc_attr($options['shadow_color']);
        $gradientStart = esc_attr($options['gradient_start']);
        $gradientEnd = esc_attr($options['gradient_end']);

        // Apply border-radius to all themes by default as a base style
        $css .= "border-radius: {$borderRadius}px; ";

        switch ($options['border_theme']) {
            case 'simple':
                $css .= "border: {$borderWidth}px solid {$borderColor}; ";
                $css .= "box-shadow: 0 4px 10px rgba(0,0,0,0.1); "; // Slightly nicer shadow
                break;

            case 'shadow':
                $css .= "border: {$borderWidth}px solid {$borderColor}; ";
                $css .= "box-shadow: 0 0 {$shadowBlur}px {$shadowColor}; "; // Enhanced shadow
                break;

            case 'gradient':
                // Using border-image for better gradient border appearance
                $css .= "border: {$borderWidth}px solid transparent; ";
                $css .= "border-image: linear-gradient(to right, {$gradientStart}, {$gradientEnd}) 1; "; // '1' slices the image and uses it as border
                $css .= "box-shadow: 0 4px 15px rgba(0,0,0,0.25); "; // Add a subtle shadow
                break;

            case 'fancy-border':
                $css .= "border: {$borderWidth}px solid {$borderColor}; ";
                $css .= "border-style: double; "; // Double border
                $css .= "box-shadow: 0 0 " . ($shadowBlur / 2) . "px {$shadowColor}, inset 0 0 8px rgba(0,0,0,0.1); "; // Inner and outer shadow
                $css .= "background-color: #fff; ";
                break;

            case 'animated':
                $css .= "border: {$borderWidth}px solid {$borderColor}; ";
                $css .= "animation: floatingBorderPulse 2s infinite alternate, floatingShadowPulse 3s infinite ease-in-out; ";
                // Initial shadow for when animation hasn't started
                $css .= "box-shadow: 0 0 {$shadowBlur}px {$shadowColor}; "; 
                $css .= "transition: border-color 0.5s ease, box-shadow 0.5s ease; "; // Smooth transitions for non-animated properties
                break;
        }

        return $css;
    }

    /**
     * Renders the floating slider HTML on the frontend.
     */
    public function display_slider() {
        if (!$this->should_display_slider()) {
            return;
        }

        $options = get_option('floating_slider_options', $this->get_default_options());

        if (empty($options['images'])) {
            return;
        }
        ?>
        <div id="floating-slider">
            <?php if ($options['close_button_enabled']): ?>
                <button class="close-btn">&times;</button>
            <?php endif; ?>

            <?php foreach ($options['images'] as $index => $image):
                $image_url = isset($image['url']) ? esc_url($image['url']) : '';
                $image_link = isset($image['link']) ? esc_url($image['link']) : '';
                $overlay_text = isset($image['overlay_text']) ? wp_kses_post($image['overlay_text']) : '';
            ?>
                <div class="slide-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                    <a href="<?php echo !empty($image_link) ? $image_link : '#'; ?>" 
                       <?php echo !empty($image_link) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> 
                       style="cursor: <?php echo !empty($image_link) ? 'pointer' : 'default'; ?>;">
                        <img src="<?php echo $image_url; ?>" alt="">
                        <?php if ($options['overlay_text_enabled'] && !empty($overlay_text)): ?>
                            <div class="overlay-text"><span><?php echo $overlay_text; ?></span></div>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($options['border_theme'] === 'animated'): ?>
        <style>
        @keyframes floatingBorderPulse {
            0% { border-color: <?php echo esc_attr($options['border_color']); ?>; }
            50% { border-color: <?php echo esc_attr($options['gradient_start']); ?>; }
            100% { border-color: <?php echo esc_attr($options['border_color']); ?>; }
        }
        @keyframes floatingShadowPulse {
            0% { box-shadow: 0 0 5px <?php echo esc_attr($options['shadow_color']); ?>; }
            50% { box-shadow: 0 0 25px <?php echo esc_attr($options['gradient_end']); ?>; }
            100% { box-shadow: 0 0 5px <?php echo esc_attr($options['shadow_color']); ?>; }
        }
        </style>
        <?php endif;
    }

    /**
     * Determines whether the slider should be displayed on the current page.
     * @return bool True if slider should be displayed, false otherwise.
     */
    private function should_display_slider() {
        $options = get_option('floating_slider_options', $this->get_default_options());

        if (!isset($options['enabled']) || !$options['enabled']) {
            return false;
        }

        if (!isset($options['display_pages']) || $options['display_pages'] === 'all') {
            return true;
        } elseif ($options['display_pages'] === 'specific' && !empty($options['specific_pages'])) {
            $current_page_id = get_the_ID();
            return in_array($current_page_id, (array)$options['specific_pages']);
        }

        return false;
    }

    /**
     * Renders the admin settings page with all settings in one tab.
     */
    public function admin_page() {
        $options = get_option('floating_slider_options', $this->get_default_options());
        $pages = get_pages();

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        ?>
        <div class="wrap">
            <h1>Floating Image Slider Settings</h1>

            <form id="floating-slider-settings-form" method="post" action="">
                <?php wp_nonce_field('floating_slider_settings_action', 'floating_slider_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_slider_settings">

                <div class="settings-section">
                    <h2>General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Slider</th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="enabled" value="1" <?php checked(isset($options['enabled']) ? $options['enabled'] : false); ?>>
                                    <span class="slider-toggle"></span>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Display Pages</th>
                            <td>
                                <select name="display_pages">
                                    <option value="all" <?php selected($options['display_pages'], 'all'); ?>>All Pages</option>
                                    <option value="specific" <?php selected($options['display_pages'], 'specific'); ?>>Specific Pages</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="specific-pages-row" style="<?php echo ($options['display_pages'] === 'specific') ? '' : 'display: none;'; ?>">
                            <th scope="row">Select Specific Pages</th>
                            <td>
                                <select name="specific_pages[]" multiple style="width: 300px; height: 150px;">
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>"
                                                <?php echo in_array($page->ID, (array)$options['specific_pages']) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Slider Dimensions (Desktop)</th>
                            <td>
                                <label>Width:
                                    <input type="range" name="width" min="100" max="800" value="<?php echo esc_attr($options['width']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['width']); ?></output> px
                                    <input type="number" name="width_text" min="100" max="800" value="<?php echo esc_attr($options['width']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Height:
                                    <input type="range" name="height" min="100" max="600" value="<?php echo esc_attr($options['height']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['height']); ?></output> px
                                    <input type="number" name="height_text" min="100" max="600" value="<?php echo esc_attr($options['height']); ?>"
                                           class="number-input">
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Slider Dimensions (Mobile)</th>
                            <td>
                                <label>Width:
                                    <input type="range" name="mobile_width" min="100" max="400" value="<?php echo esc_attr($options['mobile_width']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['mobile_width']); ?></output> px
                                    <input type="number" name="mobile_width_text" min="100" max="400" value="<?php echo esc_attr($options['mobile_width']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Height:
                                    <input type="range" name="mobile_height" min="80" max="300" value="<?php echo esc_attr($options['mobile_height']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['mobile_height']); ?></output> px
                                    <input type="number" name="mobile_height_text" min="80" max="300" value="<?php echo esc_attr($options['mobile_height']); ?>"
                                           class="number-input">
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Position (Desktop)</th>
                            <td>
                                <label>Horizontal:
                                    <select name="position_x">
                                        <option value="left" <?php selected($options['position_x'], 'left'); ?>>Left</option>
                                        <option value="right" <?php selected($options['position_x'], 'right'); ?>>Right</option>
                                    </select>
                                </label><br>

                                <label>Vertical:
                                    <select name="position_y">
                                        <option value="top" <?php selected($options['position_y'], 'top'); ?>>Top</option>
                                        <option value="bottom" <?php selected($options['position_y'], 'bottom'); ?>>Bottom</option>
                                    </select>
                                </label><br>

                                <label>X Offset:
                                    <input type="range" name="offset_x" min="0" max="200" value="<?php echo esc_attr($options['offset_x']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['offset_x']); ?></output> px
                                    <input type="number" name="offset_x_text" min="0" max="200" value="<?php echo esc_attr($options['offset_x']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Y Offset:
                                    <input type="range" name="offset_y" min="0" max="200" value="<?php echo esc_attr($options['offset_y']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['offset_y']); ?></output> px
                                    <input type="number" name="offset_y_text" min="0" max="200" value="<?php echo esc_attr($options['offset_y']); ?>"
                                           class="number-input">
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Position (Mobile)</th>
                            <td>
                                <label>X Offset:
                                    <input type="range" name="mobile_offset_x" min="0" max="100" value="<?php echo esc_attr($options['mobile_offset_x']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['mobile_offset_x']); ?></output> px
                                    <input type="number" name="mobile_offset_x_text" min="0" max="100" value="<?php echo esc_attr($options['mobile_offset_x']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Y Offset:
                                    <input type="range" name="mobile_offset_y" min="0" max="100" value="<?php echo esc_attr($options['mobile_offset_y']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['mobile_offset_y']); ?></output> px
                                    <input type="number" name="mobile_offset_y_text" min="0" max="100" value="<?php echo esc_attr($options['mobile_offset_y']); ?>"
                                           class="number-input">
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Close Button</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="close_button_enabled" value="1" <?php checked(isset($options['close_button_enabled']) ? $options['close_button_enabled'] : false); ?>>
                                    Enable Close Button
                                </label><br>

                                <label>Size:
                                    <input type="range" name="close_button_size" min="15" max="50" value="<?php echo esc_attr($options['close_button_size']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['close_button_size']); ?></output> px
                                    <input type="number" name="close_button_size_text" min="15" max="50" value="<?php echo esc_attr($options['close_button_size']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Position:
                                    <select name="close_button_position">
                                        <option value="top-left" <?php selected($options['close_button_position'], 'top-left'); ?>>Top Left</option>
                                        <option value="top-right" <?php selected($options['close_button_position'], 'top-right'); ?>>Top Right</option>
                                        <option value="bottom-left" <?php selected($options['close_button_position'], 'bottom-left'); ?>>Bottom Left</option>
                                        <option value="bottom-right" <?php selected($options['close_button_position'], 'bottom-right'); ?>>Bottom Right</option>
                                    </select>
                                </label><br>

                                <label>Button Text Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="close_button_color" value="<?php echo esc_attr($options['close_button_color']); ?>"></label><br>
                                <label>Button Background Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="close_button_bg" value="<?php echo esc_attr($options['close_button_bg']); ?>"></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="settings-section">
                    <h2>Manage Images</h2>
                    <div class="image-management-section">
                        <h3>Add New Image</h3>
                        <div class="add-image-controls">
                            <label for="new_image_url">Image URL:</label>
                            <input type="url" id="new_image_url" placeholder="Enter image URL or select from media" style="width: calc(100% - 180px);">
                            <button type="button" id="select-image-from-media" class="button">Select from Media Library</button>
                            <br><br>

                            <label for="new_image_link">Link URL (Optional):</label>
                            <input type="url" id="new_image_link" placeholder="e.g., https://example.com/product">
                            <br>

                            <label for="new_overlay_text">Text to display on image (Optional):</label>
                            <textarea id="new_overlay_text" rows="3" placeholder="Enter text to display over the image"></textarea>
                            <br>

                            <button type="button" id="add-new-image" class="button button-primary">Add Image to Slider</button>
                        </div>

                        <h3>Current Slider Images (Drag to reorder)</h3>
                        <ul id="current-images-list">
                            <?php if (!empty($options['images'])): ?>
                                <?php foreach ($options['images'] as $index => $image):
                                    $image_url = isset($image['url']) ? esc_attr($image['url']) : '';
                                    $image_link = isset($image['link']) ? esc_attr($image['link']) : '';
                                    $overlay_text = isset($image['overlay_text']) ? esc_textarea($image['overlay_text']) : '';
                                ?>
                                    <li class="image-item" data-index="<?php echo $index; ?>">
                                        <img src="<?php echo $image_url; ?>">
                                        <div class="image-item-details">
                                            <label>Image URL: <input type="url" name="images[<?php echo $index; ?>][url]" value="<?php echo $image_url; ?>" readonly></label>
                                            <label>Link URL: <input type="url" name="images[<?php echo $index; ?>][link]" value="<?php echo $image_link; ?>"></label>
                                            <label>Overlay Text: <textarea name="images[<?php echo $index; ?>][overlay_text]" rows="2"><?php echo $overlay_text; ?></textarea></label>
                                        </div>
                                        <button type="button" class="button remove-image">Remove</button>
                                        <input type="hidden" name="images[<?php echo $index; ?>][order]" value="<?php echo $index; ?>">
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="settings-section">
                    <h2>Design & Animation</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Animation Settings</th>
                            <td>
                                <label>Animation Type:
                                    <select name="slide_animation">
                                        <option value="fade" <?php selected($options['slide_animation'], 'fade'); ?>>Fade</option>
                                        <option value="slide" <?php selected($options['slide_animation'], 'slide'); ?>>Slide</option>
                                    </select>
                                </label><br>

                                <label>Slide Duration:
                                    <input type="range" name="slide_duration" min="1000" max="10000" step="500" value="<?php echo esc_attr($options['slide_duration']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['slide_duration']); ?></output> ms
                                    <input type="number" name="slide_duration_text" min="1000" max="10000" step="500" value="<?php echo esc_attr($options['slide_duration']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Animation Speed:
                                    <input type="range" name="animation_speed" min="200" max="2000" step="100" value="<?php echo esc_attr($options['animation_speed']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['animation_speed']); ?></output> ms
                                    <input type="number" name="animation_speed_text" min="200" max="2000" step="100" value="<?php echo esc_attr($options['animation_speed']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Delay Before First Slide Appears:
                                    <input type="range" name="delay_before_first_slide_range" min="0" max="10" step="0.5" value="<?php echo esc_attr($options['delay_before_first_slide'] / 1000); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['delay_before_first_slide'] / 1000); ?></output> seconds
                                    <input type="number" name="delay_before_first_slide_text" min="0" max="10" step="0.5" value="<?php echo esc_attr($options['delay_before_first_slide'] / 1000); ?>"
                                           class="number-input">
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Image Fit</th>
                            <td>
                                <label>How images fill the slider:
                                    <select name="image_fit">
                                        <option value="cover" <?php selected($options['image_fit'], 'cover'); ?>>Cover (Crop if needed)</option>
                                        <option value="contain" <?php selected($options['image_fit'], 'contain'); ?>>Contain (Show full image)</option>
                                        <option value="fill" <?php selected($options['image_fit'], 'fill'); ?>>Fill (Stretch to fit)</option>
                                        <option value="none" <?php selected($options['image_fit'], 'none'); ?>>None (Original size, crop if needed)</option>
                                        <option value="scale-down" <?php selected($options['image_fit'], 'scale-down'); ?>>Scale-Down (Contain or None)</option>
                                    </select>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Border Theme</th>
                            <td>
                                <label>Theme:
                                    <select name="border_theme">
                                        <option value="simple" <?php selected($options['border_theme'], 'simple'); ?>>Simple Border</option>
                                        <option value="shadow" <?php selected($options['border_theme'], 'shadow'); ?>>Shadow Effect</option>
                                        <option value="gradient" <?php selected($options['border_theme'], 'gradient'); ?>>Gradient Border</option>
                                        <option value="fancy-border" <?php selected($options['border_theme'], 'fancy-border'); ?>>Fancy Dashed Border</option>
                                        <option value="animated" <?php selected($options['border_theme'], 'animated'); ?>>Animated Pulse Border</option>
                                    </select>
                                </label><br>

                                <label>Border Radius:
                                    <input type="range" name="border_radius" min="0" max="50" value="<?php echo esc_attr($options['border_radius']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['border_radius']); ?></output> px
                                    <input type="number" name="border_radius_text" min="0" max="50" value="<?php echo esc_attr($options['border_radius']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Border Width:
                                    <input type="range" name="border_width" min="0" max="10" value="<?php echo esc_attr($options['border_width']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['border_width']); ?></output> px
                                    <input type="number" name="border_width_text" min="0" max="10" value="<?php echo esc_attr($options['border_width']); ?>"
                                           class="number-input">
                                </label><br>

                                <label>Border Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="border_color" value="<?php echo esc_attr($options['border_color']); ?>"></label><br>
                                <div class="shadow-settings" style="<?php echo (in_array($options['border_theme'], ['shadow', 'fancy-border', 'animated'])) ? '' : 'display: none;'; ?>">
                                    <label>Shadow Blur:
                                        <input type="range" name="shadow_blur" min="0" max="30" value="<?php echo esc_attr($options['shadow_blur']); ?>"
                                               oninput="this.nextElementSibling.value = this.value;">
                                    <output><?php echo esc_attr($options['shadow_blur']); ?></output> px
                                    <input type="number" name="shadow_blur_text" min="0" max="30" value="<?php echo esc_attr($options['shadow_blur']); ?>"
                                           class="number-input">
                                    </label><br>
                                    <label>Shadow Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="shadow_color" value="<?php echo esc_attr($options['shadow_color']); ?>"></label><br>
                                </div>
                                <div class="gradient-settings" style="<?php echo (in_array($options['border_theme'], ['gradient', 'animated'])) ? '' : 'display: none;'; ?>">
                                    <label>Gradient Start Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="gradient_start" value="<?php echo esc_attr($options['gradient_start']); ?>"></label><br>
                                    <label>Gradient End Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="gradient_end" value="<?php echo esc_attr($options['gradient_end']); ?>"></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Overlay Text Styling</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="overlay_text_enabled" value="1" <?php checked(isset($options['overlay_text_enabled']) ? $options['overlay_text_enabled'] : false); ?>>
                                    Enable Overlay Text
                                </label><br>
                                <div class="overlay-text-settings" style="<?php echo (isset($options['overlay_text_enabled']) && $options['overlay_text_enabled']) ? '' : 'display: none;'; ?>">
                                    <label>Desktop Font Size:
                                        <input type="range" name="overlay_font_size" min="10" max="40" value="<?php echo esc_attr($options['overlay_font_size']); ?>"
                                               oninput="this.nextElementSibling.value = this.value;">
                                        <output><?php echo esc_attr($options['overlay_font_size']); ?></output> px
                                        <input type="number" name="overlay_font_size_text" min="10" max="40" value="<?php echo esc_attr($options['overlay_font_size']); ?>"
                                               class="number-input">
                                    </label><br>
                                    <label>Mobile Font Size:
                                        <input type="range" name="mobile_overlay_font_size" min="8" max="30" value="<?php echo esc_attr($options['mobile_overlay_font_size']); ?>"
                                           oninput="this.nextElementSibling.value = this.value;">
                                        <output><?php echo esc_attr($options['mobile_overlay_font_size']); ?></output> px
                                        <input type="number" name="mobile_overlay_font_size_text" min="8" max="30" value="<?php echo esc_attr($options['mobile_overlay_font_size']); ?>"
                                           class="number-input">
                                    </label><br>

                                    <label>
                                        <input type="checkbox" name="overlay_bg_enabled" value="1" <?php checked(isset($options['overlay_bg_enabled']) ? $options['overlay_bg_enabled'] : false); ?>>
                                        Enable Overlay Background
                                    </label><br>

                                    <label class="overlay-bg-color-label" style="<?php echo (isset($options['overlay_bg_enabled']) && $options['overlay_bg_enabled']) ? '' : 'display: none;'; ?>">Background Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="overlay_bg_color" value="<?php echo esc_attr($options['overlay_bg_color']); ?>"></label><br>

                                    <label>Text Color: <span class="color-field-label"></span><input type="text" class="wp-color-picker-field" name="overlay_text_color" value="<?php echo esc_attr($options['overlay_text_color']); ?>"></label><br>

                                    <label>Text Position:
                                        <select name="overlay_text_position">
                                            <option value="top" <?php selected($options['overlay_text_position'], 'top'); ?>>Top</option>
                                            <option value="center" <?php selected($options['overlay_text_position'], 'center'); ?>>Center</option>
                                            <option value="bottom" <?php selected($options['overlay_text_position'], 'bottom'); ?>>Bottom</option>
                                        </select>
                                    </label><br>
                                    <label>Text Alignment:
                                        <select name="overlay_text_alignment">
                                            <option value="left" <?php selected($options['overlay_text_alignment'], 'left'); ?>>Left</option>
                                            <option value="center" <?php selected($options['overlay_text_alignment'], 'center'); ?>>Center</option>
                                            <option value="right" <?php selected($options['overlay_text_alignment'], 'right'); ?>>Right</option>
                                        </select>
                                    </label><br>
                                    <label>Padding:
                                        <input type="range" name="overlay_padding" min="0" max="30" value="<?php echo esc_attr($options['overlay_padding']); ?>"
                                               oninput="this.nextElementSibling.value = this.value;">
                                        <output><?php echo esc_attr($options['overlay_padding']); ?></output> px
                                        <input type="number" name="overlay_padding_text" min="0" max="30" value="<?php echo esc_attr($options['overlay_padding']); ?>"
                                               class="number-input">
                                    </label><br>
                                    <label>Border Radius:
                                        <input type="range" name="overlay_border_radius" min="0" max="20" value="<?php echo esc_attr($options['overlay_border_radius']); ?>"
                                               oninput="this.nextElementSibling.value = this.value;">
                                        <output><?php echo esc_attr($options['overlay_border_radius']); ?></output> px
                                        <input type="number" name="overlay_border_radius_text" min="0" max="20" value="<?php echo esc_attr($options['overlay_border_radius']); ?>"
                                           class="number-input">
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings', 'primary', 'submit_floating_slider_settings'); ?>
                <div id="settings-save-feedback" style="display: none; margin-top: 10px;"></div> </form>
        </div>

        <style>
        .wrap h1 { font-size: 2em; margin-bottom: 20px; }
        .settings-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 5px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            font-size: 1.5em;
            color: #23282d;
        }
        .form-table th { width: 220px; padding-right: 20px; vertical-align: top; font-weight: 600; }
        .form-table td { padding-bottom: 15px; }
        .form-table label { display: inline-block; margin-bottom: 8px; }
        .form-table input[type="range"] { width: 200px; margin-right: 10px; vertical-align: middle; }
        .form-table output { display: inline-block; width: 30px; text-align: right; vertical-align: middle; }
        .form-table .number-input {
            width: 70px; /* Adjusted width for number input */
            padding: 5px;
            vertical-align: middle;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            text-align: center;
        }

        /* Toggle Switch */
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider-toggle {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider-toggle:before {
            position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s; border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        input:checked + .slider-toggle { background-color: #4CAF50; }
        input:checked + .slider-toggle:before { transform: translateX(26px); }

        /* Image Management Styles */
        #current-images-list { list-style: none; padding: 0; margin: 0; }
        .image-item {
            display: flex; align-items: flex-start; margin-bottom: 15px; padding: 10px;
            border: 1px solid #ddd; background-color: #f9f9f9; border-radius: 5px;
            cursor: grab; position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .image-item.ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.2); transform: rotate(1deg); }
        .image-item img { max-width: 120px; height: auto; margin-right: 15px; border-radius: 4px; object-fit: cover; }
        .image-item-details { flex-grow: 1; }
        .image-item-details label { display: block; margin-bottom: 5px; font-weight: normal; font-size: 0.9em; }
        .image-item-details label input[type="text"],
        .image-item-details label input[type="url"],
        .image-item-details label textarea {
            width: 98%; padding: 7px; margin-top: 3px; margin-bottom: 5px;
            border: 1px solid #e0e0e0; border-radius: 4px; box-sizing: border-box;
            font-size: 0.9em;
        }
        .image-item .remove-image {
            position: absolute; top: 8px; right: 8px; background: #f44336; color: white;
            border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 11px;
            transition: background-color 0.3s ease;
        }
        .image-item .remove-image:hover { background: #d32f2f; }
        .add-image-controls {
            margin-top: 20px; padding: 15px; border: 1px dashed #cfd8dc;
            background-color: #fcfcfc; border-radius: 5px;
        }
        .add-image-controls label { display: block; margin-bottom: 8px; font-weight: bold; }
        .add-image-controls input[type="text"],
        .add-image-controls input[type="url"],
        .add-image-controls textarea {
            width: calc(100% - 180px); padding: 8px; border: 1px solid #ddd;
            border-radius: 4px; box-sizing: border-box; display: inline-block;
            vertical-align: middle; margin-right: 10px;
        }
        .add-image-controls button { vertical-align: middle; }
        .add-image-controls .button-primary { margin-top: 15px; }

        /* Color Picker styles */
        .wp-picker-container { display: inline-block; vertical-align: middle; margin-top: 5px; }
        .wp-picker-container .wp-color-result {
            height: 30px; width: 30px; border-radius: 4px; margin: 0; vertical-align: top;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        .color-field-label {
            display: inline-block;
            min-width: 15px; /* Adjust as needed for spacing */
        }


        /* Save feedback message */
        #settings-save-feedback {
            margin-top: 15px;
            padding: 10px 15px;
            border-left: 4px solid #46b450;
            background-color: #e6ffe6;
            color: #333;
            font-size: 14px;
            display: none;
        }
        #settings-save-feedback.error {
            border-left-color: #dc3232;
            background-color: #ffe6e6;
        }

        /* Submit button styling */
        .submit button {
            margin-top: 20px;
            padding: 10px 25px;
            font-size: 1.1em;
            height: auto;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var frame; // Variable for the WordPress media frame

            // Initialize all color pickers
            $('.wp-color-picker-field').wpColorPicker();

            // Handle "Add Image" button click
            $('#add-new-image').on('click', function(e) {
                e.preventDefault();

                var imageUrl = $('#new_image_url').val();
                var imageLink = $('#new_image_link').val();
                var overlayText = $('#new_overlay_text').val();

                if (!imageUrl) {
                    alert('Please provide an image URL or select one from media library.');
                    return;
                }

                addImageToList(imageUrl, imageLink, overlayText);

                // Clear input fields after adding
                $('#new_image_url').val('');
                $('#new_image_link').val('');
                $('#new_overlay_text').val('');
            });

            // Handle "Select from Media Library" button click
            $('#select-image-from-media').on('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use Image' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#new_image_url').val(attachment.url);
                });

                frame.open();
            });

            function addImageToList(url, link, overlayText) {
                // Determine the highest existing index to ensure unique names for new items
                var maxIndex = -1;
                $('#current-images-list .image-item').each(function() {
                    var currentIndex = parseInt($(this).data('index'));
                    if (!isNaN(currentIndex) && currentIndex > maxIndex) {
                        maxIndex = currentIndex;
                    }
                });
                var newIndex = maxIndex + 1;

                var html = `
                    <li class="image-item" data-index="${newIndex}">
                        <img src="${url}">
                        <div class="image-item-details">
                            <label>Image URL: <input type="url" name="images[${newIndex}][url]" value="${url}" readonly></label>
                            <label>Link URL: <input type="url" name="images[${newIndex}][link]" value="${link}"></label>
                            <label>Overlay Text: <textarea name="images[${newIndex}][overlay_text]" rows="2">${overlayText}</textarea></label>
                        </div>
                        <button type="button" class="button remove-image">Remove</button>
                        <input type="hidden" name="images[${newIndex}][order]" value="${newIndex}">
                    </li>
                `;
                $('#current-images-list').append(html);
                updateImageIndices(); // Re-index all items after adding
            }

            $(document).on('click', '.remove-image', function() {
                if (confirm('Are you sure you want to remove this image?')) {
                    $(this).closest('.image-item').remove();
                    updateImageIndices();
                }
            });

            $('#current-images-list').sortable({
                handle: '.image-item',
                cursor: 'grabbing',
                update: function(event, ui) {
                    updateImageIndices();
                }
            });

            function updateImageIndices() {
                $('#current-images-list .image-item').each(function(index) {
                    $(this).data('index', index); // Update data-index for correct visual representation
                    $(this).find('input, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            // Ensure the name attributes are correctly re-indexed: images[0][url], images[1][url], etc.
                            name = name.replace(/images\[\d+\]/, 'images[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                    // Ensure the hidden order field is updated, this is crucial for server-side processing
                    $(this).find('input[name*="[order]"]').val(index);
                });
            }
            updateImageIndices(); // Initial call to ensure existing items are correctly indexed

            // Sync range and number inputs
            $(document).on('input', 'input[type="range"]', function() {
                var associatedNumberInput = $(this).nextAll('input[type="number"]').first();
                var isDelayInput = $(this).attr('name') === 'delay_before_first_slide_range';

                if (associatedNumberInput.length) {
                    if (isDelayInput) {
                        // For delay, display seconds with one decimal
                        associatedNumberInput.val(parseFloat($(this).val()).toFixed(1));
                    } else {
                        associatedNumberInput.val($(this).val());
                    }
                }
            });

            $(document).on('input', 'input[type="number"]', function() {
                var associatedRangeInput = $(this).prevAll('input[type="range"]').first();
                var isDelayInput = $(this).attr('name') === 'delay_before_first_slide_text';

                if (associatedRangeInput.length) {
                    var val = parseFloat($(this).val()); // Use parseFloat for decimal values
                    var min = parseFloat(associatedRangeInput.attr('min'));
                    var max = parseFloat(associatedRangeInput.attr('max'));
                    
                    if (isNaN(val) || val < min) {
                        val = min;
                    } else if (val > max) {
                        val = max;
                    }
                    associatedRangeInput.val(val);
                    if (isDelayInput) {
                        // For delay, format the displayed number to one decimal
                        $(this).val(val.toFixed(1));
                    }
                }
            });


            // Handle saving settings via AJAX
            $('#floating-slider-settings-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var formData = form.serialize();
                var feedbackDiv = $('#settings-save-feedback');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        feedbackDiv.fadeOut(100, function() {
                            $(this).removeClass('error success').html('<p>Saving settings...</p>').fadeIn(100);
                        });
                    },
                    success: function(response) {
                        feedbackDiv.html(response).removeClass('error').addClass('success').fadeIn(100);
                        setTimeout(function() {
                            feedbackDiv.fadeOut(500);
                        }, 3000);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        feedbackDiv.html('<p>Error saving settings. Please try again. Detailed Error: ' + (xhr.responseText || error) + '</p>').removeClass('success').addClass('error').fadeIn(100);
                        setTimeout(function() {
                            feedbackDiv.fadeOut(500);
                        }, 5000);
                    }
                });
            });

            // Show/hide specific pages selection based on dropdown
            $('select[name="display_pages"]').on('change', function() {
                if ($(this).val() === 'specific') {
                    $('.specific-pages-row').show();
                } else {
                    $('.specific-pages-row').hide();
                }
            }).change();

            // Border theme display logic (show/hide shadow/gradient settings)
            $('select[name="border_theme"]').on('change', function() {
                var theme = $(this).val();
                $('.shadow-settings').hide();
                $('.gradient-settings').hide();
                if (theme === 'shadow' || theme === 'fancy-border' || theme === 'animated') {
                    $('.shadow-settings').show();
                }
                if (theme === 'gradient' || theme === 'animated') {
                    $('.gradient-settings').show();
                }
            }).change();

            // Overlay text settings display logic
            $('input[name="overlay_text_enabled"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.overlay-text-settings').show();
                } else {
                    $('.overlay-text-settings').hide();
                }
            }).change();

            // Overlay background setting display logic
            $('input[name="overlay_bg_enabled"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.overlay-bg-color-label').show();
                } else {
                    $('.overlay-bg-color-label').hide();
                }
            }).change();
        });
        </script>
        <?php
    }

    /**
     * Saves plugin settings to the WordPress options table via AJAX.
     * All fields are submitted together, simplifying the save logic.
     */
    public function save_settings() {
        if (!current_user_can('manage_options') || !check_admin_referer('floating_slider_settings_action', 'floating_slider_settings_nonce')) {
            wp_die('<div class="notice notice-error"><p>Error: You do not have sufficient permissions to save settings or nonce verification failed.</p></div>');
        }

        $options = $this->get_default_options(); // Start with defaults to ensure all keys are present

        // Update values from $_POST, sanitizing as we go
        // Checkboxes: if key exists in $_POST, it's true; otherwise, false.
        $options['enabled'] = isset($_POST['enabled']);
        $options['close_button_enabled'] = isset($_POST['close_button_enabled']);
        $options['overlay_text_enabled'] = isset($_POST['overlay_text_enabled']);
        $options['overlay_bg_enabled'] = isset($_POST['overlay_bg_enabled']);

        // General settings
        $options['display_pages'] = sanitize_text_field($_POST['display_pages']);
        $options['specific_pages'] = isset($_POST['specific_pages']) && is_array($_POST['specific_pages']) ? array_map('intval', $_POST['specific_pages']) : array();

        // Use the numerical input values if available, fallback to range if only range is present
        $options['width'] = isset($_POST['width_text']) ? intval($_POST['width_text']) : (isset($_POST['width']) ? intval($_POST['width']) : $options['width']);
        $options['height'] = isset($_POST['height_text']) ? intval($_POST['height_text']) : (isset($_POST['height']) ? intval($_POST['height']) : $options['height']);
        $options['mobile_width'] = isset($_POST['mobile_width_text']) ? intval($_POST['mobile_width_text']) : (isset($_POST['mobile_width']) ? intval($_POST['mobile_width']) : $options['mobile_width']);
        $options['mobile_height'] = isset($_POST['mobile_height_text']) ? intval($_POST['mobile_height_text']) : (isset($_POST['mobile_height']) ? intval($_POST['mobile_height']) : $options['mobile_height']);

        $options['position_x'] = sanitize_text_field($_POST['position_x']);
        $options['position_y'] = sanitize_text_field($_POST['position_y']);
        $options['offset_x'] = isset($_POST['offset_x_text']) ? intval($_POST['offset_x_text']) : (isset($_POST['offset_x']) ? intval($_POST['offset_x']) : $options['offset_x']);
        $options['offset_y'] = isset($_POST['offset_y_text']) ? intval($_POST['offset_y_text']) : (isset($_POST['offset_y']) ? intval($_POST['offset_y']) : $options['offset_y']);
        $options['mobile_offset_x'] = isset($_POST['mobile_offset_x_text']) ? intval($_POST['mobile_offset_x_text']) : (isset($_POST['mobile_offset_x']) ? intval($_POST['mobile_offset_x']) : $options['mobile_offset_x']);
        $options['mobile_offset_y'] = isset($_POST['mobile_offset_y_text']) ? intval($_POST['mobile_offset_y_text']) : (isset($_POST['mobile_offset_y']) ? intval($_POST['mobile_offset_y']) : $options['mobile_offset_y']);

        $options['close_button_size'] = isset($_POST['close_button_size_text']) ? intval($_POST['close_button_size_text']) : (isset($_POST['close_button_size']) ? intval($_POST['close_button_size']) : $options['close_button_size']);
        $options['close_button_position'] = sanitize_text_field($_POST['close_button_position']);
        $options['close_button_color'] = sanitize_hex_color($_POST['close_button_color']);
        $options['close_button_bg'] = sanitize_hex_color($_POST['close_button_bg']);

        // Design & Animation
        $options['slide_animation'] = sanitize_text_field($_POST['slide_animation']);
        $options['slide_duration'] = isset($_POST['slide_duration_text']) ? intval($_POST['slide_duration_text']) : (isset($_POST['slide_duration']) ? intval($_POST['slide_duration']) : $options['slide_duration']);
        $options['animation_speed'] = isset($_POST['animation_speed_text']) ? intval($_POST['animation_speed_text']) : (isset($_POST['animation_speed']) ? intval($_POST['animation_speed']) : $options['animation_speed']);
        
        // Convert seconds to milliseconds for delay_before_first_slide
        $delay_seconds = isset($_POST['delay_before_first_slide_text']) ? floatval($_POST['delay_before_first_slide_text']) : (isset($_POST['delay_before_first_slide_range']) ? floatval($_POST['delay_before_first_slide_range']) : ($options['delay_before_first_slide'] / 1000));
        $options['delay_before_first_slide'] = intval($delay_seconds * 1000);

        $options['border_theme'] = sanitize_text_field($_POST['border_theme']);
        $options['border_radius'] = isset($_POST['border_radius_text']) ? intval($_POST['border_radius_text']) : (isset($_POST['border_radius']) ? intval($_POST['border_radius']) : $options['border_radius']);
        $options['border_color'] = sanitize_hex_color($_POST['border_color']);
        $options['border_width'] = isset($_POST['border_width_text']) ? intval($_POST['border_width_text']) : (isset($_POST['border_width']) ? intval($_POST['border_width']) : $options['border_width']);
        $options['shadow_blur'] = isset($_POST['shadow_blur_text']) ? intval($_POST['shadow_blur_text']) : (isset($_POST['shadow_blur']) ? intval($_POST['shadow_blur']) : $options['shadow_blur']);
        $options['shadow_color'] = sanitize_hex_color($_POST['shadow_color']);
        $options['gradient_start'] = sanitize_hex_color($_POST['gradient_start']);
        $options['gradient_end'] = sanitize_hex_color($_POST['gradient_end']);

        // New: Image Fit
        $options['image_fit'] = sanitize_text_field($_POST['image_fit']);

        // Overlay Text Styling
        $options['overlay_font_size'] = isset($_POST['overlay_font_size_text']) ? intval($_POST['overlay_font_size_text']) : (isset($_POST['overlay_font_size']) ? intval($_POST['overlay_font_size']) : $options['overlay_font_size']);
        $options['mobile_overlay_font_size'] = isset($_POST['mobile_overlay_font_size_text']) ? intval($_POST['mobile_overlay_font_size_text']) : (isset($_POST['mobile_overlay_font_size']) ? intval($_POST['mobile_overlay_font_size']) : $options['mobile_overlay_font_size']);
        $options['overlay_text_color'] = sanitize_hex_color($_POST['overlay_text_color']);
        
        $overlay_bg_color = sanitize_text_field($_POST['overlay_bg_color']);
        if (preg_match('/^rgba\(\d{1,3},\d{1,3},\d{1,3},(0|1|0?\.\d+)\)$/i', $overlay_bg_color) || sanitize_hex_color($overlay_bg_color) === $overlay_bg_color) {
            $options['overlay_bg_color'] = $overlay_bg_color;
        } else {
            $options['overlay_bg_color'] = $this->get_default_options()['overlay_bg_color'];
        }

        $options['overlay_text_position'] = sanitize_text_field($_POST['overlay_text_position']);
        $options['overlay_text_alignment'] = sanitize_text_field($_POST['overlay_text_alignment']);
        $options['overlay_padding'] = isset($_POST['overlay_padding_text']) ? intval($_POST['overlay_padding_text']) : (isset($_POST['overlay_padding']) ? intval($_POST['overlay_padding']) : $options['overlay_padding']);
        $options['overlay_border_radius'] = isset($_POST['overlay_border_radius_text']) ? intval($_POST['overlay_border_radius_text']) : (isset($_POST['overlay_border_radius']) ? intval($_POST['overlay_border_radius']) : $options['overlay_border_radius']);

        // Process images directly as they come from the form, relying on JS for order
        $processed_images = [];
        if (isset($_POST['images']) && is_array($_POST['images'])) {
            // Sort images by the 'order' field if it exists, otherwise keep natural order
            $ordered_images = [];
            foreach ($_POST['images'] as $image_data) {
                $url = isset($image_data['url']) ? esc_url_raw($image_data['url']) : '';
                $link = isset($image_data['link']) ? esc_url_raw($image_data['link']) : '';
                $overlay_text = isset($image_data['overlay_text']) ? wp_kses_post($image_data['overlay_text']) : '';

                if (!empty($url)) {
                    // Use the 'order' field from the form for sorting
                    if (isset($image_data['order']) && is_numeric($image_data['order'])) {
                        $ordered_images[intval($image_data['order'])] = array(
                            'url' => $url,
                            'link' => $link,
                            'overlay_text' => $overlay_text
                        );
                    } else {
                        // Fallback if 'order' is missing (should not happen with updated JS)
                        $ordered_images[] = array(
                            'url' => $url,
                            'link' => $link,
                            'overlay_text' => $overlay_text
                        );
                    }
                }
            }
            ksort($ordered_images); // Sort by keys (the 'order' values)
            $options['images'] = array_values($ordered_images); // Re-index array after sorting
        }
        
        $updated = update_option('floating_slider_options', $options);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        } else {
            $current_options = get_option('floating_slider_options', $this->get_default_options());
            if ($current_options === $options) {
                echo '<div class="notice notice-info is-dismissible"><p>Settings are already up to date (no changes detected).</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error: Settings could not be saved. Please check your database permissions or try again.</p></div>';
            }
        }
        
        wp_die();
    }
}

// Initialize the plugin
new FloatingImageSlider();
