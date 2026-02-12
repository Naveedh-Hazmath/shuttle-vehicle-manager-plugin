<?php
/**
 * Handles user registration and login with mobile number.
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_User_Registration {

    /**
     * Register scripts.
     */
    public function register_scripts() {
        // Remove email requirement from registration
        add_filter('registration_errors', array($this, 'remove_email_requirement'), 10, 3);
    }

    /**
     * Remove email requirement from registration.
     */
    public function remove_email_requirement($errors, $sanitized_user_login, $user_email) {
        $errors->remove('empty_email');
        return $errors;
    }

    /**
     * Register a new user.
     */
    public function register_user() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');

        $mobile_number = sanitize_text_field($_POST['mobile_number']);
        $password = sanitize_text_field($_POST['password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);
        
        // Validate mobile number
        if (empty($mobile_number)) {
            wp_send_json_error(array('message' => __('Mobile number is required.', 'shuttle-vehicle-manager')));
            return;
        }
        
        // Validate password
        if (empty($password)) {
            wp_send_json_error(array('message' => __('Password is required.', 'shuttle-vehicle-manager')));
            return;
        }
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => __('Passwords do not match.', 'shuttle-vehicle-manager')));
            return;
        }

        // Check if mobile number already exists
        $user_exists = username_exists($mobile_number);
        
        if ($user_exists) {
            wp_send_json_error(array('message' => __('This mobile number is already registered.', 'shuttle-vehicle-manager')));
            return;
        }

        // Create user
        $user_id = wp_create_user($mobile_number, $password);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }

        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('vehicle_owner');

        // Add mobile number as user meta
        update_user_meta($user_id, 'mobile_number', $mobile_number);
        
        // Set profile status as pending
        update_user_meta($user_id, 'profile_status', 'pending');

        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

 $redirect_path = get_option('svm_redirect_path', '/my-account/');
wp_send_json_success(array(
    'message' => __('Registration successful! Redirecting...', 'shuttle-vehicle-manager'),
    'redirect' => home_url($redirect_path),
));

    }

    /**
     * Login user.
     */
    public function login_user() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');

        $mobile_number = sanitize_text_field($_POST['mobile_number']);
        $password = sanitize_text_field($_POST['password']);
        $remember = isset($_POST['remember']) ? true : false;
        
        // Validate inputs
        if (empty($mobile_number) || empty($password)) {
            wp_send_json_error(array('message' => __('Mobile number and password are required.', 'shuttle-vehicle-manager')));
            return;
        }

        $user = get_user_by('login', $mobile_number);
        
        if (!$user) {
            wp_send_json_error(array('message' => __('Invalid mobile number or password.', 'shuttle-vehicle-manager')));
            return;
        }

        $creds = array(
            'user_login'    => $mobile_number,
            'user_password' => $password,
            'remember'      => $remember,
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => __('Invalid mobile number or password.', 'shuttle-vehicle-manager')));
            return;
        }

       $redirect_path = get_option('svm_redirect_path', '/my-account/');
wp_send_json_success(array(
    'message' => __('Login successful! Redirecting...', 'shuttle-vehicle-manager'),
    'redirect' => home_url($redirect_path),
));
    }
}