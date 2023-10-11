//Step 1: Add this to your child themes functions.php
In your post type you will need to create a Meta field Type Media.
Then add the ID of the meta field inte code: Replece YOUR META FIELD ID with your meta field ID


// Enqueue Signature Pad library and custom script
function enqueue_signature_scripts() {
wp_enqueue_script('signature_pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), null, true);
// Enqueue our custom JS code
wp_add_inline_script('signature_pad', '
document.addEventListener("DOMContentLoaded", function() {
var canvas = document.getElementById("signature-pad");
var signaturePad = new SignaturePad(canvas);
var saveButton = document.getElementById("save-signature");
var clearButton = document.getElementById("clearCanvas"); // Clean button
saveButton.addEventListener("click", function() {
if (signaturePad.isEmpty()) {
alert("Please sign before save.");
} else {
var dataURL = signaturePad.toDataURL();
saveSignature(dataURL);
}
});
clearButton.addEventListener("click", function() { //Event controller to clean the area
signaturePad.clear();
});
function saveSignature(dataURL) {
var postId = signature_object.post_id; // Get post id from localized object
var ajaxurl = signature_object.ajax_url; // Get ajax url from localized object
var formData = new FormData();
formData.append("signature_data", dataURL);
formData.append("post_id", postId);
formData.append("action", "save_signature");
fetch(ajaxurl, {
method: "POST",
body: formData
}).then(response => response.json())
.then(data => {
if (data.success) {
alert("The signature was saved with success");
location.reload(); // This will reload the page
} else {
alert("Can not save the signature");
}
});
}
});
', 'after');
// Localize our script with necessary variables
global $post;
wp_localize_script('signature_pad', 'signature_object', array(
'post_id' => $post->ID,
'ajax_url' => admin_url('admin-ajax.php')
));
}
add_action('wp_enqueue_scripts', 'enqueue_signature_scripts');
// Handle AJAX request to save signature
add_action('wp_ajax_save_signature', 'handle_signature_save');
add_action('wp_ajax_nopriv_save_signature', 'handle_signature_save');
function handle_signature_save() {
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$signature_data = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';
if ($post_id && $signature_data) {
// Extract the base64 data from the signature
list($type, $signature_data) = explode(';', $signature_data);
list(, $signature_data) = explode(',', $signature_data);
$signature_data = base64_decode($signature_data);
// Use WordPress upload to add to media library
$upload_dir = wp_upload_dir();
$unique_file_name = wp_unique_filename($upload_dir['path'], 'signature_' . $post_id . '.png');
$filename = $upload_dir['path'] . '/' . $unique_file_name;
file_put_contents($filename, $signature_data);
// Check image file type
$wp_filetype = wp_check_filetype($filename, null);
// Set attachment data
$attachment = array(
'post_mime_type' => $wp_filetype['type'],
'post_title' => sanitize_file_name($unique_file_name),
'post_content' => '',
'post_status' => 'inherit'
);
// Create the attachment
$attach_id = wp_insert_attachment($attachment, $filename, $post_id);
// Include image in the media library
$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
wp_update_attachment_metadata($attach_id, $attach_data);
// Link the attachment to the post as meta
update_post_meta($post_id, 'YOUR META FIELD ID', $attach_id);
echo json_encode(['success' => true]);
} else {
echo json_encode(['success' => false]);
}
wp_die();
}
