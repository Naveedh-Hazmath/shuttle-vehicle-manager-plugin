<?php
/**
 * Registers and manages the vehicle custom post type.
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Vehicle_Post_Type {

    /**
     * Register the vehicle custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Vehicles', 'Post type general name', 'shuttle-vehicle-manager'),
            'singular_name'         => _x('Vehicle', 'Post type singular name', 'shuttle-vehicle-manager'),
            'menu_name'             => _x('Vehicles', 'Admin Menu text', 'shuttle-vehicle-manager'),
            'name_admin_bar'        => _x('Vehicle', 'Add New on Toolbar', 'shuttle-vehicle-manager'),
            'add_new'               => __('Add New', 'shuttle-vehicle-manager'),
            'add_new_item'          => __('Add New Vehicle', 'shuttle-vehicle-manager'),
            'new_item'              => __('New Vehicle', 'shuttle-vehicle-manager'),
            'edit_item'             => __('Edit Vehicle', 'shuttle-vehicle-manager'),
            'view_item'             => __('View Vehicle', 'shuttle-vehicle-manager'),
            'all_items'             => __('All Vehicles', 'shuttle-vehicle-manager'),
            'search_items'          => __('Search Vehicles', 'shuttle-vehicle-manager'),
            'not_found'             => __('No vehicles found.', 'shuttle-vehicle-manager'),
            'not_found_in_trash'    => __('No vehicles found in Trash.', 'shuttle-vehicle-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'vehicle'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'thumbnail', 'author'),
            'menu_icon'          => 'dashicons-car',
            'taxonomies'         => array('vehicle_status'),
            'show_in_rest'       => true,
            'rest_base'          => 'vehicles',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type('vehicle', $args);
        
        // Register meta boxes
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_vehicle', array($this, 'save_vehicle_meta'), 10, 2);
    }
    
    /**
     * Register custom taxonomies.
     */
    public function register_taxonomies() {
        // Register Status Taxonomy
        $labels = array(
            'name'              => _x('Vehicle Statuses', 'taxonomy general name', 'shuttle-vehicle-manager'),
            'singular_name'     => _x('Vehicle Status', 'taxonomy singular name', 'shuttle-vehicle-manager'),
            'search_items'      => __('Search Vehicle Statuses', 'shuttle-vehicle-manager'),
            'all_items'         => __('All Vehicle Statuses', 'shuttle-vehicle-manager'),
            'edit_item'         => __('Edit Vehicle Status', 'shuttle-vehicle-manager'),
            'update_item'       => __('Update Vehicle Status', 'shuttle-vehicle-manager'),
            'add_new_item'      => __('Add New Vehicle Status', 'shuttle-vehicle-manager'),
            'new_item_name'     => __('New Vehicle Status Name', 'shuttle-vehicle-manager'),
            'menu_name'         => __('Statuses', 'shuttle-vehicle-manager'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'vehicle-status'),
        );

        register_taxonomy('vehicle_status', array('vehicle'), $args);
    }

    /**
     * Register meta boxes for the vehicle post type.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'vehicle_details',
            __('Vehicle Details', 'shuttle-vehicle-manager'),
            array($this, 'render_vehicle_details_meta_box'),
            'vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'vehicle_documents',
            __('Vehicle Documents', 'shuttle-vehicle-manager'),
            array($this, 'render_vehicle_documents_meta_box'),
            'vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'vehicle_availability',
            __('Vehicle Availability', 'shuttle-vehicle-manager'),
            array($this, 'render_vehicle_availability_meta_box'),
            'vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'vehicle_status',
            __('Vehicle Status', 'shuttle-vehicle-manager'),
            array($this, 'render_vehicle_status_meta_box'),
            'vehicle',
            'side',
            'high'
        );
    }

    /**
     * Render the vehicle details meta box.
     */
    public function render_vehicle_details_meta_box($post) {
        wp_nonce_field('shuttle_vehicle_meta', 'shuttle_vehicle_meta_nonce');
        
        $make = get_post_meta($post->ID, 'vehicle_make', true);
        $model = get_post_meta($post->ID, 'vehicle_model', true);
        $type = get_post_meta($post->ID, 'vehicle_type', true);
        $year_manufacture = get_post_meta($post->ID, 'year_manufacture', true);
        $year_registration = get_post_meta($post->ID, 'year_registration', true);
        $license_plate = get_post_meta($post->ID, 'license_plate', true);
        $seating_capacity = get_post_meta($post->ID, 'seating_capacity', true);
        $fuel_type = get_post_meta($post->ID, 'fuel_type', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="vehicle_make"><?php _e('Make', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="text" id="vehicle_make" name="vehicle_make" value="<?php echo esc_attr($make); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="vehicle_model"><?php _e('Model', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="text" id="vehicle_model" name="vehicle_model" value="<?php echo esc_attr($model); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="vehicle_type"><?php _e('Vehicle Type', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="text" id="vehicle_type" name="vehicle_type" value="<?php echo esc_attr($type); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="year_manufacture"><?php _e('Year of Manufacture', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="number" id="year_manufacture" name="year_manufacture" value="<?php echo esc_attr($year_manufacture); ?>" min="1900" max="2099" step="1"></td>
            </tr>
            <tr>
                <th><label for="year_registration"><?php _e('Year of Registration', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="number" id="year_registration" name="year_registration" value="<?php echo esc_attr($year_registration); ?>" min="1900" max="2099" step="1"></td>
            </tr>
            <tr>
                <th><label for="license_plate"><?php _e('License Plate Number', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="text" id="license_plate" name="license_plate" value="<?php echo esc_attr($license_plate); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="seating_capacity"><?php _e('Seating Capacity', 'shuttle-vehicle-manager'); ?></label></th>
                <td><input type="number" id="seating_capacity" name="seating_capacity" value="<?php echo esc_attr($seating_capacity); ?>" min="1" max="99" step="1"></td>
            </tr>
            <tr>
                <th><label for="fuel_type"><?php _e('Fuel Type', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <select id="fuel_type" name="fuel_type">
                        <option value="petrol" <?php selected($fuel_type, 'petrol'); ?>><?php _e('Petrol', 'shuttle-vehicle-manager'); ?></option>
                        <option value="diesel" <?php selected($fuel_type, 'diesel'); ?>><?php _e('Diesel', 'shuttle-vehicle-manager'); ?></option>
                        <option value="electric" <?php selected($fuel_type, 'electric'); ?>><?php _e('Electric', 'shuttle-vehicle-manager'); ?></option>
                        <option value="hybrid" <?php selected($fuel_type, 'hybrid'); ?>><?php _e('Hybrid', 'shuttle-vehicle-manager'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the vehicle documents meta box.
     */
    public function render_vehicle_documents_meta_box($post) {
        $rc_doc = get_post_meta($post->ID, 'rc_document', true);
        $insurance_doc = get_post_meta($post->ID, 'insurance_document', true);
        $emission_doc = get_post_meta($post->ID, 'emission_document', true);
        $revenue_license_doc = get_post_meta($post->ID, 'revenue_license_document', true);
        $fitness_doc = get_post_meta($post->ID, 'fitness_document', true);
        $vehicle_images = get_post_meta($post->ID, 'vehicle_images', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('RC Document', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($rc_doc)) : ?>
                        <a href="<?php echo esc_url($rc_doc); ?>" target="_blank" class="svm-button"><?php _e('View Document', 'shuttle-vehicle-manager'); ?></a>
                    <?php else : ?>
                        <p><?php _e('No document uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Insurance Document', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($insurance_doc)) : ?>
                        <a href="<?php echo esc_url($insurance_doc); ?>" target="_blank" class="svm-button"><?php _e('View Document', 'shuttle-vehicle-manager'); ?></a>
                    <?php else : ?>
                        <p><?php _e('No document uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Emission Document', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($emission_doc)) : ?>
                        <a href="<?php echo esc_url($emission_doc); ?>" target="_blank" class="svm-button"><?php _e('View Document', 'shuttle-vehicle-manager'); ?></a>
                    <?php else : ?>
                        <p><?php _e('No document uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Revenue License Document', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($revenue_license_doc)) : ?>
                        <a href="<?php echo esc_url($revenue_license_doc); ?>" target="_blank" class="svm-button"><?php _e('View Document', 'shuttle-vehicle-manager'); ?></a>
                    <?php else : ?>
                        <p><?php _e('No document uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Fitness Document', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($fitness_doc)) : ?>
                        <a href="<?php echo esc_url($fitness_doc); ?>" target="_blank" class="svm-button"><?php _e('View Document', 'shuttle-vehicle-manager'); ?></a>
                    <?php else : ?>
                        <p><?php _e('No document uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Vehicle Images', 'shuttle-vehicle-manager'); ?></label></th>
                <td>
                    <?php if (!empty($vehicle_images) && is_array($vehicle_images)) : ?>
                        <div class="vehicle-images-gallery">
                            <?php foreach ($vehicle_images as $image) : ?>
                                <div class="vehicle-image">
                                    <img src="<?php echo esc_url($image); ?>" width="150" height="150">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php _e('No images uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the vehicle availability meta box.
     */
    public function render_vehicle_availability_meta_box($post) {
        $available_dates = get_post_meta($post->ID, 'available_dates', true);
        
        ?>
        <div class="availability-calendar-container">
            <div id="vehicle-availability-calendar"></div>
            <input type="hidden" id="available_dates" name="available_dates" value="<?php echo esc_attr($available_dates); ?>">
            
            <script>
                jQuery(document).ready(function($) {
                    var availableDates = '<?php echo esc_js($available_dates); ?>';
                    var dates = availableDates ? JSON.parse(availableDates) : [];
                    
                    var calendar = flatpickr('#vehicle-availability-calendar', {
                        mode: 'multiple',
                        dateFormat: 'Y-m-d',
                        defaultDate: dates,
                        onChange: function(selectedDates, dateStr, instance) {
                            var dateArray = selectedDates.map(function(date) {
                                return instance.formatDate(date, 'Y-m-d');
                            });
                            $('#available_dates').val(JSON.stringify(dateArray));
                        }
                    });
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render the vehicle status meta box.
     */
    public function render_vehicle_status_meta_box($post) {
        // Get current status
        $current_status = 'pending';
        $terms = get_the_terms($post->ID, 'vehicle_status');
        if (!empty($terms) && !is_wp_error($terms)) {
            $current_status = $terms[0]->slug;
        }
        
        ?>
        <div class="vehicle-status-container">
            <p><strong><?php _e('Current Status:', 'shuttle-vehicle-manager'); ?></strong></p>
            <div class="status-badge status-<?php echo esc_attr($current_status); ?>">
                <?php echo esc_html(ucfirst($current_status)); ?>
            </div>
            
            <p><strong><?php _e('Change Status:', 'shuttle-vehicle-manager'); ?></strong></p>
            <select name="vehicle_status" id="vehicle_status">
                <option value="pending" <?php selected($current_status, 'pending'); ?>><?php _e('Pending', 'shuttle-vehicle-manager'); ?></option>
                <option value="verified" <?php selected($current_status, 'verified'); ?>><?php _e('Verified', 'shuttle-vehicle-manager'); ?></option>
            </select>
        </div>
        <?php
    }

    /**
     * Save vehicle meta data.
     */
    public function save_vehicle_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['shuttle_vehicle_meta_nonce']) || !wp_verify_nonce($_POST['shuttle_vehicle_meta_nonce'], 'shuttle_vehicle_meta')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save vehicle details
        if (isset($_POST['vehicle_make'])) {
            update_post_meta($post_id, 'vehicle_make', sanitize_text_field($_POST['vehicle_make']));
        }
        
        if (isset($_POST['vehicle_model'])) {
            update_post_meta($post_id, 'vehicle_model', sanitize_text_field($_POST['vehicle_model']));
        }
        
        if (isset($_POST['vehicle_type'])) {
            update_post_meta($post_id, 'vehicle_type', sanitize_text_field($_POST['vehicle_type']));
        }
        
        if (isset($_POST['year_manufacture'])) {
            update_post_meta($post_id, 'year_manufacture', intval($_POST['year_manufacture']));
        }
        
        if (isset($_POST['year_registration'])) {
            update_post_meta($post_id, 'year_registration', intval($_POST['year_registration']));
        }
        
        if (isset($_POST['license_plate'])) {
            update_post_meta($post_id, 'license_plate', sanitize_text_field($_POST['license_plate']));
        }
        
        if (isset($_POST['seating_capacity'])) {
            update_post_meta($post_id, 'seating_capacity', intval($_POST['seating_capacity']));
        }
        
        if (isset($_POST['fuel_type'])) {
            update_post_meta($post_id, 'fuel_type', sanitize_text_field($_POST['fuel_type']));
        }
        
        // Save availability dates
        if (isset($_POST['available_dates'])) {
            update_post_meta($post_id, 'available_dates', sanitize_text_field($_POST['available_dates']));
        }
        
        // Save vehicle status
        if (isset($_POST['vehicle_status'])) {
            wp_set_object_terms($post_id, $_POST['vehicle_status'], 'vehicle_status');
        }
    }
    
    /**
     * Add custom columns to the vehicle list.
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        // Insert columns after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['vehicle_info'] = __('Vehicle Info', 'shuttle-vehicle-manager');
                $new_columns['owner'] = __('Owner', 'shuttle-vehicle-manager');
                $new_columns['status'] = __('Status', 'shuttle-vehicle-manager');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content.
     */
    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'vehicle_info':
                $make = get_post_meta($post_id, 'vehicle_make', true);
                $model = get_post_meta($post_id, 'vehicle_model', true);
                $license_plate = get_post_meta($post_id, 'license_plate', true);
                
                echo '<strong>' . esc_html($make . ' ' . $model) . '</strong><br>';
                echo esc_html($license_plate);
                break;
                
            case 'owner':
                $post = get_post($post_id);
                $owner_id = $post->post_author;
                $owner = get_userdata($owner_id);
                
                if ($owner) {
                    $mobile = get_user_meta($owner_id, 'mobile_number', true);
                    $full_name = get_user_meta($owner_id, 'full_name', true);
                    
                    echo esc_html($full_name) . '<br>';
                    echo esc_html($mobile);
                }
                break;
                
            case 'status':
                $terms = get_the_terms($post_id, 'vehicle_status');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $status = $terms[0]->slug;
                    
                    echo '<div class="status-badge status-' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</div>';
                    
                    if ($status === 'pending') {
                        echo '<a href="#" class="verify-vehicle-button svm-button" data-id="' . esc_attr($post_id) . '">' . __('Verify', 'shuttle-vehicle-manager') . '</a>';
                    }
                } else {
                    echo '<div class="status-badge status-pending">' . __('Pending', 'shuttle-vehicle-manager') . '</div>';
                    echo '<a href="#" class="verify-vehicle-button svm-button" data-id="' . esc_attr($post_id) . '">' . __('Verify', 'shuttle-vehicle-manager') . '</a>';
                }
                break;
        }
    }
}