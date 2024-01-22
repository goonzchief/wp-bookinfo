<?php
/**
 * Plugin Name: Book Info Integration
 * Description: Snags book details from the Open Library API and showcases them in a nifty meta box for WooCommerce products and regular posts.
 * Tags: book, library, open library, WooCommerce, meta box
 * Author:      Amir F (Goonzchief)
 * Stable Tag: 1.0.0
 * License: MIT
 * Author URI: https://github.com/goonzchief
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Tested up to: 5.9
 */

// Enqueue jQuery for AJAX awesomeness
function book_info_enqueue_jquery_for_ajax() {
    // Check if on the post-new.php or post.php pages and load jQuery goodness
    global $pagenow;
    if ($pagenow === 'post-new.php' || $pagenow === 'post.php') {
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'book_info_enqueue_jquery_for_ajax');

// AJAX handler to fetch book intel
function book_info_fetch_book_info() {
    // Verify AJAX referer and fetch the lowdown from Open Library API
    check_ajax_referer('book_info_nonce', 'nonce');
    $book_title = sanitize_text_field($_POST['book_title']);
    $api_url = "http://openlibrary.org/search.json";
    $params = array('q' => $book_title);
    $response = wp_safe_remote_get(add_query_arg($params, $api_url));

    // Process the deets
    if (!is_wp_error($response)) {
        $data = wp_remote_retrieve_body($response);
        $book_data = json_decode($data, true);

        // Check if the bookish scoop is available
        if (isset($book_data['docs'][0])) {
            wp_send_json_success($book_data['docs'][0]);
        } else {
            wp_send_json_error('Whoopsie! Couldn\'t find info on this book. Give it another whirl! 😅');
        }
    } else {
        wp_send_json_error('Oops! Error fetching book info. Please try again later. 🚀');
    }
}
add_action('wp_ajax_book_info_fetch_book_info', 'book_info_fetch_book_info');

// Add a cool custom meta box for book search on product and post types
function book_info_add_custom_meta_box($post_type) {
    // Slap on a custom meta box to specified post types
    $allowed_post_types = array('product', 'post');
    if (in_array($post_type, $allowed_post_types)) {
        add_meta_box('book_info_search_meta_box', 'Book Search', 'book_info_render_meta_box', $post_type, 'normal', 'default');
    }
}
add_action('add_meta_boxes', 'book_info_add_custom_meta_box');

// Render the hip custom meta box content
function book_info_render_meta_box($post) {
    // Show off the custom meta box content
    echo '<label for="book_search">Book Title</label>';
    echo '<input type="text" id="book_search" name="book_search" value="" />';
    echo '<button id="book_info_fetch_button" type="button">Fetch Book Info</button>';
    echo '<div id="book_info_result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto;"></div>';
    echo '<div id="html_box" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto;"></div>';
    ?>
    <script>
        // jQuery script for handling AJAX and UI shenanigans
        jQuery(document).ready(function($) {
            $('#book_info_fetch_button').on('click', function(e) {
                e.preventDefault();
                var bookTitle = $('#book_search').val();

                // AJAX call to snag book info
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'book_info_fetch_book_info',
                        book_title: bookTitle,
                        nonce: '<?php echo wp_create_nonce('book_info_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var htmlCode = generateHtmlCode(response.data);
                            $('#book_info_result').html(htmlCode);
                            
                            // Display HTML in the box - just for kicks!
                            $('#html_box').html('<strong>HTML Code:</strong><br><pre>' + escapeHtml(htmlCode) + '</pre>');
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            });

            // Function to generate HTML code for the snazzy fetched data
            function generateHtmlCode(data) {
                var formattedHtml = '<div style="border: 1px solid #ccc; padding: 10px;">';
                formattedHtml += '<strong>Book Info:</strong><br>';
                formattedHtml += '<pre>';
                for (var key in data) {
                    // Generate HTML code for each juicy piece of information
                    formattedHtml += '<p><strong>' + key + ':</strong> ' + data[key] + '</p>';
                }
                formattedHtml += '</pre>';
                formattedHtml += '</div>';

                return formattedHtml;
            }

            // Function to escape HTML characters - just in case!
            function escapeHtml(html) {
                var text = document.createTextNode(html);
                var div = document.createElement('div');
                div.appendChild(text);
                return div.innerHTML;
            }
        });
    </script>
    <?php


}
?>
