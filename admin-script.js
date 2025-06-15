jQuery(document).ready(function($) {
    // Initialize WordPress Color Picker
    $('.color-picker').wpColorPicker({
        palettes: true,
        change: function(event, ui) {
            var newColor = ui.color.toString();
            // If alpha is used, ensure it's rgba for proper saving
            if ($(this).data('alpha') && ui.color.alpha() < 1 && !newColor.includes('rgba')) {
                newColor = ui.color.toRgbString();
            }
            $(this).val(newColor).trigger('change'); // Trigger change for instant preview if needed
        },
        clear: function() {
            // Clears the value if the clear button is clicked
            $(this).val('').trigger('change');
        }
    });

    // Custom Numeric Slider Functionality using jQuery UI Slider
    $('.numeric-slider-input').each(function() {
        var $input = $(this);
        var min = parseFloat($input.data('min') || $input.attr('min') || 0);
        var max = parseFloat($input.data('max') || $input.attr('max') || 100);
        var step = parseFloat($input.data('step') || $input.attr('step') || 1);
        var initialValue = parseFloat($input.val());

        // Clamp initial value to min/max
        if (isNaN(initialValue) || initialValue < min) initialValue = min;
        if (initialValue > max) initialValue = max;
        $input.val(initialValue);

        // Create a div for the slider
        var $sliderDiv = $('<div class="slider"></div>');
        $input.parent('.numeric-slider-wrapper').append($sliderDiv); // Append to the wrapper next to the input

        $sliderDiv.slider({
            range: "min",
            value: initialValue,
            min: min,
            max: max,
            step: step,
            slide: function(event, ui) {
                $input.val(ui.value);
                // Trigger change event so form is marked as dirty if needed
                $input.trigger('change');
            }
        });

        // Update slider when input changes manually
        $input.on('input change', function() {
            var val = parseFloat($(this).val());
            if (isNaN(val)) val = min; // Default if input is empty or invalid
            if (val < min) val = val; // No need to clamp here, slider does it
            if (val > max) val = max; // No need to clamp here, slider does it
            $(this).val(val); // Update input field (clamped)
            $sliderDiv.slider('value', val); // Update slider
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
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var linkUrl = prompt(floatingSliderAjax.messages.enter_image_link, attachment.url);

            if (linkUrl !== null) { // If user didn't cancel the prompt
                $('#no-images-message').remove(); // Remove "No images" message if present

                $.ajax({
                    url: floatingSliderAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fs_upload_slider_image',
                        attachment_id: attachment.id,
                        link_url: linkUrl,
                        nonce: floatingSliderAjax.nonce // Ensure nonce is included
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
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        alert('Error uploading image: ' + (xhr.responseJSON ? xhr.responseJSON.data.message : 'Unknown error. Check console for details.'));
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
        placeholder: 'ui-state-highlight',
        update: function(event, ui) {
            var imageOrder = $(this).children('.fs-image-item').map(function() {
                return $(this).data('attachment-id');
            }).get();

            // Re-index data-index attribute after sorting
            $(this).children('.fs-image-item').each(function(index) {
                $(this).data('index', index);
            });

            $.ajax({
                url: floatingSliderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_update_slider_image_order',
                    order: imageOrder,
                    nonce: floatingSliderAjax.nonce // Ensure nonce is included
                },
                success: function(response) {
                    if (response.success) {
                        console.log(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    alert('Error updating image order: ' + (xhr.responseJSON ? xhr.responseJSON.data.message : 'Unknown error. Check console for details.'));
                }
            });
        }
    });

    // Handle Delete Image button click
    $(document).on('click', '.fs-delete-image-btn', function() {
        if (confirm(floatingSliderAjax.messages.confirm_delete)) {
            var $item = $(this).closest('.fs-image-item');
            var index = $item.data('index'); // Use data-index to get the current order index

            $.ajax({
                url: floatingSliderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_delete_slider_image',
                    index: index,
                    nonce: floatingSliderAjax.nonce // Ensure nonce is included
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            // Re-index all remaining items
                            $('#fs-slider-images-list').children('.fs-image-item').each(function(idx) {
                                $(this).data('index', idx);
                            });
                            if ($('#fs-slider-images-list').children('.fs-image-item').length === 0) {
                                $('#fs-slider-images-list').append('<p id="no-images-message">' + floatingSliderAjax.messages.no_images_yet + '</p>');
                            }
                        });
                        console.log(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    alert('Error deleting image: ' + (xhr.responseJSON ? xhr.responseJSON.data.message : 'Unknown error. Check console for details.'));
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
                nonce: floatingSliderAjax.nonce // Ensure nonce is included
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
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                alert('Error updating image link: ' + (xhr.responseJSON ? xhr.responseJSON.data.message : 'Unknown error. Check console for details.'));
            }
        });
    });

    // Tab functionality
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var tab_id = $(this).attr('href').split('tab=')[1];
        if (!tab_id) tab_id = 'general'; // Default to general tab

        // Update URL hash to maintain tab state on refresh
        history.pushState(null, null, '?page=floating-slider&tab=' + tab_id);

        $('.tab-content').hide();
        $('#' + tab_id + '-tab-content').show();

        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });

    // Set active tab on page load based on URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var initialTab = urlParams.get('tab');
    if (!initialTab) {
        initialTab = 'general'; // Default tab
    }
    $('.nav-tab-wrapper a[href*="tab=' + initialTab + '"]').addClass('nav-tab-active');
    $('#' + initialTab + '-tab-content').show();
});