<?php
/**
 * Plugin Name: Professional Floating Slider
 * Description: Floating image slider with full customization capabilities.
 * Version: 1.1
 * Author: Your Name
 * Text Domain: floating-slider-pro
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FloatingSliderPlugin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'display_slider'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_floating_slider_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_floating_slider_delete_image', array($this, 'ajax_delete_image'));
        add_action('wp_ajax_floating_slider_save_image_order', array($this, 'ajax_save_image_order'));
        add_action('wp_ajax_floating_slider_update_image', array($this, 'ajax_update_image'));

        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }

    /**
     * Initializes plugin settings on activation.
     */
    public function activate_plugin() {
        $default_settings = array(
            'enabled'             => 1,
            'display_pages'       => 'all',
            'specific_pages'      => array(),
            'width'               => 300,
            'height'              => 200,
            'position_horizontal' => 'right',
            'position_vertical'   => 'center',
            'horizontal_offset'   => 20,
            'vertical_offset'     => 0,
            'images'              => array(),
            'close_button_size'   => 20,
            'close_button_color'  => '#ffffff',
            'close_button_bg'     => '#ff0000',
            'close_button_pos_h'  => 'right', // New: Horizontal close button position
            'close_button_pos_v'  => 'top',   // New: Vertical close button position
            'animation_type'      => 'fade',
            'slide_duration'      => 3000,
            'delay_show'          => 2000,
            'border_theme'        => 'gradient',
            'border_width'        => 3, // New: Border width
            'border_radius'       => 15,
            'shadow_blur'         => 20,
            'shadow_color'        => 'rgba(0,0,0,0.3)',
            'padding'             => 10, // New: Padding for images inside slider
        );

        add_option('floating_slider_settings', $default_settings);
    }

    /**
     * Placeholder for future init actions (if any).
     */
    public function init() {
        // Load text domain for internationalization (if needed in the future)
        // load_plugin_textdomain('floating-slider-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Adds the plugin settings page to the admin menu.
     */
    public function add_admin_menu() {
        add_options_page(
            __('Floating Slider Settings', 'floating-slider-pro'),
            __('Floating Slider', 'floating-slider-pro'),
            'manage_options',
            'floating-slider',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Registers plugin settings using the Settings API.
     */
    public function register_settings() {
        register_setting(
            'floating_slider_group', // Option group
            'floating_slider_settings', // Option name
            array($this, 'sanitize_settings') // Sanitize callback
        );

        // Register sections and fields (more structured approach)
        add_settings_section(
            'floating_slider_general',
            __('General Settings', 'floating-slider-pro'),
            null,
            'floating-slider'
        );
        add_settings_field(
            'enabled',
            __('Enable/Disable', 'floating-slider-pro'),
            array($this, 'render_toggle_field'),
            'floating-slider',
            'floating_slider_general',
            array('label_for' => 'enabled', 'name' => 'enabled', 'value' => '1', 'text' => __('Enable Slider', 'floating-slider-pro'))
        );
        add_settings_field(
            'display_pages',
            __('Display On Pages', 'floating-slider-pro'),
            array($this, 'render_display_pages_field'),
            'floating-slider',
            'floating_slider_general'
        );
        add_settings_field(
            'dimensions',
            __('Slider Dimensions', 'floating-slider-pro'),
            array($this, 'render_dimensions_field'),
            'floating-slider',
            'floating_slider_general'
        );
        add_settings_field(
            'position',
            __('Slider Position', 'floating-slider-pro'),
            array($this, 'render_position_field'),
            'floating-slider',
            'floating_slider_general'
        );
        add_settings_field(
            'padding',
            __('Internal Padding', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_general',
            array('name' => 'padding', 'min' => 0, 'max' => 50, 'step' => 1, 'unit' => 'px')
        );

        add_settings_section(
            'floating_slider_close_button',
            __('Close Button Settings', 'floating-slider-pro'),
            null,
            'floating-slider'
        );
        add_settings_field(
            'close_button_size',
            __('Size', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_close_button',
            array('name' => 'close_button_size', 'min' => 10, 'max' => 50, 'step' => 1, 'unit' => 'px')
        );
        add_settings_field(
            'close_button_color',
            __('Text Color', 'floating-slider-pro'),
            array($this, 'render_color_field'),
            'floating-slider',
            'floating_slider_close_button',
            array('name' => 'close_button_color')
        );
        add_settings_field(
            'close_button_bg',
            __('Background Color', 'floating-slider-pro'),
            array($this, 'render_color_field'),
            'floating-slider',
            'floating_slider_close_button',
            array('name' => 'close_button_bg')
        );
        add_settings_field(
            'close_button_position',
            __('Position', 'floating-slider-pro'),
            array($this, 'render_close_button_position_field'),
            'floating-slider',
            'floating_slider_close_button'
        );


        add_settings_section(
            'floating_slider_animation',
            __('Animation Settings', 'floating-slider-pro'),
            null,
            'floating-slider'
        );
        add_settings_field(
            'animation_type',
            __('Animation Type', 'floating-slider-pro'),
            array($this, 'render_select_field'),
            'floating-slider',
            'floating_slider_animation',
            array(
                'name'    => 'animation_type',
                'options' => array(
                    'fade'  => __('Fade', 'floating-slider-pro'),
                    'slide' => __('Slide', 'floating-slider-pro'),
                    'zoom'  => __('Zoom', 'floating-slider-pro'),
                )
            )
        );
        add_settings_field(
            'slide_duration',
            __('Slide Duration', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_animation',
            array('name' => 'slide_duration', 'min' => 500, 'max' => 10000, 'step' => 100, 'unit' => 'ms')
        );
        add_settings_field(
            'delay_show',
            __('Delay Before Show', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_animation',
            array('name' => 'delay_show', 'min' => 0, 'max' => 10000, 'step' => 100, 'unit' => 'ms')
        );


        add_settings_section(
            'floating_slider_style',
            __('Style Settings', 'floating-slider-pro'),
            null,
            'floating-slider'
        );
        add_settings_field(
            'border_theme',
            __('Border Theme', 'floating-slider-pro'),
            array($this, 'render_select_field'),
            'floating-slider',
            'floating_slider_style',
            array(
                'name'    => 'border_theme',
                'options' => array(
                    'none'     => __('None', 'floating-slider-pro'), // New: No border
                    'solid'    => __('Solid Color', 'floating-slider-pro'), // New: Solid color border
                    'gradient' => __('Gradient', 'floating-slider-pro'),
                    'neon'     => __('Neon', 'floating-slider-pro'),
                    'rainbow'  => __('Rainbow', 'floating-slider-pro'),
                    'glow'     => __('Glow', 'floating-slider-pro'),
                    'pulse'    => __('Pulse', 'floating-slider-pro'),
                )
            )
        );
        add_settings_field(
            'border_width',
            __('Border Width', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_style',
            array('name' => 'border_width', 'min' => 0, 'max' => 20, 'step' => 1, 'unit' => 'px')
        );
        add_settings_field(
            'border_radius',
            __('Border Radius', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_style',
            array('name' => 'border_radius', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => 'px')
        );
        add_settings_field(
            'shadow_blur',
            __('Shadow Blur', 'floating-slider-pro'),
            array($this, 'render_range_field'),
            'floating-slider',
            'floating_slider_style',
            array('name' => 'shadow_blur', 'min' => 0, 'max' => 50, 'step' => 1, 'unit' => 'px')
        );
        add_settings_field(
            'shadow_color',
            __('Shadow Color', 'floating-slider-pro'),
            array($this, 'render_color_field'),
            'floating-slider',
            'floating_slider_style',
            array('name' => 'shadow_color', 'default' => 'rgba(0,0,0,0.3)', 'alpha' => true)
        );
    }

    /**
     * Sanitizes plugin settings.
     *
     * @param array $input The raw input settings.
     * @return array The sanitized settings.
     */
    public function sanitize_settings($input) {
        $settings = get_option('floating_slider_settings', array());

        $new_settings = array();
        $new_settings['enabled']             = isset($input['enabled']) ? 1 : 0;
        $new_settings['display_pages']       = sanitize_text_field($input['display_pages']);
        $new_settings['specific_pages']      = isset($input['specific_pages']) ? array_map('intval', (array) $input['specific_pages']) : array();
        $new_settings['width']               = intval($input['width']);
        $new_settings['height']              = intval($input['height']);
        $new_settings['position_horizontal'] = sanitize_text_field($input['position_horizontal']);
        $new_settings['position_vertical']   = sanitize_text_field($input['position_vertical']);
        $new_settings['horizontal_offset']   = intval($input['horizontal_offset']);
        $new_settings['vertical_offset']     = intval($input['vertical_offset']);
        $new_settings['padding']             = intval($input['padding']);

        $new_settings['close_button_size']   = intval($input['close_button_size']);
        $new_settings['close_button_color']  = sanitize_hex_color($input['close_button_color']);
        $new_settings['close_button_bg']     = sanitize_hex_color($input['close_button_bg']);
        $new_settings['close_button_pos_h']  = sanitize_text_field($input['close_button_pos_h']);
        $new_settings['close_button_pos_v']  = sanitize_text_field($input['close_button_pos_v']);

        $new_settings['animation_type']      = sanitize_text_field($input['animation_type']);
        $new_settings['slide_duration']      = intval($input['slide_duration']);
        $new_settings['delay_show']          = intval($input['delay_show']);

        $new_settings['border_theme']        = sanitize_text_field($input['border_theme']);
        $new_settings['border_width']        = intval($input['border_width']);
        $new_settings['border_radius']       = intval($input['border_radius']);
        $new_settings['shadow_blur']         = intval($input['shadow_blur']);
        // Allow rgba for shadow color
        $new_settings['shadow_color']        = sanitize_text_field($input['shadow_color']);

        // Images are handled via AJAX, so we retain the existing ones unless overwritten.
        $new_settings['images'] = isset($settings['images']) ? $settings['images'] : array();

        return $new_settings;
    }

    /**
     * Renders a toggle (checkbox) field.
     * @param array $args Field arguments.
     */
    public function render_toggle_field($args) {
        $settings = get_option('floating_slider_settings');
        $name     = esc_attr($args['name']);
        $value    = esc_attr($args['value']);
        $text     = esc_html($args['text']);
        $checked  = isset($settings[$name]) && $settings[$name] == $value ? 'checked' : '';
        echo "<label><input type='checkbox' name='floating_slider_settings[{$name}]' value='{$value}' {$checked} /> {$text}</label>";
    }

    /**
     * Renders a range (slider) input field.
     * @param array $args Field arguments.
     */
    public function render_range_field($args) {
        $settings = get_option('floating_slider_settings');
        $name     = esc_attr($args['name']);
        $min      = esc_attr($args['min']);
        $max      = esc_attr($args['max']);
        $step     = esc_attr($args['step']);
        $unit     = isset($args['unit']) ? esc_html($args['unit']) : '';
        $value    = isset($settings[$name]) ? esc_attr($settings[$name]) : '';

        echo "<input type='range' id='{$name}' name='floating_slider_settings[{$name}]' min='{$min}' max='{$max}' step='{$step}' value='{$value}' class='floating-slider-range'>";
        echo " <span class='floating-slider-range-value'>{$value}{$unit}</span>";
    }

    /**
     * Renders a color input field.
     * @param array $args Field arguments.
     */
    public function render_color_field($args) {
        $settings = get_option('floating_slider_settings');
        $name     = esc_attr($args['name']);
        $value    = isset($settings[$name]) ? esc_attr($settings[$name]) : (isset($args['default']) ? esc_attr($args['default']) : '');
        $alpha    = isset($args['alpha']) && $args['alpha'] ? 'data-alpha="true"' : '';
        echo "<input type='text' name='floating_slider_settings[{$name}]' value='{$value}' class='floating-slider-color-picker' {$alpha} />";
    }

    /**
     * Renders the display pages radio and select fields.
     */
    public function render_display_pages_field() {
        $settings = get_option('floating_slider_settings');
        $pages    = get_pages();
        ?>
        <input type="radio" name="floating_slider_settings[display_pages]" value="all" <?php checked($settings['display_pages'], 'all'); ?> />
        <label><?php _e('All Pages', 'floating-slider-pro'); ?></label><br>
        <input type="radio" name="floating_slider_settings[display_pages]" value="specific" <?php checked($settings['display_pages'], 'specific'); ?> />
        <label><?php _e('Specific Pages', 'floating-slider-pro'); ?></label><br>
        <select name="floating_slider_settings[specific_pages][]" multiple style="height: 100px; width: 300px;">
            <?php foreach ($pages as $page) : ?>
                <option value="<?php echo esc_attr($page->ID); ?>"
                    <?php echo in_array($page->ID, $settings['specific_pages']) ? 'selected' : ''; ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Renders the slider dimensions fields.
     */
    public function render_dimensions_field() {
        $settings = get_option('floating_slider_settings');
        ?>
        <?php _e('Width', 'floating-slider-pro'); ?>: <input type="number" name="floating_slider_settings[width]" value="<?php echo esc_attr($settings['width']); ?>" min="50" max="1000" step="1" /> px<br>
        <?php _e('Height', 'floating-slider-pro'); ?>: <input type="number" name="floating_slider_settings[height]" value="<?php echo esc_attr($settings['height']); ?>" min="50" max="1000" step="1" /> px
        <?php
    }

    /**
     * Renders the slider position fields.
     */
    public function render_position_field() {
        $settings = get_option('floating_slider_settings');
        ?>
        <?php _e('Horizontal Position', 'floating-slider-pro'); ?>:
        <select name="floating_slider_settings[position_horizontal]">
            <option value="left" <?php selected($settings['position_horizontal'], 'left'); ?>><?php _e('Left', 'floating-slider-pro'); ?></option>
            <option value="right" <?php selected($settings['position_horizontal'], 'right'); ?>><?php _e('Right', 'floating-slider-pro'); ?></option>
        </select><br>
        <?php _e('Vertical Position', 'floating-slider-pro'); ?>:
        <select name="floating_slider_settings[position_vertical]">
            <option value="top" <?php selected($settings['position_vertical'], 'top'); ?>><?php _e('Top', 'floating-slider-pro'); ?></option>
            <option value="center" <?php selected($settings['position_vertical'], 'center'); ?>><?php _e('Center', 'floating-slider-pro'); ?></option>
            <option value="bottom" <?php selected($settings['position_vertical'], 'bottom'); ?>><?php _e('Bottom', 'floating-slider-pro'); ?></option>
        </select><br>
        <?php _e('Horizontal Offset', 'floating-slider-pro'); ?>:
        <input type="range" id="horizontal_offset" name="floating_slider_settings[horizontal_offset]" min="0" max="200" step="1" value="<?php echo esc_attr($settings['horizontal_offset']); ?>" class="floating-slider-range">
        <span class="floating-slider-range-value"><?php echo esc_attr($settings['horizontal_offset']); ?>px</span><br>
        <?php _e('Vertical Offset', 'floating-slider-pro'); ?>:
        <input type="range" id="vertical_offset" name="floating_slider_settings[vertical_offset]" min="-200" max="200" step="1" value="<?php echo esc_attr($settings['vertical_offset']); ?>" class="floating-slider-range">
        <span class="floating-slider-range-value"><?php echo esc_attr($settings['vertical_offset']); ?>px</span>
        <?php
    }

    /**
     * Renders the close button position fields.
     */
    public function render_close_button_position_field() {
        $settings = get_option('floating_slider_settings');
        ?>
        <?php _e('Horizontal', 'floating-slider-pro'); ?>:
        <select name="floating_slider_settings[close_button_pos_h]">
            <option value="left" <?php selected($settings['close_button_pos_h'], 'left'); ?>><?php _e('Left', 'floating-slider-pro'); ?></option>
            <option value="right" <?php selected($settings['close_button_pos_h'], 'right'); ?>><?php _e('Right', 'floating-slider-pro'); ?></option>
        </select><br>
        <?php _e('Vertical', 'floating-slider-pro'); ?>:
        <select name="floating_slider_settings[close_button_pos_v]">
            <option value="top" <?php selected($settings['close_button_pos_v'], 'top'); ?>><?php _e('Top', 'floating-slider-pro'); ?></option>
            <option value="bottom" <?php selected($settings['close_button_pos_v'], 'bottom'); ?>><?php _e('Bottom', 'floating-slider-pro'); ?></option>
        </select>
        <?php
    }

    /**
     * Renders a generic select dropdown field.
     * @param array $args Field arguments.
     */
    public function render_select_field($args) {
        $settings = get_option('floating_slider_settings');
        $name     = esc_attr($args['name']);
        $options  = $args['options'];
        $value    = isset($settings[$name]) ? esc_attr($settings[$name]) : '';
        ?>
        <select name="floating_slider_settings[<?php echo $name; ?>]">
            <?php foreach ($options as $opt_val => $opt_label) : ?>
                <option value="<?php echo esc_attr($opt_val); ?>" <?php selected($value, $opt_val); ?>>
                    <?php echo esc_html($opt_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Enqueues admin scripts and styles.
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'settings_page_floating-slider') {
            return;
        }

        // Enqueue WordPress media uploader scripts
        wp_enqueue_media();
        // Enqueue jQuery UI Sortable for image reordering
        wp_enqueue_script('jquery-ui-sortable');
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Custom admin CSS
        wp_add_inline_style('wp-admin', '
            .floating-slider-range {
                width: 200px;
                vertical-align: middle;
            }
            .floating-slider-range-value {
                display: inline-block;
                min-width: 50px;
                text-align: right;
            }
            .image-item {
                border: 1px solid #ddd;
                padding: 10px;
                margin: 10px;
                display: inline-block;
                vertical-align: top;
                width: 150px;
                text-align: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                border-radius: 5px;
                position: relative;
            }
            .image-item img {
                width: 100%;
                height: 100px; /* Fixed height for consistent display */
                object-fit: cover;
                display: block;
                margin-bottom: 5px;
                border-radius: 3px;
            }
            .image-actions {
                margin-top: 5px;
            }
            .image-item button {
                margin: 2px;
                cursor: pointer;
            }
            #slider-images-container {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                border: 1px dashed #ccc;
                padding: 15px;
                min-height: 100px;
                align-items: center;
                justify-content: center;
            }
            #add-image-btn {
                margin-top: 20px;
            }
            .form-table th {
                width: 250px;
            }
        ');

        // Custom admin JavaScript
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Initialize color pickers
                $(".floating-slider-color-picker").wpColorPicker();

                // Update range values dynamically
                $(".floating-slider-range").on("input", function() {
                    $(this).next(".floating-slider-range-value").text($(this).val() + $(this).siblings("span").text().replace(/[\d\.\-]+/g, ""));
                });

                // Image Uploader
                $("#add-image-btn").click(function(e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: "' . esc_html__('Select or Upload Image', 'floating-slider-pro') . '",
                        button: {
                            text: "' . esc_html__('Use this image', 'floating-slider-pro') . '"
                        },
                        multiple: false
                    });

                    frame.on("select", function() {
                        var attachment = frame.state().get("selection").first().toJSON();
                        var linkUrl = prompt("' . esc_html__('Enter image link (optional):', 'floating-slider-pro') . '", ""); // Allow empty string for no link

                        if (linkUrl !== null) { // If user didn\'t cancel prompt
                            $.post(ajaxurl, {
                                action: "floating_slider_upload_image",
                                security: "' . wp_create_nonce('floating_slider_image_upload') . '",
                                attachment_id: attachment.id,
                                link_url: linkUrl
                            }, function(response) {
                                if (response.success) {
                                    location.reload(); // Reload to reflect changes
                                } else {
                                    alert("' . esc_html__('Error uploading image.', 'floating-slider-pro') . '");
                                }
                            });
                        }
                    });
                    frame.open();
                });

                // Image Deletion
                $(document).on("click", ".delete-image-btn", function() {
                    if (confirm("' . esc_html__('Are you sure you want to delete this image?', 'floating-slider-pro') . '")) {
                        var index = $(this).data("index");
                        $.post(ajaxurl, {
                            action: "floating_slider_delete_image",
                            security: "' . wp_create_nonce('floating_slider_image_delete') . '",
                            index: index
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert("' . esc_html__('Error deleting image.', 'floating-slider-pro') . '");
                            }
                        });
                    }
                });

                // Image Editing
                $(document).on("click", ".edit-image-btn", function() {
                    var $this = $(this);
                    var index = $this.data("index");
                    var currentLink = $this.data("link");

                    var newLinkUrl = prompt("' . esc_html__('Edit image link:', 'floating-slider-pro') . '", currentLink);

                    if (newLinkUrl !== null) {
                        // Check if a new image should be selected
                        var selectNewImage = confirm("' . esc_html__('Do you want to select a new image file?', 'floating-slider-pro') . '");

                        if (selectNewImage) {
                            var frame = wp.media({
                                title: "' . esc_html__('Select New Image', 'floating-slider-pro') . '",
                                button: {
                                    text: "' . esc_html__('Use this image', 'floating-slider-pro') . '"
                                },
                                multiple: false
                            });

                            frame.on("select", function() {
                                var attachment = frame.state().get("selection").first().toJSON();
                                $.post(ajaxurl, {
                                    action: "floating_slider_update_image",
                                    security: "' . wp_create_nonce('floating_slider_image_update') . '",
                                    index: index,
                                    attachment_id: attachment.id,
                                    link_url: newLinkUrl
                                }, function(response) {
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        alert("' . esc_html__('Error updating image.', 'floating-slider-pro') . '");
                                    }
                                });
                            });
                            frame.open();
                        } else {
                            // Only update the link if no new image selected
                            $.post(ajaxurl, {
                                action: "floating_slider_update_image",
                                security: "' . wp_create_nonce('floating_slider_image_update') . '",
                                index: index,
                                attachment_id: false, // Indicate no new attachment
                                link_url: newLinkUrl
                            }, function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert("' . esc_html__('Error updating image link.', 'floating-slider-pro') . '");
                                }
                            });
                        }
                    }
                });

                // Image Reordering (Sortable)
                $("#slider-images-container").sortable({
                    items: ".image-item",
                    cursor: "move",
                    axis: "x,y",
                    opacity: 0.7,
                    stop: function(event, ui) {
                        var newOrder = [];
                        $("#slider-images-container .image-item").each(function() {
                            newOrder.push($(this).data("id"));
                        });
                        $.post(ajaxurl, {
                            action: "floating_slider_save_image_order",
                            security: "' . wp_create_nonce('floating_slider_image_order') . '",
                            order: newOrder
                        }, function(response) {
                            if (!response.success) {
                                alert("' . esc_html__('Error saving image order.', 'floating-slider-pro') . '");
                            }
                        });
                    }
                });
            });
        ');
    }

    /**
     * Enqueues frontend scripts.
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('jquery');
    }

    /**
     * AJAX handler for uploading slider images.
     */
    public function ajax_upload_image() {
        check_ajax_referer('floating_slider_image_upload', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $attachment_id = intval($_POST['attachment_id']);
        $link_url      = isset($_POST['link_url']) ? sanitize_url($_POST['link_url']) : '';

        $settings = get_option('floating_slider_settings', array());
        if (!isset($settings['images'])) {
            $settings['images'] = array();
        }

        if ($attachment_id) {
            $image_url = wp_get_attachment_url($attachment_id);
            if ($image_url) {
                $settings['images'][] = array(
                    'id'   => $attachment_id,
                    'url'  => $image_url,
                    'link' => $link_url
                );
                update_option('floating_slider_settings', $settings);
                wp_send_json_success();
            } else {
                wp_send_json_error(array('message' => __('Invalid attachment ID.', 'floating-slider-pro')));
            }
        } else {
            wp_send_json_error(array('message' => __('No attachment ID provided.', 'floating-slider-pro')));
        }
    }

    /**
     * AJAX handler for deleting slider images.
     */
    public function ajax_delete_image() {
        check_ajax_referer('floating_slider_image_delete', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $index = intval($_POST['index']);
        $settings = get_option('floating_slider_settings', array());

        if (isset($settings['images'][$index])) {
            unset($settings['images'][$index]);
            $settings['images'] = array_values($settings['images']); // Re-index array
            update_option('floating_slider_settings', $settings);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Image not found.', 'floating-slider-pro')));
        }
    }

    /**
     * AJAX handler for updating slider images (link or replacing image).
     */
    public function ajax_update_image() {
        check_ajax_referer('floating_slider_image_update', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $index = intval($_POST['index']);
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : false;
        $link_url = isset($_POST['link_url']) ? sanitize_url($_POST['link_url']) : '';

        $settings = get_option('floating_slider_settings', array());

        if (isset($settings['images'][$index])) {
            if ($attachment_id) {
                $image_url = wp_get_attachment_url($attachment_id);
                if ($image_url) {
                    $settings['images'][$index]['id'] = $attachment_id;
                    $settings['images'][$index]['url'] = $image_url;
                } else {
                    wp_send_json_error(array('message' => __('Invalid attachment ID for update.', 'floating-slider-pro')));
                }
            }
            $settings['images'][$index]['link'] = $link_url;

            update_option('floating_slider_settings', $settings);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Image not found for update.', 'floating-slider-pro')));
        }
    }

    /**
     * AJAX handler for saving image order after sorting.
     */
    public function ajax_save_image_order() {
        check_ajax_referer('floating_slider_image_order', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $order_ids = (array) $_POST['order']; // Array of attachment IDs in new order
        $settings = get_option('floating_slider_settings', array());
        $current_images = isset($settings['images']) ? $settings['images'] : array();
        $new_images = array();

        foreach ($order_ids as $id) {
            foreach ($current_images as $image) {
                if ($image['id'] == $id) {
                    $new_images[] = $image;
                    break;
                }
            }
        }
        // Ensure no images are lost if some IDs were not in the current order (shouldn't happen with correct JS)
        if (count($new_images) === count($current_images)) {
             $settings['images'] = $new_images;
             update_option('floating_slider_settings', $settings);
             wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to reorder images.', 'floating-slider-pro')));
        }
    }

    /**
     * Renders the main admin page.
     */
    public function render_admin_page() {
        // Retrieve saved settings
        $settings = get_option('floating_slider_settings', array());
        $pages = get_pages();
        ?>
        <div class="wrap">
            <h1><?php _e('Professional Floating Slider Settings', 'floating-slider-pro'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('floating_slider_group'); ?>
                <?php do_settings_sections('floating-slider'); ?>

                <h2><?php _e('Image Management', 'floating-slider-pro'); ?></h2>
                <p><?php _e('Drag and drop images to reorder them.', 'floating-slider-pro'); ?></p>
                <div id="slider-images-container">
                    <?php if (!empty($settings['images'])) : ?>
                        <?php foreach ($settings['images'] as $index => $image) : ?>
                            <div class="image-item" data-id="<?php echo esc_attr($image['id']); ?>" data-index="<?php echo esc_attr($index); ?>">
                                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr__('Slider Image', 'floating-slider-pro'); ?>" />
                                <div class="image-actions">
                                    <button type="button" class="edit-image-btn button button-small" data-index="<?php echo esc_attr($index); ?>" data-link="<?php echo esc_attr($image['link']); ?>"><?php _e('Edit', 'floating-slider-pro'); ?></button>
                                    <button type="button" class="delete-image-btn button button-small" data-index="<?php echo esc_attr($index); ?>"><?php _e('Delete', 'floating-slider-pro'); ?></button>
                                </div>
                                <div style="font-size: 0.8em; word-break: break-all;">
                                    <?php
                                    // Display link, truncate if too long
                                    $link_display = !empty($image['link']) ? esc_url($image['link']) : __('No Link', 'floating-slider-pro');
                                    echo mb_strlen($link_display) > 20 ? mb_substr($link_display, 0, 17) . '...' : $link_display;
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p><?php _e('No images added yet. Click "Add Image" to start!', 'floating-slider-pro'); ?></p>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-image-btn" class="button button-primary"><?php _e('Add Image', 'floating-slider-pro'); ?></button>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders the slider HTML, CSS, and JavaScript on the frontend.
     */
    public function display_slider() {
        $settings = get_option('floating_slider_settings', array());

        if (empty($settings) || !$settings['enabled'] || empty($settings['images'])) {
            return;
        }

        // Check display on specific pages
        if ($settings['display_pages'] == 'specific') {
            $current_page_id = get_queried_object_id();
            if (!in_array($current_page_id, $settings['specific_pages'])) {
                return;
            }
        }

        $this->render_slider_css($settings);
        $this->render_slider_html($settings);
        $this->render_slider_js($settings);
    }

    /**
     * Renders inline CSS for the floating slider.
     * @param array $settings Plugin settings.
     */
    private function render_slider_css($settings) {
        $position_style = '';

        // Horizontal position
        if ($settings['position_horizontal'] == 'left') {
            $position_style .= 'left: ' . esc_attr($settings['horizontal_offset']) . 'px;';
        } else {
            $position_style .= 'right: ' . esc_attr($settings['horizontal_offset']) . 'px;';
        }

        // Vertical position
        if ($settings['position_vertical'] == 'top') {
            $position_style .= 'top: ' . esc_attr($settings['vertical_offset']) . 'px;';
        } elseif ($settings['position_vertical'] == 'bottom') {
            $position_style .= 'bottom: ' . esc_attr($settings['vertical_offset']) . 'px;';
        } else { // center
            $position_style .= 'top: 50%; margin-top: -' . ($settings['height'] / 2) . 'px; transform: translateY(' . esc_attr($settings['vertical_offset']) . 'px);';
        }

        // Border theme
        $border_style = '';
        $border_width = esc_attr($settings['border_width']);
        $border_radius = esc_attr($settings['border_radius']);
        $shadow_blur = esc_attr($settings['shadow_blur']);
        $shadow_color = esc_attr($settings['shadow_color']);

        switch ($settings['border_theme']) {
            case 'none':
                $border_style = 'border: none;';
                break;
            case 'solid':
                $border_style = "border: {$border_width}px solid #333;"; // Default solid color
                break;
            case 'gradient':
                $border_style = "border: {$border_width}px solid; border-image: linear-gradient(45deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3) 1;";
                break;
            case 'neon':
                $border_style = "border: {$border_width}px solid #00ffff; box-shadow: 0 0 10px #00ffff, inset 0 0 10px #00ffff;";
                break;
            case 'rainbow':
                $border_style = "border: {$border_width}px solid; border-image: conic-gradient(from 0deg, red, orange, yellow, green, blue, indigo, violet, red) 1; animation: floating-slider-rainbow-rotate 3s linear infinite;";
                break;
            case 'glow':
                $border_style = "border: {$border_width}px solid #fff; box-shadow: 0 0 20px rgba(255,255,255,0.8);";
                break;
            case 'pulse':
                $border_style = "border: {$border_width}px solid #ff0080; animation: floating-slider-pulse-glow 2s ease-in-out infinite;";
                break;
        }

        // Close button position
        $close_btn_pos_h = ($settings['close_button_pos_h'] == 'left') ? 'left: -' . ($settings['close_button_size'] / 2) . 'px;' : 'right: -' . ($settings['close_button_size'] / 2) . 'px;';
        $close_btn_pos_v = ($settings['close_button_pos_v'] == 'top') ? 'top: -' . ($settings['close_button_size'] / 2) . 'px;' : 'bottom: -' . ($settings['close_button_size'] / 2) . 'px;';

        ?>
        <style>
        #floating-slider {
            position: fixed;
            width: <?php echo esc_attr($settings['width']); ?>px;
            height: <?php echo esc_attr($settings['height']); ?>px;
            <?php echo $position_style; ?>
            z-index: 999999;
            border-radius: <?php echo $border_radius; ?>px;
            <?php echo $border_style; ?>
            box-shadow: <?php echo $shadow_color; ?> 0px 0px <?php echo $shadow_blur; ?>px;
            overflow: hidden; /* Important for border-radius on images */
            display: none; /* Hidden by default, shown by JS after delay */
            padding: <?php echo esc_attr($settings['padding']); ?>px; /* New: Internal padding */
            box-sizing: border-box; /* Include padding in width/height */
        }

        #floating-slider #slider-inner-container {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
            border-radius: <?php echo $border_radius; ?>px; /* Match outer border radius */
        }
        
        #floating-slider .slider-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
            position: absolute;
            top: 0;
            left: 0;
            border-radius: <?php echo $border_radius; ?>px; /* Match outer border radius */
        }
        
        #floating-slider .slider-image.active {
            display: block; /* Ensure the active image is visible for transitions */
            opacity: 1;
            transform: none;
        }

        /* Initial states for animations */
        .fade-transition { opacity: 0; }
        .slide-transition { transform: translateX(100%); }
        .zoom-transition { transform: scale(0); }

        #slider-close {
            position: absolute;
            <?php echo $close_btn_pos_v; ?>
            <?php echo $close_btn_pos_h; ?>
            width: <?php echo esc_attr($settings['close_button_size']); ?>px;
            height: <?php echo esc_attr($settings['close_button_size']); ?>px;
            background: <?php echo esc_attr($settings['close_button_bg']); ?>;
            color: <?php echo esc_attr($settings['close_button_color']); ?>;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: <?php echo esc_attr($settings['close_button_size'] - 8); ?>px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        #slider-close:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        
        @keyframes floating-slider-rainbow-rotate {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }
        
        @keyframes floating-slider-pulse-glow {
            0%, 100% { box-shadow: 0 0 5px #ff0080, 0 0 10px #ff0080, 0 0 15px #ff0080; }
            50% { box-shadow: 0 0 10px #ff0080, 0 0 20px #ff0080, 0 0 30px #ff0080; }
        }
        </style>
        <?php
    }

    /**
     * Renders the HTML structure for the floating slider.
     * @param array $settings Plugin settings.
     */
    private function render_slider_html($settings) {
        ?>
        <div id="floating-slider">
            <button id="slider-close" aria-label="<?php esc_attr_e('Close Slider', 'floating-slider-pro'); ?>">×</button>
            <div id="slider-inner-container">
                <?php foreach ($settings['images'] as $index => $image) : ?>
                    <a href="<?php echo esc_url($image['link']); ?>" target="_blank" rel="noopener noreferrer" class="slider-image-link">
                        <img src="<?php echo esc_url($image['url']); ?>"
                             alt="<?php esc_attr_e('Slider Image', 'floating-slider-pro'); ?> <?php echo esc_attr($index + 1); ?>"
                             class="slider-image <?php echo esc_attr($settings['animation_type']); ?>-transition <?php echo $index === 0 ? 'active' : ''; ?>"
                             style="<?php echo $index === 0 ? '' : 'display: none;'; ?>" />
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renders inline JavaScript for the floating slider functionality.
     * @param array $settings Plugin settings.
     */
    private function render_slider_js($settings) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var currentSlide = 0;
            var totalSlides = <?php echo count($settings['images']); ?>;
            var slideInterval;
            var animationType = '<?php echo esc_js($settings['animation_type']); ?>';
            var slideDuration = <?php echo esc_js($settings['slide_duration']); ?>;

            var $slider = $('#floating-slider');
            var $images = $slider.find('.slider-image');

            // Show slider after delay
            setTimeout(function() {
                $slider.fadeIn(500, function() {
                    if (totalSlides > 0) {
                        $images.eq(0).addClass('active').show(); // Ensure first image is active
                    }
                    startSlideshow();
                });
            }, <?php echo esc_js($settings['delay_show']); ?>);
            
            // Close button click handler
            $('#slider-close').on('click', function() {
                $slider.fadeOut(300, function() {
                    clearInterval(slideInterval); // Stop slideshow when closed
                });
            });

            function startSlideshow() {
                if (totalSlides <= 1) return; // No need for slideshow if 0 or 1 image
                
                slideInterval = setInterval(function() {
                    nextSlide();
                }, slideDuration);
            }
            
            function nextSlide() {
                var $currentImg = $images.eq(currentSlide);
                currentSlide = (currentSlide + 1) % totalSlides;
                var $nextImg = $images.eq(currentSlide);
                
                // Reset states for animation
                $currentImg.removeClass('active');
                $nextImg.css({
                    'opacity': 0,
                    'transform': animationType === 'slide' ? 'translateX(100%)' : (animationType === 'zoom' ? 'scale(0)' : 'opacity: 0'),
                    'display': 'block'
                });

                switch(animationType) {
                    case 'fade':
                        $currentImg.fadeOut(500);
                        $nextImg.fadeIn(500, function() {
                            $(this).addClass('active');
                        });
                        break;
                        
                    case 'slide':
                        // Use CSS transitions defined in style block
                        $currentImg.css('transform', 'translateX(-100%)'); // Slide out left
                        $nextImg.css('transform', 'translateX(0)'); // Slide in right
                        setTimeout(function() {
                             $currentImg.hide().css('transform', 'translateX(100%)'); // Reset for next cycle
                             $nextImg.addClass('active');
                        }, 500); // Allow transition to complete before hiding/resetting
                        break;
                        
                    case 'zoom':
                        // Use CSS transitions defined in style block
                        $currentImg.css('transform', 'scale(0)'); // Zoom out
                        $nextImg.css('transform', 'scale(1)'); // Zoom in
                        setTimeout(function() {
                            $currentImg.hide().css('transform', 'scale(0)'); // Reset for next cycle
                            $nextImg.addClass('active');
                        }, 500);
                        break;
                }
            }
            
            // Pause slideshow on hover
            $slider.hover(
                function() {
                    clearInterval(slideInterval);
                },
                function() {
                    startSlideshow();
                }
            );
        });
        </script>
        <?php
    }
}

// Instantiate the plugin
new FloatingSliderPlugin();