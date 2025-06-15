jQuery(document).ready(function($) {
    // Initialize WordPress Color Picker
    $('.color-picker').wpColorPicker({
        palettes: true,
        change: function(event, ui) {
            if ($(this).data('alpha')) {
                var newColor = ui.color.toString();
                // Ensure it's rgba for proper saving if alpha is used
                if (ui.color.alpha() < 1 && !newColor.includes('rgba')) {
                    newColor = ui.color.toRgbString();
                }
                $(this).val(newColor);
            }
        },
        clear: function() {
            if ($(this).data('alpha')) {
                $(this).val(''); // Clear value on clear button
            }
        }
    });

    // Custom Numeric Slider Functionality
    // This creates a visual slider next to each numeric input with class 'numeric-slider-input'
    $('.numeric-slider-input').each(function() {
        var $input = $(this);
        var min = parseFloat($input.data('min') || $input.attr('min') || 0);
        var max = parseFloat($input.data('max') || $input.attr('max') || 100);
        var step = parseFloat($input.data('step') || $input.attr('step') || 1);
        var initialValue = parseFloat($input.val());

        // Clamp initial value to min/max
        if (initialValue < min) initialValue = min;
        if (initialValue > max) initialValue = max;
        $input.val(initialValue);

        var $slider = $('<input type="range" class="slider">')
            .attr('min', min)
            .attr('max', max)
            .attr('step', step)
            .val(initialValue);

        $input.wrap('<div class="numeric-slider-wrapper"></div>').after($slider);

        $slider.on('input', function() {
            $input.val($(this).val());
        });

        $input.on('input', function() {
            var val = parseFloat($(this).val());
            if (isNaN(val)) val = min; // Default if input is empty or invalid
            if (val < min) val = min;
            if (val > max) val = max;
            $(this).val(val); // Update input field (clamped)
            $slider.val(val); // Update slider
        });
    });


    // Handle Add Image button click
    $('#fs-add-image-btn').on('click', function(event) {
        event.preventDefault();

        var frame = wp.media({
            title: 'Select or Upload Image',
            button: {
                text: 'Use this image'
            },
            multiple: false // Allow selection of only one image
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var linkUrl = prompt(floatingSliderAjax.messages.enter_image_link, attachment.url); // Pre-fill with image URL

            if (linkUrl !== null) { // If user didn't cancel prompt
                // Show message if no images were present
                $('#no-images-message').remove();

                $.ajax({
                    url: floatingSliderAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fs_upload_slider_image',
                        attachment_id: attachment.id,
                        link_url: linkUrl,
                        nonce: floatingSliderAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var imageData = response.data.image;
                            var currentImageCount = $('#fs-slider-images-list .fs-image-item').length;
                            var imageItemHtml = `
                                <li class="fs-image-item" data-attachment-id="${imageData.id}" data-index="${currentImageCount}">
                                    <div class="fs-image-preview" style="width: ${$('#floating_slider_width').val()}px; height: ${$('#floating_slider_height').val()}px;">
                                        <img src="${imageData.url}" alt="Slider Image" style="object-fit: ${$('#floating_slider_image_fit').val()}; border-radius: ${$('#floating_slider_border_radius').val()}px;" />
                                    </div>
                                    <div class="fs-image-details">
                                        <strong>Link:</strong>
                                        <span class="fs-image-link-display">${imageData.link}</span>
                                        <input type="url" class="fs-image-link-input" value="${imageData.link}" style="display: none;" />
                                        <button type="button" class="fs-edit-link-btn button button-small">Edit Link</button>
                                        <button type="button" class="fs-save-link-btn button button-small button-primary" style="display: none;">Save Link</button>
                                        <p class="fs-image-info">Original Dimensions: ${imageData.original_dimensions}</p>
                                        <button type="button" class="fs-delete-image-btn button button-small button-danger">Delete</button>
                                    </div>
                                </li>
                            `;
                            $('#fs-slider-images-list').append(imageItemHtml);
                            console.log(response.data.message);
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error uploading image.');
                    }
                });
            }
        });

        frame.open();
    });

    // Make images sortable
    $('#fs-slider-images-list').sortable({
        items: '.fs-image-item',
        cursor: 'grabbing',
        placeholder: 'ui-state-highlight', // Class for placeholder while dragging
        update: function(event, ui) {
            var imageOrder = $(this).children('.fs-image-item').map(function() {
                return $(this).data('attachment-id'); // Get attachment ID
            }).get();

            $.ajax({
                url: floatingSliderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_update_slider_image_order',
                    order: imageOrder,
                    nonce: floatingSliderAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Re-index data-index attributes after sorting
                        $('#fs-slider-images-list').children('.fs-image-item').each(function(index) {
                            $(this).data('index', index);
                        });
                        console.log(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error updating image order.');
                }
            });
        }
    });

    // Handle Delete Image button click
    $(document).on('click', '.fs-delete-image-btn', function() {
        if (confirm(floatingSliderAjax.messages.confirm_delete)) {
            var $item = $(this).closest('.fs-image-item');
            var index = $item.data('index'); // Get the current index

            $.ajax({
                url: floatingSliderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_delete_slider_image',
                    index: index,
                    nonce: floatingSliderAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            // Re-index remaining items after removal
                            $('#fs-slider-images-list').children('.fs-image-item').each(function(idx) {
                                $(this).data('index', idx);
                            });
                            if ($('#fs-slider-images-list').children('.fs-image-item').length === 0) {
                                $('#fs-slider-images-list').append('<p id="no-images-message">' + floatingSliderAjax.messages.no_images_yet + '</p>');
                            }
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error deleting image.');
                }
            });
        }
    });

    // Handle Edit Link button click
    $(document).on('click', '.fs-edit-link-btn', function() {
        var $parent = $(this).closest('.fs-image-item');
        $parent.find('.fs-image-link-display').hide();
        $parent.find('.fs-image-link-input').show().focus();
        $(this).hide();
        $parent.find('.fs-save-link-btn').show();
    });

    // Handle Save Link button click
    $(document).on('click', '.fs-save-link-btn', function() {
        var $parent = $(this).closest('.fs-image-item');
        var index = $parent.data('index');
        var newLink = $parent.find('.fs-image-link-input').val();

        $.ajax({
            url: floatingSliderAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'fs_update_slider_image_link',
                index: index,
                link: newLink,
                nonce: floatingSliderAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $parent.find('.fs-image-link-display').text(newLink).show();
                    $parent.find('.fs-image-link-input').hide();
                    $parent.find('.fs-edit-link-btn').show();
                    $parent.find('.fs-save-link-btn').hide();
                    console.log(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Error updating image link.');
            }
        });
    });

    // Tab functionality - Modified to just switch visibility without full page reload
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href').split('tab=')[1];
        if (!tab_id) tab_id = 'general'; // Default tab

        // Update URL without reloading page
        history.pushState(null, null, '?page=floating-slider&tab=' + tab_id);

        // Show/hide content based on tab
        $('.tab-content').hide(); // Hide all tab content
        $('#' + tab_id + '-tab-content').show(); // Show current tab content

        // Update active class for tabs
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });

    // Ensure the correct tab is active on page load and show content
    var currentTab = new URLSearchParams(window.location.search).get('tab');
    if (!currentTab) currentTab = 'general';
    $('.nav-tab-wrapper a[href$="tab=' + currentTab + '"]').addClass('nav-tab-active');
    $('#' + currentTab + '-tab-content').show();
});