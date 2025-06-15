<?php
/**
 * Plugin Name: Floating Professional Slider
 * Description: Floating image slider with full customization options.
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
            'position_horizontal'   => 'right',
            'position_vertical'     => 'center',
            'horizontal_offset'     => 20,
            'vertical_offset'       => 0,
            'images'                => array(),
            'close_button_size'     => 20,
            'close_button_color'    => '#ffffff',
            'close_button_bg'       => '#ff0000',
            'close_button_pos_h'    => 'right', // New: close button horizontal position
            'close_button_offset_h' => -10,     // New: close button horizontal offset
            'close_button_pos_v'    => 'top',   // New: close button vertical position
            'close_button_offset_v' => -10,     // New: close button vertical offset
            'animation_type'        => 'fade',
            'slide_duration'        => 3000,
            'delay_show'            => 2000,
            'border_theme'          => 'gradient',
            'border_radius'         => 15,
            'shadow_blur'           => 20,
            'shadow_color'          => 'rgba(0,0,0,0.3)',
            'image_fit'             => 'cover', // New: object-fit property for images
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
        $output['shadow_color']          = sanitize_text_field($input['shadow_color']); // wpColorPicker handles various formats
        $output['image_fit']             = sanitize_text_field($input['image_fit']);

        // Images are handled via AJAX, so they are retrieved from current option
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

        // Enqueue WordPress media uploader
        wp_enqueue_media();
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        // Enqueue jQuery UI Sortable for image reordering
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'floating-slider-admin-script',
            plugin_dir_url(__FILE__) . 'admin-script.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
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

        // Check page display conditions
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

        $settings['images'][] = array(
            'id'   => $attachment_id,
            'url'  => wp_get_attachment_url($attachment_id),
            'link' => $link_url
        );

        update_option('floating_slider_settings', $settings);

        wp_send_json_success(array(
            'message' => __('Image uploaded successfully.', 'floating-slider-pro'),
            'image'   => array(
                'id'   => $attachment_id,
                'url'  => wp_get_attachment_url($attachment_id),
                'link' => $link_url
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
            $settings['images'] = array_values($settings['images']); // Re-index array
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

        $new_order = isset($_POST['order']) ? (array) $_POST['order'] : array();
        $settings = get_option('floating_slider_settings', array());
        $ordered_images = array();

        foreach ($new_order as $image_id) {
            foreach ($settings['images'] as $image) {
                if ($image['id'] == $image_id) {
                    $ordered_images[] = $image;
                    break;
                }
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

        // Ensure default values are set for new settings
        $settings = wp_parse_args($settings, array(
            'close_button_pos_h'    => 'right',
            'close_button_offset_h' => -10,
            'close_button_pos_v'    => 'top',
            'close_button_offset_v' => -10,
            'image_fit'             => 'cover',
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
                do_settings_sections('floating_slider_group'); // This line is technically for sections, but register_setting handles direct save.

                $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

                switch ($current_tab) {
                    case 'design':
                        $this->render_design_settings($settings);
                        break;
                    case 'images':
                        $this->render_image_settings($settings);
                        break;
                    case 'general':
                    default:
                        $this->render_general_settings($settings, $pages);
                        break;
                }
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
                <th scope="row"><?php esc_html_e('Slider Dimensions', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_width"><?php esc_html_e('Width:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_width" name="floating_slider_settings[width]" value="<?php echo esc_attr($settings['width']); ?>" min="50" max="1000" /> px<br>
                    <label for="floating_slider_height"><?php esc_html_e('Height:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_height" name="floating_slider_settings[height]" value="<?php echo esc_attr($settings['height']); ?>" min="50" max="1000" /> px
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
                    <input type="number" id="floating_slider_offset_h" name="floating_slider_settings[horizontal_offset]" value="<?php echo esc_attr($settings['horizontal_offset']); ?>" /> px<br>
                    <label for="floating_slider_offset_v"><?php esc_html_e('Vertical Offset:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_offset_v" name="floating_slider_settings[vertical_offset]" value="<?php echo esc_attr($settings['vertical_offset']); ?>" /> px
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Close Button', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_close_size"><?php esc_html_e('Size:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_close_size" name="floating_slider_settings[close_button_size]" value="<?php echo esc_attr($settings['close_button_size']); ?>" min="10" max="50" /> px<br>
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
                    <input type="number" id="floating_slider_close_offset_h" name="floating_slider_settings[close_button_offset_h]" value="<?php echo esc_attr($settings['close_button_offset_h']); ?>" /> px<br>
                    <label for="floating_slider_close_pos_v"><?php esc_html_e('Button Vertical Position:', 'floating-slider-pro'); ?></label>
                    <select id="floating_slider_close_pos_v" name="floating_slider_settings[close_button_pos_v]">
                        <option value="top" <?php selected($settings['close_button_pos_v'], 'top'); ?>><?php esc_html_e('Top', 'floating-slider-pro'); ?></option>
                        <option value="bottom" <?php selected($settings['close_button_pos_v'], 'bottom'); ?>><?php esc_html_e('Bottom', 'floating-slider-pro'); ?></option>
                    </select><br>
                    <label for="floating_slider_close_offset_v"><?php esc_html_e('Button Vertical Offset:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_close_offset_v" name="floating_slider_settings[close_button_offset_v]" value="<?php echo esc_attr($settings['close_button_offset_v']); ?>" /> px
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
                    <input type="number" id="floating_slider_slide_duration" name="floating_slider_settings[slide_duration]" value="<?php echo esc_attr($settings['slide_duration']); ?>" min="500" /> ms<br>
                    <label for="floating_slider_delay_show"><?php esc_html_e('Delay Show:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_delay_show" name="floating_slider_settings[delay_show]" value="<?php echo esc_attr($settings['delay_show']); ?>" min="0" /> ms
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
                    <input type="number" id="floating_slider_border_radius" name="floating_slider_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="100" /> px
                    <p class="description"><?php esc_html_e('This value also applies to image corners for harmony.', 'floating-slider-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Shadow Settings', 'floating-slider-pro'); ?></th>
                <td>
                    <label for="floating_slider_shadow_blur"><?php esc_html_e('Shadow Blur:', 'floating-slider-pro'); ?></label>
                    <input type="number" id="floating_slider_shadow_blur" name="floating_slider_settings[shadow_blur]" value="<?php echo esc_attr($settings['shadow_blur']); ?>" min="0" max="100" /> px<br>
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
        <p class="description"><?php esc_html_e('Drag and drop images to reorder them.', 'floating-slider-pro'); ?></p>

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
                            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php esc_attr_e('Slider Image', 'floating-slider-pro'); ?>" style="object-fit: <?php echo esc_attr($settings['image_fit']); ?>;" />
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
            box-sizing: content-box; /* To ensure border doesn't shrink content */
        }

        #floating-slider-pro-container .fs-slider-image {
            width: 100%;
            height: 100%;
            object-fit: <?php echo esc_attr($settings['image_fit']); ?>;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: block; /* Ensure images are block level */
            border-radius: <?php echo esc_attr($settings['border_radius']); ?>px; /* Match slider border radius */
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
            font-size: <?php echo esc_attr($settings['close_button_size'] - 8); ?>px;
            line-height: <?php echo esc_attr($settings['close_button_size']); ?>px; /* Center 'x' vertically */
            text-align: center;
            z-index: 1000000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            box-sizing: border-box; /* Include padding/border in element's total width and height */
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
        .fs-slider-image.fade-transition {
            opacity: 0;
            position: absolute; /* Needed for overlap in fade */
        }
        .fs-slider-image.fade-transition.active {
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }

        .fs-slider-image.slide-transition {
            position: absolute;
            top: 0;
            /* Start position for slide: depends on direction, handled in JS */
            transition: transform 0.5s ease-in-out;
        }
        .fs-slider-image.slide-transition.active {
            transform: translateX(0) scale(1);
        }

        .fs-slider-image.zoom-transition {
            transform: scale(0);
            position: absolute; /* Needed for overlap */
            transition: transform 0.5s ease-in-out;
        }
        .fs-slider-image.zoom-transition.active {
            transform: scale(1);
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
            <button id="fs-slider-close" onclick="closeFloatingSlider()">×</button>
            <div id="fs-slider-images">
                <?php foreach ($settings['images'] as $index => $image): ?>
                    <img src="<?php echo esc_url($image['url']); ?>"
                         alt="<?php esc_attr_e('Slider Image', 'floating-slider-pro'); ?>"
                         onclick="window.open('<?php echo esc_url($image['link']); ?>', '_blank')"
                         class="fs-slider-image <?php echo esc_attr($settings['animation_type']); ?>-transition <?php echo $index === 0 ? 'active' : ''; ?>"
                         style="<?php echo $index === 0 ? '' : 'display: none;'; ?>" />
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the slider's JavaScript dynamically.
     * This JS will now be in public-script.js.
     */
    private function render_slider_js($settings) {
        // No inline JS here. All front-end JS is moved to public-script.js and localized.
    }

    /**
     * Display the slider on the front end if enabled and conditions met.
     */
    public function display_slider() {
        $settings = get_option('floating_slider_settings', array());

        if (!$settings['enabled'] || empty($settings['images'])) {
            return;
        }

        // Check page display conditions
        if ($settings['display_pages'] == 'specific') {
            $current_page_id = get_queried_object_id();
            if (!in_array($current_page_id, $settings['specific_pages'])) {
                return;
            }
        }

        $this->render_slider_css($settings);
        $this->render_slider_html($settings);
        // The JS is now enqueued via wp_enqueue_scripts.
    }
}

// Initialize the plugin
new FloatingSliderPlugin();