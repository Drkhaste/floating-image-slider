jQuery(document).ready(function($) {
    // Initialize WordPress Color Picker
    $('.color-picker').wpColorPicker({
        // Add alpha channel support if 'data-alpha="true"' is set
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

    // Handle Add Image button click
    $('#fs-add-image-btn').on('click', function(event) {
        event.preventDefault();

        var frame = wp.media({
            title: floatingSliderAjax.messages.enter_image_link,
            button: {
                text: 'Select Image'
            },
            multiple: false // Allow selection of only one image
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var linkUrl = prompt(floatingSliderAjax.messages.enter_image_link, 'https://'); // Prompt for link

            if (linkUrl !== null) { // If user didn't cancel prompt
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
                            // Reload the page to show the new image and re-initialize sortable
                            location.reload();
                            // Or, for a smoother UX, dynamically add the image without reload:
                            // We could build the image item HTML and append it to #fs-slider-images-list
                            // But for simplicity with current architecture and sortable, reload is easier.
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
                        // Re-index data-index attributes after sorting if necessary
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
                            // Re-index remaining items
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

    // Tab functionality
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).attr('href').split('tab=')[1];
        if (!tab) tab = 'general'; // Default tab

        // Update URL without reloading page
        history.pushState(null, null, '?page=floating-slider&tab=' + tab);

        // Show/hide content based on tab
        $('.tab-content').hide(); // Hide all tab content
        $('#' + tab + '-tab-content').show(); // Show current tab content

        // Update active class for tabs
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Reload the page to ensure proper form submission and data display after tab switch
        // This is a simple way to handle form submission for different sections.
        // For a more advanced SPA-like experience, you would use AJAX to save settings per tab.
        window.location.href = $(this).attr('href');
    });

    // Ensure the correct tab is active on page load
    var currentTab = new URLSearchParams(window.location.search).get('tab');
    if (!currentTab) currentTab = 'general';
    $('.nav-tab-wrapper a[href$="tab=' + currentTab + '"]').addClass('nav-tab-active');

});