<?php
/**
 * Plugin Name: اسلایدر شناور حرفه ای
 * Description: اسلایدر تصویری شناور با قابلیت‌های کامل شخصی‌سازی
 * Version: 1.0
 * Author: Your Name
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class FloatingSliderPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_footer', array($this, 'display_slider'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_upload_slider_image', array($this, 'upload_slider_image'));
        add_action('wp_ajax_delete_slider_image', array($this, 'delete_slider_image'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        // تنظیمات اولیه
    }
    
    public function activate() {
        // ایجاد تنظیمات پیش‌فرض
        $default_settings = array(
            'enabled' => 1,
            'display_pages' => 'all',
            'specific_pages' => array(),
            'width' => 300,
            'height' => 200,
            'position_horizontal' => 'right',
            'position_vertical' => 'center',
            'horizontal_offset' => 20,
            'vertical_offset' => 0,
            'images' => array(),
            'close_button_size' => 20,
            'close_button_color' => '#ffffff',
            'close_button_bg' => '#ff0000',
            'animation_type' => 'fade',
            'slide_duration' => 3000,
            'delay_show' => 2000,
            'border_theme' => 'gradient',
            'border_radius' => 15,
            'shadow_blur' => 20,
            'shadow_color' => 'rgba(0,0,0,0.3)'
        );
        
        add_option('floating_slider_settings', $default_settings);
    }
    
    public function admin_menu() {
        add_options_page(
            'تنظیمات اسلایدر شناور',
            'اسلایدر شناور',
            'manage_options',
            'floating-slider',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('floating_slider_group', 'floating_slider_settings');
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook != 'settings_page_floating-slider') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }
    
    public function upload_slider_image() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        $link_url = sanitize_url($_POST['link_url']);
        
        $settings = get_option('floating_slider_settings', array());
        if (!isset($settings['images'])) {
            $settings['images'] = array();
        }
        
        $settings['images'][] = array(
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'link' => $link_url
        );
        
        update_option('floating_slider_settings', $settings);
        
        wp_send_json_success();
    }
    
    public function delete_slider_image() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $index = intval($_POST['index']);
        $settings = get_option('floating_slider_settings', array());
        
        if (isset($settings['images'][$index])) {
            unset($settings['images'][$index]);
            $settings['images'] = array_values($settings['images']);
            update_option('floating_slider_settings', $settings);
        }
        
        wp_send_json_success();
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $settings = array(
                'enabled' => isset($_POST['enabled']) ? 1 : 0,
                'display_pages' => sanitize_text_field($_POST['display_pages']),
                'specific_pages' => isset($_POST['specific_pages']) ? array_map('intval', $_POST['specific_pages']) : array(),
                'width' => intval($_POST['width']),
                'height' => intval($_POST['height']),
                'position_horizontal' => sanitize_text_field($_POST['position_horizontal']),
                'position_vertical' => sanitize_text_field($_POST['position_vertical']),
                'horizontal_offset' => intval($_POST['horizontal_offset']),
                'vertical_offset' => intval($_POST['vertical_offset']),
                'close_button_size' => intval($_POST['close_button_size']),
                'close_button_color' => sanitize_hex_color($_POST['close_button_color']),
                'close_button_bg' => sanitize_hex_color($_POST['close_button_bg']),
                'animation_type' => sanitize_text_field($_POST['animation_type']),
                'slide_duration' => intval($_POST['slide_duration']),
                'delay_show' => intval($_POST['delay_show']),
                'border_theme' => sanitize_text_field($_POST['border_theme']),
                'border_radius' => intval($_POST['border_radius']),
                'shadow_blur' => intval($_POST['shadow_blur']),
                'shadow_color' => sanitize_text_field($_POST['shadow_color'])
            );
            
            $old_settings = get_option('floating_slider_settings', array());
            $settings['images'] = isset($old_settings['images']) ? $old_settings['images'] : array();
            
            update_option('floating_slider_settings', $settings);
            echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد!</p></div>';
        }
        
        $settings = get_option('floating_slider_settings', array());
        $pages = get_pages();
        
        ?>
        <div class="wrap">
            <h1>تنظیمات اسلایدر شناور</h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">فعال/غیرفعال</th>
                        <td>
                            <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled'], 1); ?> />
                            <label>اسلایدر را فعال کن</label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">نمایش در صفحات</th>
                        <td>
                            <input type="radio" name="display_pages" value="all" <?php checked($settings['display_pages'], 'all'); ?> />
                            <label>همه صفحات</label><br>
                            <input type="radio" name="display_pages" value="specific" <?php checked($settings['display_pages'], 'specific'); ?> />
                            <label>صفحات خاص</label><br>
                            <select name="specific_pages[]" multiple style="height: 100px; width: 300px;">
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo $page->ID; ?>" 
                                        <?php echo in_array($page->ID, $settings['specific_pages']) ? 'selected' : ''; ?>>
                                        <?php echo $page->post_title; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">ابعاد اسلایدر</th>
                        <td>
                            عرض: <input type="number" name="width" value="<?php echo $settings['width']; ?>" /> پیکسل<br>
                            ارتفاع: <input type="number" name="height" value="<?php echo $settings['height']; ?>" /> پیکسل
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">موقعیت اسلایدر</th>
                        <td>
                            موقعیت افقی: 
                            <select name="position_horizontal">
                                <option value="left" <?php selected($settings['position_horizontal'], 'left'); ?>>چپ</option>
                                <option value="right" <?php selected($settings['position_horizontal'], 'right'); ?>>راست</option>
                            </select><br>
                            موقعیت عمودی:
                            <select name="position_vertical">
                                <option value="top" <?php selected($settings['position_vertical'], 'top'); ?>>بالا</option>
                                <option value="center" <?php selected($settings['position_vertical'], 'center'); ?>>وسط</option>
                                <option value="bottom" <?php selected($settings['position_vertical'], 'bottom'); ?>>پایین</option>
                            </select><br>
                            فاصله افقی: <input type="number" name="horizontal_offset" value="<?php echo $settings['horizontal_offset']; ?>" /> پیکسل<br>
                            فاصله عمودی: <input type="number" name="vertical_offset" value="<?php echo $settings['vertical_offset']; ?>" /> پیکسل
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">دکمه بستن</th>
                        <td>
                            اندازه: <input type="number" name="close_button_size" value="<?php echo $settings['close_button_size']; ?>" /> پیکسل<br>
                            رنگ متن: <input type="color" name="close_button_color" value="<?php echo $settings['close_button_color']; ?>" /><br>
                            رنگ پس‌زمینه: <input type="color" name="close_button_bg" value="<?php echo $settings['close_button_bg']; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">انیمیشن</th>
                        <td>
                            نوع انیمیشن:
                            <select name="animation_type">
                                <option value="fade" <?php selected($settings['animation_type'], 'fade'); ?>>محو شدن</option>
                                <option value="slide" <?php selected($settings['animation_type'], 'slide'); ?>>اسلاید</option>
                                <option value="zoom" <?php selected($settings['animation_type'], 'zoom'); ?>>زوم</option>
                            </select><br>
                            مدت زمان بین اسلایدها: <input type="number" name="slide_duration" value="<?php echo $settings['slide_duration']; ?>" /> میلی‌ثانیه<br>
                            تاخیر نمایش: <input type="number" name="delay_show" value="<?php echo $settings['delay_show']; ?>" /> میلی‌ثانیه
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">تم حاشیه</th>
                        <td>
                            <select name="border_theme">
                                <option value="gradient" <?php selected($settings['border_theme'], 'gradient'); ?>>گرادینت رنگی</option>
                                <option value="neon" <?php selected($settings['border_theme'], 'neon'); ?>>نئونی</option>
                                <option value="rainbow" <?php selected($settings['border_theme'], 'rainbow'); ?>>رنگین‌کمان</option>
                                <option value="glow" <?php selected($settings['border_theme'], 'glow'); ?>>درخشان</option>
                                <option value="pulse" <?php selected($settings['border_theme'], 'pulse'); ?>>ضربان‌دار</option>
                            </select><br>
                            شعاع گردی: <input type="number" name="border_radius" value="<?php echo $settings['border_radius']; ?>" /> پیکسل<br>
                            شدت سایه: <input type="number" name="shadow_blur" value="<?php echo $settings['shadow_blur']; ?>" /> پیکسل<br>
                            رنگ سایه: <input type="text" name="shadow_color" value="<?php echo $settings['shadow_color']; ?>" placeholder="rgba(0,0,0,0.3)" />
                        </td>
                    </tr>
                </table>
                
                <h3>مدیریت تصاویر</h3>
                <div id="slider-images">
                    <?php if (!empty($settings['images'])): ?>
                        <?php foreach ($settings['images'] as $index => $image): ?>
                            <div class="image-item" style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; display: inline-block;">
                                <img src="<?php echo $image['url']; ?>" style="width: 100px; height: 70px; object-fit: cover;" />
                                <br>لینک: <?php echo $image['link']; ?>
                                <br><button type="button" onclick="deleteImage(<?php echo $index; ?>)">حذف</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-image-btn">افزودن تصویر</button>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-image-btn').click(function() {
                var frame = wp.media({
                    title: 'انتخاب تصویر',
                    button: {
                        text: 'انتخاب'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var linkUrl = prompt('لینک تصویر را وارد کنید:', 'http://');
                    
                    if (linkUrl) {
                        $.post(ajaxurl, {
                            action: 'upload_slider_image',
                            attachment_id: attachment.id,
                            link_url: linkUrl
                        }, function() {
                            location.reload();
                        });
                    }
                });
                
                frame.open();
            });
        });
        
        function deleteImage(index) {
            if (confirm('آیا مطمئن هستید؟')) {
                jQuery.post(ajaxurl, {
                    action: 'delete_slider_image',
                    index: index
                }, function() {
                    location.reload();
                });
            }
        }
        </script>
        <?php
    }
    
    public function display_slider() {
        $settings = get_option('floating_slider_settings', array());
        
        if (!$settings['enabled'] || empty($settings['images'])) {
            return;
        }
        
        // بررسی نمایش در صفحات
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
    
    private function render_slider_css($settings) {
        $position_style = '';
        
        // موقعیت افقی
        if ($settings['position_horizontal'] == 'left') {
            $position_style .= 'left: ' . $settings['horizontal_offset'] . 'px;';
        } else {
            $position_style .= 'right: ' . $settings['horizontal_offset'] . 'px;';
        }
        
        // موقعیت عمودی
        if ($settings['position_vertical'] == 'top') {
            $position_style .= 'top: ' . $settings['vertical_offset'] . 'px;';
        } elseif ($settings['position_vertical'] == 'bottom') {
            $position_style .= 'bottom: ' . $settings['vertical_offset'] . 'px;';
        } else {
            $position_style .= 'top: 50%; margin-top: -' . ($settings['height'] / 2) . 'px; transform: translateY(' . $settings['vertical_offset'] . 'px);';
        }
        
        // تم حاشیه
        $border_style = '';
        switch ($settings['border_theme']) {
            case 'gradient':
                $border_style = 'border: 3px solid; border-image: linear-gradient(45deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3) 1;';
                break;
            case 'neon':
                $border_style = 'border: 2px solid #00ffff; box-shadow: 0 0 10px #00ffff, inset 0 0 10px #00ffff;';
                break;
            case 'rainbow':
                $border_style = 'border: 3px solid; border-image: conic-gradient(from 0deg, red, orange, yellow, green, blue, indigo, violet, red) 1; animation: rainbow-rotate 3s linear infinite;';
                break;
            case 'glow':
                $border_style = 'border: 2px solid #fff; box-shadow: 0 0 20px rgba(255,255,255,0.8);';
                break;
            case 'pulse':
                $border_style = 'border: 3px solid #ff0080; animation: pulse-glow 2s ease-in-out infinite;';
                break;
        }
        
        ?>
        <style>
        #floating-slider {
            position: fixed;
            width: <?php echo $settings['width']; ?>px;
            height: <?php echo $settings['height']; ?>px;
            <?php echo $position_style; ?>
            z-index: 999999;
            border-radius: <?php echo $settings['border_radius']; ?>px;
            <?php echo $border_style; ?>
            box-shadow: <?php echo $settings['shadow_color']; ?> 0px 0px <?php echo $settings['shadow_blur']; ?>px;
            overflow: hidden;
            display: none;
        }
        
        #floating-slider img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        #floating-slider img:hover {
            transform: scale(1.05);
        }
        
        #slider-close {
            position: absolute;
            top: -10px;
            right: -10px;
            width: <?php echo $settings['close_button_size']; ?>px;
            height: <?php echo $settings['close_button_size']; ?>px;
            background: <?php echo $settings['close_button_bg']; ?>;
            color: <?php echo $settings['close_button_color']; ?>;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: <?php echo $settings['close_button_size'] - 8; ?>px;
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
        
        @keyframes rainbow-rotate {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 5px #ff0080, 0 0 10px #ff0080, 0 0 15px #ff0080; }
            50% { box-shadow: 0 0 10px #ff0080, 0 0 20px #ff0080, 0 0 30px #ff0080; }
        }
        
        .fade-transition {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .fade-transition.active {
            opacity: 1;
        }
        
        .slide-transition {
            transform: translateX(100%);
            transition: transform 0.5s ease-in-out;
        }
        
        .slide-transition.active {
            transform: translateX(0);
        }
        
        .zoom-transition {
            transform: scale(0);
            transition: transform 0.5s ease-in-out;
        }
        
        .zoom-transition.active {
            transform: scale(1);
        }
        </style>
        <?php
    }
    
    private function render_slider_html($settings) {
        ?>
        <div id="floating-slider">
            <button id="slider-close" onclick="closeSlider()">×</button>
            <div id="slider-images">
                <?php foreach ($settings['images'] as $index => $image): ?>
                    <img src="<?php echo $image['url']; ?>" 
                         onclick="window.open('<?php echo $image['link']; ?>', '_blank')"
                         class="slider-image <?php echo $settings['animation_type']; ?>-transition <?php echo $index === 0 ? 'active' : ''; ?>"
                         style="<?php echo $index === 0 ? '' : 'display: none;'; ?>" />
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    private function render_slider_js($settings) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var currentSlide = 0;
            var totalSlides = <?php echo count($settings['images']); ?>;
            var slideInterval;
            var animationType = '<?php echo $settings['animation_type']; ?>';
            
            // نمایش اسلایدر پس از تاخیر
            setTimeout(function() {
                $('#floating-slider').fadeIn(500);
                startSlideshow();
            }, <?php echo $settings['delay_show']; ?>);
            
            function startSlideshow() {
                if (totalSlides <= 1) return;
                
                slideInterval = setInterval(function() {
                    nextSlide();
                }, <?php echo $settings['slide_duration']; ?>);
            }
            
            function nextSlide() {
                var currentImg = $('.slider-image').eq(currentSlide);
                currentSlide = (currentSlide + 1) % totalSlides;
                var nextImg = $('.slider-image').eq(currentSlide);
                
                switch(animationType) {
                    case 'fade':
                        currentImg.removeClass('active').fadeOut(300, function() {
                            nextImg.fadeIn(300).addClass('active');
                        });
                        break;
                        
                    case 'slide':
                        currentImg.removeClass('active').animate({left: '-100%'}, 300, function() {
                            $(this).hide().css('left', '100%');
                            nextImg.css('left', '100%').show().animate({left: '0'}, 300).addClass('active');
                        });
                        break;
                        
                    case 'zoom':
                        currentImg.removeClass('active').animate({
                            width: '0%',
                            height: '0%',
                            top: '50%',
                            left: '50%'
                        }, 300, function() {
                            $(this).hide().css({
                                width: '100%',
                                height: '100%',
                                top: '0',
                                left: '0'
                            });
                            nextImg.css({
                                width: '0%',
                                height: '0%',
                                top: '50%',
                                left: '50%'
                            }).show().animate({
                                width: '100%',
                                height: '100%',
                                top: '0',
                                left: '0'
                            }, 300).addClass('active');
                        });
                        break;
                }
            }
            
            // توقف اسلایدشو هنگام hover
            $('#floating-slider').hover(
                function() {
                    clearInterval(slideInterval);
                },
                function() {
                    startSlideshow();
                }
            );
        });
        
        function closeSlider() {
            jQuery('#floating-slider').fadeOut(300);
        }
        </script>
        <?php
    }
}

// راه‌اندازی پلاگین
new FloatingSliderPlugin();
?>