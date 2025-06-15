<?php
/**
 * Plugin Name: Floating Professional Slider
 * Description: Floating image slider with full customization options.
 * Version: 1.3
 * Author: DrKhaste
 * Text Domain: floating-slider-pro
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FloatingSliderPlugin {

    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'display_slider'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_fs_upload_slider_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_fs_delete_slider_image', array($this, 'handle_image_delete'));
        add_action('wp_ajax_fs_update_slider_image_order', array($this, 'handle_image_order_update'));
        add_action('wp_ajax_fs_update_slider_image_link', array($this, 'handle_image_link_update'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Load plugin textdomain for internationalization.
     */
    public function load_textdomain() {
        load_plugin_textdomain('floating-slider-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Plugin activation hook.
     * Sets default options if they don't exist.
     */
    public function activate() {
        $default_settings = array(
            'enabled'               => 1,
            'display_pages'         => 'all',
            'specific_pages'        => array(),
            'width'                 => 300,
            'height'                => 200,
            'mobile_width'          => 200, // New default for mobile
            'mobile_height'         => 150, // New default for mobile
            'position_horizontal'   => 'right',
            'position_vertical'     => 'center',
            'horizontal_offset'     => 20,
            'vertical_offset'       => 0,
            'images'                => array(),
            'close_button_size'     => 30,
            'close_button_color'    => '#ffffff',
            'close_button_bg'       => '#ff0000',
            'close_button_pos_h'    => 'right',
            'close_button_offset_h' => -15,
            'close_button_pos_v'    => 'top',
            'close_button_offset_v' => -15,
            'animation_type'        => 'fade',
            'slide_duration'        => 3000,
            'delay_show'            => 2000,
            'border_theme'          => 'gradient',
            'border_radius'         => 15,
            'shadow_blur'           => 20,
            'shadow_color'          => 'rgba(0,0,0,0.3)',
            'image_fit'             => 'cover',
        );

        add_option('floating_slider_settings', $default_settings);
    }

    /**
     * Add options page to the admin menu.
     */
    public function admin_menu() {
        add_options_page(
            __('Floating Slider Settings', 'floating-slider-pro'),
            __('Floating Slider', 'floating-slider-pro'),
            'manage_options',
            'floating-slider',
            array($this, 'admin_page_html')
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('floating_slider_group', 'floating_slider_settings', array($this, 'sanitize_settings'));
    }

    /**
     * Sanitize plugin settings on save.
     *
     * @param array $input The raw input settings.
     * @return array The sanitized settings.
     */
    public function sanitize_settings($input) {
        $output = get_option('floating_slider_settings', array());

        $output['enabled']               = isset($input['enabled']) ? 1 : 0;
        $output['display_pages']         = sanitize_text_field($input['display_pages']);
        $output['specific_pages']        = isset($input['specific_pages']) ? array_map('intval', (array) $input['specific_pages']) : array();
        $output['width']                 = intval($input['width']);
        $output['height']                = intval($input['height']);
        $output['mobile_width']          = intval($input['mobile_width']); // Sanitize new mobile setting
        $output['mobile_height']         = intval($input['mobile_height']); // Sanitize new mobile setting
        $output['position_horizontal']   = sanitize_text_field($input['position_horizontal']);
        $output['position_vertical']     = sanitize_text_field($input['position_vertical']);
        $output['horizontal_offset']     = intval($input['horizontal_offset']);
        $output['vertical_offset']       = intval($input['vertical_offset']);
        $output['close_button_size']     = intval($input['close_button_size']);
        $output['close_button_color']    = sanitize_hex_color($input['close_button_color']);
        $output['close_button_bg']       = sanitize_hex_color($input['close_button_bg']);
        $output['close_button_pos_h']    = sanitize_text_field($input['close_button_pos_h']);
        $output['close_button_offset_h'] = intval($input['close_button_offset_h']);
        $output['close_button_pos_v']    = sanitize_text_field($input['close_button_pos_v']);
        $output['close_button_offset_v'] = intval($input['close_button_offset_v']);
        $output['animation_type']        = sanitize_text_field($input['animation_type']);
        $output['slide_duration']        = intval($input['slide_duration']);
        $output['delay_show']            = intval($input['delay_show']);
        $output['border_theme']          = sanitize_text_field($input['border_theme']);
        $output['border_radius']         = intval($input['border_radius']);
        $output['shadow_blur']           = intval($input['shadow_blur']);
        $output['shadow_color']          = sanitize_text_field($input['shadow_color']);
        $output['image_fit']             = sanitize_text_field($input['image_fit']);

        if (!isset($output['images'])) {
            $output['images'] = array();
        }
        
        add_settings_error('floating_slider_settings', 'settings_updated', __('Settings saved successfully!', 'floating-slider-pro'), 'success');

        return $output;
    }

    /**
     * Enqueue scripts and styles for the admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'settings_page_floating-slider') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-slider'); // Enqueue jQuery UI Slider

        wp_enqueue_script(
            'floating-slider-admin-script',
            plugin_dir_url(__FILE__) . 'admin-script.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-ui-slider'), // Add jquery-ui-slider dependency
            null,
            true
        );

        wp_enqueue_style(
            'floating-slider-admin-style',
            plugin_dir_url(__FILE__) . 'admin-style.css',
            array(),
            null
        );

        wp_localize_script(
            'floating-slider-admin-script',
            'floatingSliderAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('floating-slider-nonce'),
                'messages' => array(
                    'enter_image_link' => __('Please enter image link:', 'floating-slider-pro'),
                    'confirm_delete'   => __('Are you sure you want to delete this image?', 'floating-slider-pro'),
                    'no_images_yet'    => __('No images added yet. Click "Add Image" to start.', 'floating-slider-pro'),
                )
            )
        );
    }

    /**
     * Enqueue scripts for the public-facing slider.
     */
    public function enqueue_public_scripts() {
        $settings = get_option('floating_slider_settings', array());

        if (!$settings['enabled'] || empty($settings['images'])) {
            return;
        }

        if ($settings['display_pages'] == 'specific') {
            $current_page_id = get_queried_object_id();
            if (!in_array($current_page_id, $settings['specific_pages'])) {
                return;
            }
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'floating-slider-public-script',
            plugin_dir_url(__FILE__) . 'public-script.js',
            array('jquery'),
            null,
            true
        );
        wp_localize_script(
            'floating-slider-public-script',
            'floatingSliderData',
            array(
                'delayShow'     => $settings['delay_show'],
                'slideDuration' => $settings['slide_duration'],
                'animationType' => $settings['animation_type'],
                'totalSlides'   => count($settings['images']),
                'mobileWidth'   => $settings['mobile_width'], // Pass mobile settings to public script
                'mobileHeight'  => $settings['mobile_height'], // Pass mobile settings to public script
            )
        );
    }

    /**
     * Handle AJAX image upload.
     */
    public function handle_image_upload() {
        check_ajax_referer('floating-slider-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $attachment_id = intval($_POST['attachment_id']);
        $link_url      = isset($_POST['link_url']) ? esc_url_raw($_POST['link_url']) : '';

        $settings = get_option('floating_slider_settings', array());
        if (!isset($settings['images'])) {
            $settings['images'] = array();
        }

        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        if (!$image_url) {
            $image_url = wp_get_attachment_url($attachment_id);
        }

        $settings['images'][] = array(
            'id'   => $attachment_id,
            'url'  => $image_url,
            'link' => $link_url
        );

        update_option('floating_slider_settings', $settings);

        wp_send_json_success(array(
            'message' => __('Image uploaded successfully.', 'floating-slider-pro'),
            'image'   => array(
                'id'   => $attachment_id,
                'url'  => $image_url,
                'link' => $link_url,
                'original_dimensions' => wp_get_attachment_metadata($attachment_id) ? wp_get_attachment_metadata($attachment_id)['width'] . 'x' . wp_get_attachment_metadata($attachment_id)['height'] . 'px' : '',
            )
        ));
    }

    /**
     * Handle AJAX image deletion.
     */
    public function handle_image_delete() {
        check_ajax_referer('floating-slider-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $index = intval($_POST['index']);
        $settings = get_option('floating_slider_settings', array());

        if (isset($settings['images'][$index])) {
            unset($settings['images'][$index]);
            $settings['images'] = array_values($settings['images']);
            update_option('floating_slider_settings', $settings);
            wp_send_json_success(array('message' => __('Image deleted successfully.', 'floating-slider-pro')));
        } else {
            wp_send_json_error(array('message' => __('Image not found.', 'floating-slider-pro')));
        }
    }

    /**
     * Handle AJAX image order update.
     */
    public function handle_image_order_update() {
        check_ajax_referer('floating-slider-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $new_order_ids = isset($_POST['order']) ? (array) $_POST['order'] : array();
        $settings = get_option('floating_slider_settings', array());
        $ordered_images = array();

        $image_map = [];
        foreach ($settings['images'] as $image) {
            $image_map[$image['id']] = $image;
        }

        foreach ($new_order_ids as $image_id) {
            if (isset($image_map[$image_id])) {
                $ordered_images[] = $image_map[$image_id];
            }
        }

        $settings['images'] = $ordered_images;
        update_option('floating_slider_settings', $settings);
        wp_send_json_success(array('message' => __('Image order updated successfully.', 'floating-slider-pro')));
    }

    /**
     * Handle AJAX image link update.
     */
    public function handle_image_link_update() {
        check_ajax_referer('floating-slider-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'floating-slider-pro')));
        }

        $index = intval($_POST['index']);
        $new_link = isset($_POST['link']) ? esc_url_raw($_POST['link']) : '';

        $settings = get_option('floating_slider_settings', array());

        if (isset($settings['images'][$index])) {
            $settings['images'][$index]['link'] = $new_link;
            update_option('floating_slider_settings', $settings);
            wp_send_json_success(array('message' => __('Image link updated successfully.', 'floating-slider-pro')));
        } else {
            wp_send_json_error(array('message' => __('Image not found.', 'floating-slider-pro')));
        }
    }

    /**
     * Render the admin page HTML.
     */
    public function admin_page_html() {
        $settings = get_option('floating_slider_settings', array());
        $pages = get_pages();

        $settings = wp_parse_args($settings, array(
            'mobile_width'          => 200,
            'mobile_height'         => 150,
            'close_button_pos_h'    => 'right',
            'close_button_offset_h' => -15,
            'close_button_pos_v'    => 'top',
            'close_button_offset_v' => -15,
            'image_fit'             => 'cover',
            'close_button_size'     => 30,
        ));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Floating Slider Settings', 'floating-slider-pro'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=floating-slider&tab=general" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'general') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'floating-slider-pro'); ?></a>
                <a href="?page=floating-slider&tab=design" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'design') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Design', 'floating-slider-pro'); ?></a>
                <a href="?page=floating-slider&tab=images" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'images') ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Images', 'floating-slider-pro'); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('floating_slider_group');

                $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

                echo '<div class="tab-content-wrapper">';
                echo '<div id="general-tab-content" class="tab-content" ' . ($current_tab == 'general' ? '' : 'style="display:none;"') . '>';
                $this->render_general_settings($settings, $pages);
                echo '</div>';

                echo '<div id="design-tab-content" class="tab-content" ' . ($current_tab == 'design' ? '' : 'style="display:none;"') . '>';
                $this->render_design_settings($settings);
                echo '</div>';

                echo '<div id="images-tab-content" class="tab-content" ' . ($current_tab == 'images' ? '' : 'style="display:none;"') . '>';
                $this->render_image_settings($settings);
                echo '</div>';
                echo '</div>';

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders the general settings tab.
     */
    private function render_general_settings($settings, $pages) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Enable/Disable', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_enabled">
                        <input type="checkbox" id="floating_slider_enabled" name="floating_slider_settings[enabled]" value="1" <?php checked($settings['enabled'], 1); ?> />
                        <?php esc_html_e('Enable Slider', 'floating-slider-pro'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Display On Pages', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_display_all">
                        <input type="radio" id="floating_slider_display_all" name="floating_slider_settings[display_pages]" value="all" <?php checked($settings['display_pages'], 'all'); ?> />
                        <?php esc_html_e('All Pages', 'floating-slider-pro'); ?>
                    </label><br>
                    <label for="floating_slider_display_specific">
                        <input type="radio" id="floating_slider_display_specific" name="floating_slider_settings[display_pages]" value="specific" <?php checked($settings['display_pages'], 'specific'); ?> />
                        <?php esc_html_e('Specific Pages', 'floating-slider-pro'); ?>
                    </label><br>
                    <select name="floating_slider_settings[specific_pages][]" multiple style="height: 100px; width: 300px;">
                        <?php foreach ($pages as $page): ?>
                            <option value="<?php echo esc_attr($page->ID); ?>"
                                <?php echo in_array($page->ID, $settings['specific_pages']) ? 'selected' : ''; ?>>
                                <?php echo esc_html($page->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Hold CTRL/CMD to select multiple pages.', 'floating-slider-pro'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Desktop Slider Dimensions', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_width"><?php esc_html_e('Width:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_width" name="floating_slider_settings[width]" value="<?php echo esc_attr($settings['width']); ?>" min="50" max="1000" class="numeric-slider-input" data-min="50" data-max="1000" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_height"><?php esc_html_e('Height:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_height" name="floating_slider_settings[height]" value="<?php echo esc_attr($settings['height']); ?>" min="50" max="1000" class="numeric-slider-input" data-min="50" data-max="1000" data-step="1" /> px
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Mobile Slider Dimensions', 'floating-slider-pro'); ?></th>
                <td>
                    <p class="description"><?php esc_html_e('These settings apply on screens smaller than 768px.', 'floating-slider-pro'); ?></p>
                    <label for="floating_slider_mobile_width"><?php esc_html_e('Mobile Width:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_mobile_width" name="floating_slider_settings[mobile_width]" value="<?php echo esc_attr($settings['mobile_width']); ?>" min="50" max="500" class="numeric-slider-input" data-min="50" data-max="500" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_mobile_height"><?php esc_html_e('Mobile Height:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_mobile_height" name="floating_slider_settings[mobile_height]" value="<?php echo esc_attr($settings['mobile_height']); ?>" min="50" max="500" class="numeric-slider-input" data-min="50" data-max="500" data-step="1" /> px
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Slider Position', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_pos_h"><?php esc_html_e('Horizontal Position:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_pos_h" name="floating_slider_settings[position_horizontal]">
                        <option value="left" <?php selected($settings['position_horizontal'], 'left'); ?>><?php esc_html_e('Left', 'floating-slider-pro'); ?></option>
                        <option value="right" <?php selected($settings['position_horizontal'], 'right'); ?>><?php esc_html_e('Right', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_pos_v"><?php esc_html_e('Vertical Position:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_pos_v" name="floating_slider_settings[position_vertical]">
                        <option value="top" <?php selected($settings['position_vertical'], 'top'); ?>><?php esc_html_e('Top', 'floating-slider-pro'); ?></option>
                        <option value="center" <?php selected($settings['position_vertical'], 'center'); ?>><?php esc_html_e('Center', 'floating-slider-pro'); ?></option>
                        <option value="bottom" <?php selected($settings['position_vertical'], 'bottom'); ?>><?php esc_html_e('Bottom', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_offset_h"><?php esc_html_e('Horizontal Offset:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_offset_h" name="floating_slider_settings[horizontal_offset]" value="<?php echo esc_attr($settings['horizontal_offset']); ?>" class="numeric-slider-input" data-min="-500" data-max="500" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_offset_v"><?php esc_html_e('Vertical Offset:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_offset_v" name="floating_slider_settings[vertical_offset]" value="<?php echo esc_attr($settings['vertical_offset']); ?>" class="numeric-slider-input" data-min="-500" data-max="500" data-step="1" /> px
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Close Button', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_close_size"><?php esc_html_e('Size:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_close_size" name="floating_slider_settings[close_button_size]" value="<?php echo esc_attr($settings['close_button_size']); ?>" min="10" max="50" class="numeric-slider-input" data-min="10" data-max="50" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_close_color"><?php esc_html_e('Text Color:', 'floating-slider-pro'); ?></label>
                    <input type="text" id="floating_slider_close_color" name="floating_slider_settings[close_button_color]" value="<?php echo esc_attr($settings['close_button_color']); ?>" class="color-picker" /><br>
                    <label for="floating_slider_close_bg"><?php esc_html_e('Background Color:', 'floating-slider-pro'); ?></label>
                    <input type="text" id="floating_slider_close_bg" name="floating_slider_settings[close_button_bg]" value="<?php echo esc_attr($settings['close_button_bg']); ?>" class="color-picker" /><br>
                    <label for="floating_slider_close_pos_h"><?php esc_html_e('Button Horizontal Position:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_close_pos_h" name="floating_slider_settings[close_button_pos_h]">
                        <option value="left" <?php selected($settings['close_button_pos_h'], 'left'); ?>><?php esc_html_e('Left', 'floating-slider-pro'); ?></option>
                        <option value="right" <?php selected($settings['close_button_pos_h'], 'right'); ?>><?php esc_html_e('Right', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_close_offset_h"><?php esc_html_e('Button Horizontal Offset:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_close_offset_h" name="floating_slider_settings[close_button_offset_h]" value="<?php echo esc_attr($settings['close_button_offset_h']); ?>" class="numeric-slider-input" data-min="-100" data-max="100" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_close_pos_v"><?php esc_html_e('Button Vertical Position:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_close_pos_v" name="floating_slider_settings[close_button_pos_v]">
                        <option value="top" <?php selected($settings['close_button_pos_v'], 'top'); ?>><?php esc_html_e('Top', 'floating-slider-pro'); ?></option>
                        <option value="bottom" <?php selected($settings['close_button_pos_v'], 'bottom'); ?>><?php esc_html_e('Bottom', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_close_offset_v"><?php esc_html_e('Button Vertical Offset:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_close_offset_v" name="floating_slider_settings[close_button_offset_v]" value="<?php echo esc_attr($settings['close_button_offset_v']); ?>" class="numeric-slider-input" data-min="-100" data-max="100" data-step="1" /> px
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Animation', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_anim_type"><?php esc_html_e('Animation Type:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_anim_type" name="floating_slider_settings[animation_type]">
                        <option value="fade" <?php selected($settings['animation_type'], 'fade'); ?>><?php esc_html_e('Fade', 'floating-slider-pro'); ?></option>
                        <option value="slide" <?php selected($settings['animation_type'], 'slide'); ?>><?php esc_html_e('Slide', 'floating-slider-pro'); ?></option>
                        <option value="zoom" <?php selected($settings['animation_type'], 'zoom'); ?>><?php esc_html_e('Zoom', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_slide_duration"><?php esc_html_e('Slide Duration:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_slide_duration" name="floating_slider_settings[slide_duration]" value="<?php echo esc_attr($settings['slide_duration']); ?>" min="500" class="numeric-slider-input" data-min="500" data-max="10000" data-step="100" /> ms
                    </div><br>
                    <label for="floating_slider_delay_show"><?php esc_html_e('Delay Show:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_delay_show" name="floating_slider_settings[delay_show]" value="<?php echo esc_attr($settings['delay_show']); ?>" min="0" class="numeric-slider-input" data-min="0" data-max="10000" data-step="100" /> ms
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renders the design settings tab.
     */
    private function render_design_settings($settings) {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Border Theme', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_border_theme"><?php esc_html_e('Theme:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_border_theme" name="floating_slider_settings[border_theme]">
                        <option value="none" <?php selected($settings['border_theme'], 'none'); ?>><?php esc_html_e('None', 'floating-slider-pro'); ?></option>
                        <option value="gradient" <?php selected($settings['border_theme'], 'gradient'); ?>><?php esc_html_e('Gradient', 'floating-slider-pro'); ?></option>
                        <option value="neon" <?php selected($settings['border_theme'], 'neon'); ?>><?php esc_html_e('Neon', 'floating-slider-pro'); ?></option>
                        <option value="rainbow" <?php selected($settings['border_theme'], 'rainbow'); ?>><?php esc_html_e('Rainbow', 'floating-slider-pro'); ?></option>
                        <option value="glow" <?php selected($settings['border_theme'], 'glow'); ?>><?php esc_html_e('Glow', 'floating-slider-pro'); ?></option>
                        <option value="pulse" <?php selected($settings['border_theme'], 'pulse'); ?>><?php esc_html_e('Pulse', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_border_radius"><?php esc_html_e('Border Radius:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_border_radius" name="floating_slider_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="100" class="numeric-slider-input" data-min="0" data-max="100" data-step="1" /> px
                    </div>
                    <p class="description"><?php esc_html_e('This value also applies to image corners for harmony.', 'floating-slider-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Shadow Settings', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_shadow_blur"><?php esc_html_e('Shadow Blur:', 'floating-slider-pro'); ?></label>
                    <div class="numeric-slider-wrapper">
                        <input type="number" id="floating_slider_shadow_blur" name="floating_slider_settings[shadow_blur]" value="<?php echo esc_attr($settings['shadow_blur']); ?>" min="0" max="100" class="numeric-slider-input" data-min="0" data-max="100" data-step="1" /> px
                    </div><br>
                    <label for="floating_slider_shadow_color"><?php esc_html_e('Shadow Color:', 'floating-slider-pro'); ?></label>
                    <input type="text" id="floating_slider_shadow_color" name="floating_slider_settings[shadow_color]" value="<?php echo esc_attr($settings['shadow_color']); ?>" class="color-picker" data-alpha="true" />
                    <p class="description"><?php esc_html_e('Use hex, RGB, or RGBA for transparency.', 'floating-slider-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Image Fit', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_image_fit"><?php esc_html_e('How images fit the frame:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_image_fit" name="floating_slider_settings[image_fit]">
                        <option value="fill" <?php selected($settings['image_fit'], 'fill'); ?>><?php esc_html_e('Fill (distort if needed)', 'floating-slider-pro'); ?></option>
                        <option value="contain" <?php selected($settings['image_fit'], 'contain'); ?>><?php esc_html_e('Contain (letterbox if needed)', 'floating-slider-pro'); ?></option>
                        <option value="cover" <?php selected($settings['image_fit'], 'cover'); ?>><?php esc_html_e('Cover (crop if needed)', 'floating-slider-pro'); ?></option>
                        <option value="none" <?php selected($settings['image_fit'], 'none'); ?>><?php esc_html_e('None (original size)', 'floating-slider-pro'); ?></option>
                        <option value="scale-down" <?php selected($settings['image_fit'], 'scale-down'); ?>><?php esc_html_e('Scale Down (smallest of none/contain)', 'floating-slider-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Determines how the image should be resized to fit its container.', 'floating-slider-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renders the image management tab.
     */
    private function render_image_settings($settings) {
        ?>
        <h3><?php esc_html_e('Manage Images', 'floating-slider-pro'); ?></h3>
        <button type="button" id="fs-add-image-btn" class="button button-primary"><?php esc_html_e('Add Image', 'floating-slider-pro'); ?></button>
        <p class="description"><?php esc_html_e('Drag and drop images to reorder them. Click "Edit Link" to change the destination URL for each image.', 'floating-slider-pro'); ?></p>

        <ul id="fs-slider-images-list" class="fs-sortable-images">
            <?php if (!empty($settings['images'])): ?>
                <?php foreach ($settings['images'] as $index => $image):
                    $attachment_meta = wp_get_attachment_metadata($image['id']);
                    $image_dimensions = '';
                    if (!empty($attachment_meta['width']) && !empty($attachment_meta['height'])) {
                        $image_dimensions = $attachment_meta['width'] . 'x' . $attachment_meta['height'] . 'px';
                    }
                ?>
                    <li class="fs-image-item" data-attachment-id="<?php echo esc_attr($image['id']); ?>" data-index="<?php echo esc_attr($index); ?>">
                        <div class="fs-image-preview" style="width: <?php echo esc_attr($settings['width']); ?>px; height: <?php echo esc_attr($settings['height']); ?>px;">
                            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php esc_attr_e('Slider Image', 'floating-slider-pro'); ?>" style="object-fit: <?php echo esc_attr($settings['image_fit']); ?>; border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;" />
                        </div>
                        <div class="fs-image-details">
                            <strong><?php esc_html_e('Link:', 'floating-slider-pro'); ?></strong>
                            <span class="fs-image-link-display"><?php echo esc_url($image['link']); ?></span>
                            <input type="url" class="fs-image-link-input" value="<?php echo esc_attr($image['link']); ?>" style="display: none;" />
                            <button type="button" class="fs-edit-link-btn button button-small"><?php esc_html_e('Edit Link', 'floating-slider-pro'); ?></button>
                            <button type="button" class="fs-save-link-btn button button-small button-primary" style="display: none;"><?php esc_html_e('Save Link', 'floating-slider-pro'); ?></button>
                            <p class="fs-image-info"><?php esc_html_e('Original Dimensions:', 'floating-slider-pro'); ?> <?php echo esc_html($image_dimensions); ?></p>
                            <button type="button" class="fs-delete-image-btn button button-small button-danger"><?php esc_html_e('Delete', 'floating-slider-pro'); ?></button>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p id="no-images-message"><?php esc_html_e('No images added yet. Click "Add Image" to start.', 'floating-slider-pro'); ?></p>
            <?php endif; ?>
        </ul>
        <?php
    }

    /**
     * Render the slider's CSS dynamically based on settings.
     *
     * @param array $settings The plugin settings.
     */
    private function render_slider_css($settings) {
        $position_style = '';

        // Horizontal position
        if ($settings['position_horizontal'] == 'left') {
            $position_style .= 'left: ' . $settings['horizontal_offset'] . 'px;';
        } else {
            $position_style .= 'right: ' . $settings['horizontal_offset'] . 'px;';
        }

        // Vertical position
        if ($settings['position_vertical'] == 'top') {
            $position_style .= 'top: ' . $settings['vertical_offset'] . 'px;';
        } elseif ($settings['position_vertical'] == 'bottom') {
            $position_style .= 'bottom: ' . $settings['vertical_offset'] . 'px;';
        } else { // center
            $position_style .= 'top: 50%; transform: translateY(-50%) translateY(' . $settings['vertical_offset'] . 'px);';
        }

        // Close button position
        $close_btn_pos_style = '';
        if ($settings['close_button_pos_h'] == 'left') {
            $close_btn_pos_style .= 'left: ' . $settings['close_button_offset_h'] . 'px;';
        } else { // right
            $close_btn_pos_style .= 'right: ' . $settings['close_button_offset_h'] . 'px;';
        }
        if ($settings['close_button_pos_v'] == 'top') {
            $close_btn_pos_style .= 'top: ' . $settings['close_button_offset_v'] . 'px;';
        } else { // bottom
            $close_btn_pos_style .= 'bottom: ' . $settings['close_button_offset_v'] . 'px;';
        }


        // Border theme
        $border_style = '';
        $border_width = '3px'; // Default border width for themes
        switch ($settings['border_theme']) {
            case 'gradient':
                $border_style = 'border: ' . $border_width . ' solid; border-image: linear-gradient(45deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3) 1;';
                break;
            case 'neon':
                $border_width = '2px';
                $border_style = 'border: ' . $border_width . ' solid #00ffff; box-shadow: 0 0 10px #00ffff, inset 0 0 10px #00ffff;';
                break;
            case 'rainbow':
                $border_style = 'border: ' . $border_width . ' solid; border-image: conic-gradient(from 0deg, red, orange, yellow, green, blue, indigo, violet, red) 1; animation: fs-rainbow-rotate 3s linear infinite;';
                break;
            case 'glow':
                $border_width = '2px';
                $border_style = 'border: ' . $border_width . ' solid #fff; box-shadow: 0 0 20px rgba(255,255,255,0.8);';
                break;
            case 'pulse':
                $border_style = 'border: ' . $border_width . ' solid #ff0080; animation: fs-pulse-glow 2s ease-in-out infinite;';
                break;
            case 'none':
            default:
                $border_style = 'border: none;';
                break;
        }

        ?>
        <style>
        #floating-slider-pro-container {
            position: fixed;
            width: <?php echo esc_attr($settings['width']); ?>px;
            height: <?php echo esc_attr($settings['height']); ?>px;
            <?php echo $position_style; ?>
            z-index: 999999;
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
            <?php echo $border_style; ?>
            box-shadow: 0px 0px <?php echo esc_attr($settings['shadow_blur']); ?>px <?php echo esc_attr($settings['shadow_color']); ?>;
            overflow: hidden;
            display: none; /* Hidden by default, shown by JS */
            box-sizing: content-box;
        }

        #floating-slider-pro-container .fs-slider-image {
            width: 100%;
            height: 100%;
            object-fit: <?php echo esc_attr($settings['image_fit']); ?>;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: block;
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px;
        }

        #floating-slider-pro-container .fs-slider-image:hover {
            transform: scale(1.05);
        }

        #fs-slider-close {
            position: absolute;
            <?php echo $close_btn_pos_style; ?>
            width: <?php echo esc_attr($settings['close_button_size']); ?>px;
            height: <?php echo esc_attr($settings['close_button_size']); ?>px;
            background: <?php echo esc_attr($settings['close_button_bg']); ?>;
            color: <?php echo esc_attr($settings['close_button_color']); ?>;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: <?php echo esc_attr($settings['close_button_size'] * 0.7); ?>px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        #fs-slider-close:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }

        /* Animation Keyframes */
        @keyframes fs-rainbow-rotate {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        @keyframes fs-pulse-glow {
            0%, 100% { box-shadow: 0 0 5px #ff0080, 0 0 10px #ff0080, 0 0 15px #ff0080; }
            50% { box-shadow: 0 0 10px #ff0080, 0 0 20px #ff0080, 0 0 30px #ff0080; }
        }

        /* Animation classes for JS */
        /* Fade animation is handled by jQuery fadeOut/fadeIn */
        .fs-slider-image.active {
            display: block;
        }
        .fs-slider-image.inactive {
            display: none;
        }

        /* Responsive styles for mobile */
        @media (max-width: 767px) {
            #floating-slider-pro-container {
                width: <?php echo esc_attr($settings['mobile_width']); ?>px;
                height: <?php echo esc_attr($settings['mobile_height']); ?>px;
                /* Adjust position for mobile if needed, e.g., always center */
                left: 50% !important; /* Override desktop left/right */
                right: auto !important; /* Override desktop left/right */
                transform: translateX(-50%) translateY(-50%) translateY(<?php echo esc_attr($settings['vertical_offset']); ?>px) !important;
                top: 50% !important; /* Override top/bottom */
            }
            #fs-slider-close {
                /* Adjust close button position for mobile if it's too close to edges */
                right: 0px !important; /* Example: stick to top right on mobile */
                top: 0px !important;
                transform: translate(50%, -50%) !important; /* Move outside the container */
            }
        }
        </style>
        <?php
    }

    /**
     * Render the slider's HTML structure.
     *
     * @param array $settings The plugin settings.
     */
    private function render_slider_html($settings) {
        ?>
        <div id="floating-slider-pro-container">
            <button id="fs-slider-close">×</button>
            <div id="fs-slider-images">
                <?php foreach ($settings['images'] as $index => $image): ?>
                    <img src="<?php echo esc_url($image['url']); ?>"
                         alt="<?php esc_attr_e('Slider Image', 'floating-slider-pro'); ?>"
                         data-link="<?php echo esc_url($image['link']); ?>"
                         class="fs-slider-image <?php echo $index === 0 ? 'active' : 'inactive'; ?>" />
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display the slider on the front end if enabled and conditions met.
     */
    public function display_slider() {
        $settings = get_option('floating_slider_settings', array());

        if (!$settings['enabled'] || empty($settings['images'])) {
            return;
        }

        if ($settings['display_pages'] == 'specific') {
            $current_page_id = get_queried_object_id();
            if (!in_array($current_page_id, $settings['specific_pages'])) {
                return;
            }
        }

        $this->render_slider_css($settings);
        $this->render_slider_html($settings);
    }
}

// Initialize the plugin
new FloatingSliderPlugin();