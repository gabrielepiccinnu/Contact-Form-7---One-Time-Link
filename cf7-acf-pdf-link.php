<?php
/*
Plugin Name: CF7 ACF PDF Link
Description: Send one-time PDF links via CF7 after form submission
Version: 1.0
Author: Your Name
*/

// Your code will go here

function cf7_acf_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'one_time_links';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        token varchar(255) NOT NULL,
        file_url varchar(255) NOT NULL,
        used tinyint(1) DEFAULT '0' NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
} 
register_activation_hook(__FILE__, 'cf7_acf_create_table');


function generate_one_time_link($file_url) {
    global $wpdb;
    $token = wp_generate_password(20, false);  // Generate a unique token
    $table_name = $wpdb->prefix . 'one_time_links';
    $wpdb->insert($table_name, array('token' => $token, 'file_url' => $file_url));
    return get_site_url() . "/?one_time_token=" . $token;
}

/*
function send_one_time_link($contact_form) {
    $submission = WPCF7_Submission::get_instance();

    if($submission) {
        $posted_data = $submission->get_posted_data();
        $property_id = $posted_data['property_id'];
        $pdf_file_url = get_field('property_pdf', $property_id);

        if($pdf_file_url) {
            $one_time_link = generate_one_time_link($pdf_file_url);

            $mail = $contact_form->prop('mail');
            $mail['body'] .= "\n\nDownload the project PDF here: " . $one_time_link;
            $contact_form->set_properties(array('mail' => $mail));
        }
    }
}
add_action('wpcf7_before_send_mail', 'send_one_time_link');
*/

function send_pdf( $cf7 ) {
    global $post;
    $id = $cf7->id();
    if ($id==5){
        $submission = WPCF7_Submission::get_instance();
        $submission->add_uploaded_file('pdf', get_field('property_pdf', $post->ID));
    }
}


//add_action('wpcf7_before_send_mail','send_pdf');


function send_one_time_link($contact_form) {
    // Check for the specific form ID, in this example it's 1234
    if ($contact_form->id() != 5) {
        return; // If it's not the form with ID 1234, don't process further.
    }

    $submission = WPCF7_Submission::get_instance();

    if($submission) {
        $posted_data = $submission->get_posted_data();
        $property_id = $posted_data['property_id'];
        $pdf_file_url = get_field('property_pdf', $property_id);

        if($pdf_file_url) {
            $one_time_link = generate_one_time_link($pdf_file_url);

            $mail = $contact_form->prop('mail');
            $mail['body'] .= "\n\nDownload the project PDF here: " . $one_time_link;
            $contact_form->set_properties(array('mail' => $mail));
        }
    }
}
add_action('wpcf7_before_send_mail', 'send_one_time_link');






function serve_one_time_link() {
    if(isset($_GET['one_time_token'])) {
        global $wpdb;
        $token = sanitize_text_field($_GET['one_time_token']);
        $table_name = $wpdb->prefix . 'one_time_links';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));

        if($row && $row->used == 0) {
            // Mark the link as used
            $wpdb->update($table_name, array('used' => 1), array('id' => $row->id));

            // Redirect to the file or serve it directly
            wp_redirect($row->file_url);
            exit;
        } else {
            // Link is invalid or already used
            wp_die('Invalid or expired link.', 'Error');
        }
    }
}
add_action('init', 'serve_one_time_link');



 

function property_pdf_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => null
    ), $atts, 'property_pdf_link');

    if (!$atts['id']) return '';

    return get_field('property_pdf', $atts['id']);
}
add_shortcode('property_pdf_link', 'property_pdf_shortcode');

