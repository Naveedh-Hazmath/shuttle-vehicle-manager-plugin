<?php
/**
 * Shuttle Vehicle Manager REST API endpoints
 *
 * @since      1.5.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Vehicle_REST_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes.
     */
    public function register_routes() {
        // Public vehicle listing
        register_rest_route('lankashuttle/v1', '/vehicles', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicles'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 20,
                ),
                'status' => array(
                    'type' => 'string',
                    'description' => 'Filter by vehicle status (pending/verified)',
                ),
            ),
        ));

        // Single vehicle details
        register_rest_route('lankashuttle/v1', '/vehicles/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_vehicle'),
            'permission_callback' => '__return_true',
        ));

        // Get user's vehicles (owner only)
        register_rest_route('lankashuttle/v1', '/my-vehicles', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_my_vehicles'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        // Create new vehicle
        register_rest_route('lankashuttle/v1', '/vehicles', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_vehicle'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        // Update vehicle
        register_rest_route('lankashuttle/v1', '/vehicles/(?P<id>\d+)', array(
            'methods'  => array(WP_REST_Server::EDITABLE),
            'callback' => array($this, 'update_vehicle'),
            'permission_callback' => array($this, 'check_vehicle_ownership'),
        ));

        // Delete vehicle
        register_rest_route('lankashuttle/v1', '/vehicles/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_vehicle'),
            'permission_callback' => array($this, 'check_vehicle_ownership'),
        ));

        // Update vehicle availability
        register_rest_route('lankashuttle/v1', '/vehicles/(?P<id>\d+)/availability', array(
            'methods'  => array(WP_REST_Server::EDITABLE),
            'callback' => array($this, 'update_availability'),
            'permission_callback' => array($this, 'check_vehicle_ownership'),
        ));

        // Get user profile
        register_rest_route('lankashuttle/v1', '/user/profile', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        // Update user profile
        register_rest_route('lankashuttle/v1', '/user/profile', array(
            'methods'  => array(WP_REST_Server::EDITABLE),
            'callback' => array($this, 'update_user_profile'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        // Authentication status
        register_rest_route('lankashuttle/v1', '/auth/status', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_auth_status'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Permission callback: Check if user is logged in
     */
    public function check_user_logged_in($request) {
        return is_user_logged_in();
    }

    /**
     * Permission callback: Check vehicle ownership
     */
    public function check_vehicle_ownership($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $vehicle_id = $request['id'];
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            return false;
        }

        $current_user = wp_get_current_user();
        return $vehicle->post_author == $current_user->ID || current_user_can('manage_options');
    }

    /**
     * Get all vehicles (public endpoint)
     */
    public function get_vehicles($request) {
        $page = (int) $request->get_param('page') ?: 1;
        $per_page = (int) $request->get_param('per_page') ?: 20;
        $status = $request->get_param('status');

        $args = array(
            'post_type' => 'vehicle',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ($status) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'vehicle_status',
                    'field' => 'slug',
                    'terms' => $status,
                ),
            );
        }

        $query = new WP_Query($args);
        $vehicles = array();

        foreach ($query->posts as $post) {
            $vehicles[] = $this->prepare_vehicle_response($post);
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $vehicles,
            'pagination' => array(
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ),
        ));
    }

    /**
     * Get single vehicle details
     */
    public function get_vehicle($request) {
        $vehicle_id = $request['id'];
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            return new WP_Error(
                'vehicle_not_found',
                __('Vehicle not found', 'shuttle-vehicle-manager'),
                array('status' => 404)
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $this->prepare_vehicle_response($vehicle, true),
        ));
    }

    /**
     * Get current user's vehicles
     */
    public function get_my_vehicles($request) {
        $current_user = wp_get_current_user();

        $args = array(
            'post_type' => 'vehicle',
            'author' => $current_user->ID,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $query = new WP_Query($args);
        $vehicles = array();

        foreach ($query->posts as $post) {
            $vehicles[] = $this->prepare_vehicle_response($post);
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $vehicles,
            'count' => count($vehicles),
        ));
    }

    /**
     * Create a new vehicle
     */
    public function create_vehicle($request) {
        $params = $request->get_json_params();
        $current_user = wp_get_current_user();

        // Validate required fields
        $required_fields = array('vehicle_type', 'vehicle_model', 'year_manufacture', 'year_registration', 'license_plate', 'seating_capacity');
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('%s is required', 'shuttle-vehicle-manager'), $field),
                    array('status' => 400)
                );
            }
        }

        // Create vehicle post
        $post_data = array(
            'post_title' => $params['vehicle_type'] . ' ' . $params['vehicle_model'] . ' - ' . $params['license_plate'],
            'post_status' => 'publish',
            'post_type' => 'vehicle',
            'post_author' => $current_user->ID,
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'vehicle_creation_failed',
                $post_id->get_error_message(),
                array('status' => 500)
            );
        }

        // Set initial status to pending
        wp_set_object_terms($post_id, 'pending', 'vehicle_status');

        // Save vehicle details
        update_post_meta($post_id, 'vehicle_type', sanitize_text_field($params['vehicle_type']));
        update_post_meta($post_id, 'vehicle_model', sanitize_text_field($params['vehicle_model']));
        update_post_meta($post_id, 'year_manufacture', intval($params['year_manufacture']));
        update_post_meta($post_id, 'year_registration', intval($params['year_registration']));
        update_post_meta($post_id, 'license_plate', sanitize_text_field($params['license_plate']));
        update_post_meta($post_id, 'seating_capacity', intval($params['seating_capacity']));

        // Save optional fields
        if (isset($params['vehicle_features']) && is_array($params['vehicle_features'])) {
            update_post_meta($post_id, 'vehicle_features', array_map('sanitize_text_field', $params['vehicle_features']));
        }

        // Handle image uploads from base64 if provided
        if (isset($params['vehicle_images']) && is_array($params['vehicle_images'])) {
            $vehicle_images = $this->process_image_uploads($params['vehicle_images']);
            if (!empty($vehicle_images)) {
                update_post_meta($post_id, 'vehicle_images', $vehicle_images);
            }
        }

        // Handle document uploads from base64 if provided
        $documents = array('rc_document', 'insurance_document', 'emission_document', 'revenue_license_document', 'fitness_document');
        foreach ($documents as $doc) {
            if (isset($params[$doc])) {
                $doc_url = $this->process_document_upload($params[$doc], $doc);
                if ($doc_url) {
                    update_post_meta($post_id, $doc, $doc_url);
                }
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Vehicle created successfully and is pending verification', 'shuttle-vehicle-manager'),
            'data' => array(
                'id' => $post_id,
                'status' => 'pending',
            ),
        ));
    }

    /**
     * Update a vehicle
     */
    public function update_vehicle($request) {
        $vehicle_id = $request['id'];
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            return new WP_Error(
                'vehicle_not_found',
                __('Vehicle not found', 'shuttle-vehicle-manager'),
                array('status' => 404)
            );
        }

        $params = $request->get_json_params();

        // Update vehicle fields
        if (isset($params['vehicle_type'])) {
            update_post_meta($vehicle_id, 'vehicle_type', sanitize_text_field($params['vehicle_type']));
        }
        if (isset($params['vehicle_model'])) {
            update_post_meta($vehicle_id, 'vehicle_model', sanitize_text_field($params['vehicle_model']));
        }
        if (isset($params['year_manufacture'])) {
            update_post_meta($vehicle_id, 'year_manufacture', intval($params['year_manufacture']));
        }
        if (isset($params['year_registration'])) {
            update_post_meta($vehicle_id, 'year_registration', intval($params['year_registration']));
        }
        if (isset($params['license_plate'])) {
            update_post_meta($vehicle_id, 'license_plate', sanitize_text_field($params['license_plate']));
        }
        if (isset($params['seating_capacity'])) {
            update_post_meta($vehicle_id, 'seating_capacity', intval($params['seating_capacity']));
        }
        if (isset($params['vehicle_features'])) {
            update_post_meta($vehicle_id, 'vehicle_features', array_map('sanitize_text_field', $params['vehicle_features']));
        }

        // Update post title
        wp_update_post(array(
            'ID' => $vehicle_id,
            'post_title' => $params['vehicle_type'] . ' ' . $params['vehicle_model'] . ' - ' . $params['license_plate'],
        ));

        // Reset status to pending on update
        wp_set_object_terms($vehicle_id, 'pending', 'vehicle_status');

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Vehicle updated successfully and is now pending verification', 'shuttle-vehicle-manager'),
            'data' => $this->prepare_vehicle_response(get_post($vehicle_id)),
        ));
    }

    /**
     * Delete a vehicle
     */
    public function delete_vehicle($request) {
        $vehicle_id = $request['id'];
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            return new WP_Error(
                'vehicle_not_found',
                __('Vehicle not found', 'shuttle-vehicle-manager'),
                array('status' => 404)
            );
        }

        $result = wp_delete_post($vehicle_id, true);

        if (!$result) {
            return new WP_Error(
                'deletion_failed',
                __('Failed to delete vehicle', 'shuttle-vehicle-manager'),
                array('status' => 500)
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Vehicle deleted successfully', 'shuttle-vehicle-manager'),
        ));
    }

    /**
     * Update vehicle availability
     */
    public function update_availability($request) {
        $vehicle_id = $request['id'];
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            return new WP_Error(
                'vehicle_not_found',
                __('Vehicle not found', 'shuttle-vehicle-manager'),
                array('status' => 404)
            );
        }

        $params = $request->get_json_params();

        if (!isset($params['availability_data'])) {
            return new WP_Error(
                'missing_data',
                __('availability_data is required', 'shuttle-vehicle-manager'),
                array('status' => 400)
            );
        }

        // Save availability data as JSON
        $availability_json = is_array($params['availability_data'])
            ? json_encode($params['availability_data'])
            : $params['availability_data'];

        update_post_meta($vehicle_id, 'availability_data', $availability_json);

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Availability updated successfully', 'shuttle-vehicle-manager'),
        ));
    }

    /**
     * Get user profile
     */
    public function get_user_profile($request) {
        $current_user = wp_get_current_user();

        $owner_profile = new Shuttle_Owner_Profile();
        $profile_fields = $owner_profile->get_profile_fields();

        $profile_data = array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'email' => $current_user->user_email,
        );

        foreach ($profile_fields as $key => $field) {
            if ($key === 'email') {
                $profile_data[$key] = $current_user->user_email;
            } else {
                $profile_data[$key] = get_user_meta($current_user->ID, $key, true);
            }
        }

        // Get profile status
        $profile_data['profile_status'] = get_user_meta($current_user->ID, 'profile_status', true) ?: 'pending';
        $profile_data['profile_image'] = get_user_meta($current_user->ID, 'profile_image', true);
        $profile_data['roles'] = $current_user->roles;

        return rest_ensure_response(array(
            'success' => true,
            'data' => $profile_data,
        ));
    }

    /**
     * Update user profile
     */
    public function update_user_profile($request) {
        $current_user = wp_get_current_user();
        $params = $request->get_json_params();

        $owner_profile = new Shuttle_Owner_Profile();
        $profile_fields = $owner_profile->get_profile_fields();

        // Update email if provided
        if (isset($params['email'])) {
            $email = sanitize_email($params['email']);
            if (!is_email($email)) {
                return new WP_Error(
                    'invalid_email',
                    __('Invalid email address', 'shuttle-vehicle-manager'),
                    array('status' => 400)
                );
            }
            wp_update_user(array(
                'ID' => $current_user->ID,
                'user_email' => $email,
            ));
        }

        // Update profile fields
        foreach ($profile_fields as $key => $field) {
            if ($key === 'email') {
                continue; // Already handled above
            }
            if (isset($params[$key])) {
                if ($field['type'] === 'textarea') {
                    update_user_meta($current_user->ID, $key, sanitize_textarea_field($params[$key]));
                } else {
                    update_user_meta($current_user->ID, $key, sanitize_text_field($params[$key]));
                }
            }
        }

        // Handle profile image upload from base64
        if (isset($params['profile_image'])) {
            $image_url = $this->process_single_image_upload($params['profile_image'], 'profile');
            if ($image_url) {
                update_user_meta($current_user->ID, 'profile_image', $image_url);
            }
        }

        // Set profile status as pending if not already set
        $status = get_user_meta($current_user->ID, 'profile_status', true);
        if (empty($status)) {
            update_user_meta($current_user->ID, 'profile_status', 'pending');
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Profile updated successfully', 'shuttle-vehicle-manager'),
        ));
    }

    /**
     * Get authentication status
     */
    public function get_auth_status($request) {
        if (!is_user_logged_in()) {
            return rest_ensure_response(array(
                'authenticated' => false,
                'user' => null,
            ));
        }

        $current_user = wp_get_current_user();

        return rest_ensure_response(array(
            'authenticated' => true,
            'user' => array(
                'id' => $current_user->ID,
                'username' => $current_user->user_login,
                'email' => $current_user->user_email,
                'roles' => $current_user->roles,
            ),
        ));
    }

    /**
     * Prepare vehicle data for response
     */
    private function prepare_vehicle_response($post, $include_details = false) {
        $vehicle = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'type' => get_post_meta($post->ID, 'vehicle_type', true),
            'model' => get_post_meta($post->ID, 'vehicle_model', true),
            'license_plate' => get_post_meta($post->ID, 'license_plate', true),
            'seating_capacity' => intval(get_post_meta($post->ID, 'seating_capacity', true)),
            'year_manufacture' => intval(get_post_meta($post->ID, 'year_manufacture', true)),
            'year_registration' => intval(get_post_meta($post->ID, 'year_registration', true)),
            'owner_id' => intval($post->post_author),
            'created_at' => $post->post_date_gmt,
            'updated_at' => $post->post_modified_gmt,
        );

        // Get status
        $terms = get_the_terms($post->ID, 'vehicle_status');
        $vehicle['status'] = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'pending';

        if ($include_details) {
            $vehicle['features'] = get_post_meta($post->ID, 'vehicle_features', true) ?: array();
            $vehicle['images'] = get_post_meta($post->ID, 'vehicle_images', true) ?: array();
            $vehicle['rc_document'] = get_post_meta($post->ID, 'rc_document', true);
            $vehicle['insurance_document'] = get_post_meta($post->ID, 'insurance_document', true);
            $vehicle['emission_document'] = get_post_meta($post->ID, 'emission_document', true);
            $vehicle['revenue_license_document'] = get_post_meta($post->ID, 'revenue_license_document', true);
            $vehicle['fitness_document'] = get_post_meta($post->ID, 'fitness_document', true);
            $vehicle['availability_data'] = get_post_meta($post->ID, 'availability_data', true);
        }

        return $vehicle;
    }

    /**
     * Process image uploads from base64 or file path
     */
    private function process_image_uploads($images) {
        $uploaded_images = array();

        foreach ($images as $image_data) {
            $image_url = $this->process_single_image_upload($image_data, 'vehicle');
            if ($image_url) {
                $uploaded_images[] = $image_url;
            }
        }

        return $uploaded_images;
    }

    /**
     * Process single image upload from base64
     */
    private function process_single_image_upload($image_data, $type = 'vehicle') {
        // If it's already a URL, return it
        if (filter_var($image_data, FILTER_VALIDATE_URL)) {
            return $image_data;
        }

        // Handle base64 encoded images
        if (strpos($image_data, 'data:image') === 0) {
            preg_match('/data:image\/(\w+);base64,(.+)/', $image_data, $matches);
            if (!isset($matches[1]) || !isset($matches[2])) {
                return null;
            }

            $image_type = $matches[1];
            $image_base64 = $matches[2];

            $image_binary = base64_decode($image_base64);
            $filename = $type . '_' . time() . '.' . $image_type;

            // Use WordPress upload function
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $filename;

            if (file_put_contents($file_path, $image_binary)) {
                $file_type = 'image/' . $image_type;
                $attachment = array(
                    'post_mime_type' => $file_type,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                    'post_content' => '',
                    'post_status' => 'inherit',
                );

                $attach_id = wp_insert_attachment($attachment, $file_path);

                return $upload_dir['url'] . '/' . $filename;
            }
        }

        return null;
    }

    /**
     * Process document uploads from base64
     */
    private function process_document_upload($document_data, $doc_type) {
        // If it's already a URL, return it
        if (filter_var($document_data, FILTER_VALIDATE_URL)) {
            return $document_data;
        }

        // Handle base64 encoded documents
        if (strpos($document_data, 'data:') === 0) {
            preg_match('/data:([^;]+);base64,(.+)/', $document_data, $matches);
            if (!isset($matches[1]) || !isset($matches[2])) {
                return null;
            }

            $mime_type = $matches[1];
            $document_base64 = $matches[2];
            $document_binary = base64_decode($document_base64);

            // Determine file extension from MIME type
            $ext_map = array(
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            );

            $ext = $ext_map[$mime_type] ?? 'bin';
            $filename = $doc_type . '_' . time() . '.' . $ext;

            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $filename;

            if (file_put_contents($file_path, $document_binary)) {
                return $upload_dir['url'] . '/' . $filename;
            }
        }

        return null;
    }
}
