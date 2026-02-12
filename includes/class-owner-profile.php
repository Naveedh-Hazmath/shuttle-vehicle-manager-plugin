<?php
/**
 * Manages the vehicle owner profile.
 *
 * @since      1.1.1
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Owner_Profile {

    /**
     * Constructor.
     */
    public function __construct() {
        // Define profile fields
        $this->profile_fields = array(
            'full_name' => array(
                'label' => __('Full Name', 'shuttle-vehicle-manager'),
                'type' => 'text',
                'required' => true
            ),
            'nic_number' => array(
                'label' => __('NIC Number', 'shuttle-vehicle-manager'),
                'type' => 'text',
                'required' => true
            ),
            'address' => array(
                'label' => __('Address', 'shuttle-vehicle-manager'),
                'type' => 'textarea',
                'required' => true
            ),
            'mobile_number' => array(
                'label' => __('Mobile Number', 'shuttle-vehicle-manager'),
                'type' => 'text',
                'required' => true
            ),
            'whatsapp_number' => array(
                'label' => __('WhatsApp Number (if different)', 'shuttle-vehicle-manager'),
                'type' => 'text',
                'required' => false
            ),
            'email' => array(
                'label' => __('Email Address', 'shuttle-vehicle-manager'),
                'type' => 'email',
                'required' => false
            )
        );
    }

    /**
     * Get profile fields.
     */
    public function get_profile_fields() {
        return $this->profile_fields;
    }

    /**
     * Save owner profile.
     */
    public function save_profile() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'shuttle-vehicle-manager')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Handle email field specially
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $email = sanitize_email($_POST['email']);
            
            // Validate email only if provided
            if (!is_email($email)) {
                wp_send_json_error(array('message' => __('Please enter a valid email address.', 'shuttle-vehicle-manager')));
                return;
            }
            
            // Update both user meta and WordPress user email
            update_user_meta($user_id, 'email', $email);
            
            // Update the WordPress user email
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $email
            ));
        }
        
        // Save other profile fields
        foreach ($this->profile_fields as $key => $field) {
            // Skip email as we've already handled it
            if ($key === 'email') {
                continue;
            }
            
            if (isset($_POST[$key])) {
                if ($field['type'] === 'textarea') {
                    update_user_meta($user_id, $key, sanitize_textarea_field($_POST[$key]));
                } else {
                    update_user_meta($user_id, $key, sanitize_text_field($_POST[$key]));
                }
            }
        }
        
        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $file = $_FILES['profile_image'];
            
            $upload_overrides = array(
                'test_form' => false,
            );
            
            $uploaded_file = wp_handle_upload($file, $upload_overrides);
            
            if (!isset($uploaded_file['error'])) {
                update_user_meta($user_id, 'profile_image', $uploaded_file['url']);
            }
        }
        
        // Set profile status as pending if not already set
        $status = get_user_meta($user_id, 'profile_status', true);
        if (empty($status)) {
            update_user_meta($user_id, 'profile_status', 'pending');
        }
        
        // Get the redirect URL from options, with fallback
        $redirect_path = get_option('svm_redirect_path', '/my-account/');
        $redirect_url = home_url($redirect_path);
        
        wp_send_json_success(array(
            'message' => __('Profile updated successfully!', 'shuttle-vehicle-manager'),
            'redirect' => $redirect_url,
        ));
    }

    /**
     * Check if profile is complete.
     */
    public function check_profile_completion() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('complete' => false));
            return;
        }
        
        $user_id = get_current_user_id();
        $complete = true;
        $missing_fields = array();
        
        foreach ($this->profile_fields as $key => $field) {
            if ($field['required']) {
                // For email, check both user meta and wp_users table
                if ($key === 'email') {
                    $user_info = get_userdata($user_id);
                    $value = $user_info->user_email;
                } else {
                    $value = get_user_meta($user_id, $key, true);
                }
                
                if (empty($value)) {
                    $complete = false;
                    $missing_fields[] = $field['label'];
                }
            }
        }
        
        wp_send_json_success(array(
            'complete' => $complete,
            'missing_fields' => $missing_fields
        ));
    }

    /**
     * Get owner status.
     */
    public function get_owner_status($user_id) {
        $status = get_user_meta($user_id, 'profile_status', true);
        return empty($status) ? 'pending' : $status;
    }

    /**
     * Render profile form.
     */
    public function render_profile_form($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $profile_image = get_user_meta($user_id, 'profile_image', true);
        $status = $this->get_owner_status($user_id);
        $user_info = get_userdata($user_id);
        
        ?>
        <div class="shuttle-profile-form">
            <div class="profile-header">
                <div class="profile-image">
                    <?php if (!empty($profile_image)) : ?>
                        <img src="<?php echo esc_url($profile_image); ?>" alt="<?php _e('Profile Image', 'shuttle-vehicle-manager'); ?>">
                    <?php else : ?>
                        <div class="profile-placeholder">
                            <i class="dashicons dashicons-admin-users"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-status status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </div>
            </div>
            
            <form id="shuttle-profile-form" method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><?php _e('Profile Information', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <div class="form-group">
                        <label for="profile_image"><?php _e('Profile Image', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png">
                    </div>
                    
                    <?php foreach ($this->profile_fields as $key => $field) : ?>
                        <div class="form-group">
                            <label for="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($field['label']); ?>
                                <?php if ($field['required']) : ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php 
                            // Get the field value
                            $value = '';
                            if ($key === 'email') {
                                $value = $user_info->user_email;
                            } else {
                                $value = get_user_meta($user_id, $key, true);
                            }
                            ?>
                            
                            <?php if ($field['type'] === 'textarea') : ?>
                                <textarea id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" <?php echo $field['required'] ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
                            <?php elseif ($field['type'] === 'date') : ?>
                                <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="date-picker" <?php echo $field['required'] ? 'required' : ''; ?>>
                            <?php else : ?>
                                <input type="<?php echo esc_attr($field['type']); ?>" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="svm-button"><?php _e('Save Profile', 'shuttle-vehicle-manager'); ?></button>
                </div>
                
                <div class="form-message"></div>
                <?php wp_nonce_field('shuttle_vehicle_nonce', 'nonce'); ?>
            </form>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('.date-picker').flatpickr({
                    dateFormat: 'Y-m-d'
                });
            });
        </script>
        <?php
    }
}