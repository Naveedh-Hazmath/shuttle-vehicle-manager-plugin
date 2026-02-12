<?php
/**
 * Creates and manages the admin menus.
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Admin_Menu {

    /**
     * Register admin menus.
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Vehicle Owners', 'shuttle-vehicle-manager'),
            __('Vehicle Owners', 'shuttle-vehicle-manager'),
            'manage_options',
            'shuttle-vehicle-owners',
            array($this, 'render_vehicle_owners_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'shuttle-vehicle-owners',
            __('Vehicle Owners', 'shuttle-vehicle-manager'),
            __('All Owners', 'shuttle-vehicle-manager'),
            'manage_options',
            'shuttle-vehicle-owners',
            array($this, 'render_vehicle_owners_page')
        );
        
        add_submenu_page(
            'shuttle-vehicle-owners',
            __('All Vehicles', 'shuttle-vehicle-manager'),
            __('All Vehicles', 'shuttle-vehicle-manager'),
            'manage_options',
            'edit.php?post_type=vehicle',
            null
        );
        
        add_submenu_page(
            'shuttle-vehicle-owners',
            __('Available Vehicles', 'shuttle-vehicle-manager'),
            __('Available Vehicles', 'shuttle-vehicle-manager'),
            'manage_options',
            'shuttle-available-vehicles',
            array($this, 'render_available_vehicles_page')
        );
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // AJAX handlers for admin
        add_action('wp_ajax_shuttle_verify_vehicle', array($this, 'verify_vehicle'));
        add_action('wp_ajax_shuttle_verify_owner', array($this, 'verify_owner'));
        add_action('wp_ajax_get_all_vehicles_availability', array($this, 'get_all_vehicles_availability'));
        // add_action('wp_ajax_export_availability_pdf', array($this, 'export_availability_pdf'));
    }

    /**
     * Render the vehicle owners page.
     */
    public function render_vehicle_owners_page() {
        // Check if viewing a specific owner's vehicles
        if (isset($_GET['owner_id'])) {
            $this->render_owner_vehicles_page(intval($_GET['owner_id']));
            return;
        }
        
        // Check if viewing a specific vehicle
        if (isset($_GET['vehicle_id'])) {
            $this->render_vehicle_details_page(intval($_GET['vehicle_id']));
            return;
        }
        
        // Get all users with vehicle_owner role
        $owners = get_users(array(
            'role' => 'vehicle_owner',
        ));
        
        ?>
        <div class="wrap svm-admin-wrap">
            <h1><?php _e('Vehicle Owners', 'shuttle-vehicle-manager'); ?></h1>
            
            <div class="svm-admin-filters">
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <input type="hidden" name="page" value="shuttle-vehicle-owners">
                    
                    <select name="status" id="status-filter">
                        <option value=""><?php _e('All Statuses', 'shuttle-vehicle-manager'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>><?php _e('Pending', 'shuttle-vehicle-manager'); ?></option>
                        <option value="verified" <?php selected(isset($_GET['status']) && $_GET['status'] === 'verified'); ?>><?php _e('Verified', 'shuttle-vehicle-manager'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'shuttle-vehicle-manager'); ?>">
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped svm-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('Owner', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Contact Info', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Profile Status', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Vehicles', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Actions', 'shuttle-vehicle-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($owners)) : ?>
                        <?php foreach ($owners as $owner) : 
                            // Get owner profile data
                            $full_name = get_user_meta($owner->ID, 'full_name', true);
                            $nic_number = get_user_meta($owner->ID, 'nic_number', true);
                            $mobile_number = get_user_meta($owner->ID, 'mobile_number', true);
                            $whatsapp_number = get_user_meta($owner->ID, 'whatsapp_number', true);
                            $status = get_user_meta($owner->ID, 'profile_status', true);
                            $status = empty($status) ? 'pending' : $status;
                            
                            // Count vehicles for this owner
                            $vehicles_count = count($this->get_owner_vehicles($owner->ID));
                            
                            // Skip if filtering by status
                            if (isset($_GET['status']) && !empty($_GET['status']) && $status !== $_GET['status']) {
                                continue;
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($full_name ? $full_name : __('Not provided', 'shuttle-vehicle-manager')); ?></strong>
                                    <?php if (!empty($nic_number)) : ?>
                                        <div class="row-actions">
                                            <span><?php _e('NIC:', 'shuttle-vehicle-manager'); ?> <?php echo esc_html($nic_number); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php _e('Mobile:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($mobile_number); ?><br>
                                    <?php if (!empty($whatsapp_number)) : ?>
                                        <strong><?php _e('WhatsApp:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($whatsapp_number); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </div>
                                    
                                    <?php if ($status === 'pending') : ?>
                                        <a href="#" class="verify-owner-button svm-button svm-button-sm" data-id="<?php echo esc_attr($owner->ID); ?>">
                                            <?php _e('Verify', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($vehicles_count); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=shuttle-vehicle-owners&owner_id=' . $owner->ID)); ?>" class="svm-button">
                                        <?php _e('View Vehicles', 'shuttle-vehicle-manager'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $owner->ID)); ?>" class="svm-button svm-button-secondary">
                                        <?php _e('Edit Profile', 'shuttle-vehicle-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php _e('No vehicle owners found.', 'shuttle-vehicle-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the owner vehicles page.
     */
    private function render_owner_vehicles_page($owner_id) {
        $owner = get_userdata($owner_id);
        
        if (!$owner || !in_array('vehicle_owner', $owner->roles)) {
            wp_die(__('Invalid vehicle owner.', 'shuttle-vehicle-manager'));
        }
        
        $vehicles = $this->get_owner_vehicles($owner_id);
        $full_name = get_user_meta($owner->ID, 'full_name', true);
        $mobile_number = get_user_meta($owner->ID, 'mobile_number', true);
        $owner_status = get_user_meta($owner->ID, 'profile_status', true);
        $owner_status = empty($owner_status) ? 'pending' : $owner_status;
        
        ?>
        <div class="wrap svm-admin-wrap">
            <h1>
                <?php printf(__('Vehicles Owned by %s', 'shuttle-vehicle-manager'), esc_html($full_name ? $full_name : $mobile_number)); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=shuttle-vehicle-owners')); ?>" class="page-title-action"><?php _e('Back to Owners', 'shuttle-vehicle-manager'); ?></a>
            </h1>
            
            <div class="svm-owner-card">
                <div class="svm-owner-info">
                    <h2><?php echo esc_html($full_name ? $full_name : $mobile_number); ?></h2>
                    <div class="svm-owner-meta">
                        <div class="svm-owner-status status-<?php echo esc_attr($owner_status); ?>">
                            <?php echo esc_html(ucfirst($owner_status)); ?>
                        </div>
                        
                        <?php if ($owner_status === 'pending') : ?>
                            <a href="#" class="verify-owner-button svm-button" data-id="<?php echo esc_attr($owner->ID); ?>">
                                <?php _e('Verify Owner', 'shuttle-vehicle-manager'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php
                // Show owner details
                $profile_fields = array(
                    'mobile_number' => __('Mobile Number', 'shuttle-vehicle-manager'),
                    'whatsapp_number' => __('WhatsApp Number', 'shuttle-vehicle-manager'),
                    'nic_number' => __('NIC Number', 'shuttle-vehicle-manager'),
                    'address' => __('Address', 'shuttle-vehicle-manager'),
                );
                ?>
                
                <div class="svm-owner-details">
                    <h3><?php _e('Owner Details', 'shuttle-vehicle-manager'); ?></h3>
                    <table class="form-table">
                        <?php foreach ($profile_fields as $field => $label) : 
                            $value = get_user_meta($owner->ID, $field, true);
                            if (!empty($value)) :
                        ?>
                            <tr>
                                <th><?php echo esc_html($label); ?></th>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </table>
                </div>
            </div>
            
            <h2><?php _e('Owner\'s Vehicles', 'shuttle-vehicle-manager'); ?></h2>
            
            <div class="svm-admin-filters">
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <input type="hidden" name="page" value="shuttle-vehicle-owners">
                    <input type="hidden" name="owner_id" value="<?php echo esc_attr($owner_id); ?>">
                    
                    <select name="status" id="status-filter">
                        <option value=""><?php _e('All Statuses', 'shuttle-vehicle-manager'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>><?php _e('Pending', 'shuttle-vehicle-manager'); ?></option>
                        <option value="verified" <?php selected(isset($_GET['status']) && $_GET['status'] === 'verified'); ?>><?php _e('Verified', 'shuttle-vehicle-manager'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'shuttle-vehicle-manager'); ?>">
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped svm-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('Vehicle', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Details', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Status', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Available Dates', 'shuttle-vehicle-manager'); ?></th>
                        <th><?php _e('Actions', 'shuttle-vehicle-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vehicles)) : ?>
                        <?php foreach ($vehicles as $vehicle) : 
                            // Get vehicle status
                            $status = 'pending';
                            $terms = get_the_terms($vehicle->ID, 'vehicle_status');
                            if (!empty($terms) && !is_wp_error($terms)) {
                                $status = $terms[0]->slug;
                            }
                            
                            // Skip if filtering by status
                            if (isset($_GET['status']) && !empty($_GET['status']) && $status !== $_GET['status']) {
                                continue;
                            }
                            
                            // Get vehicle data
                            $type = get_post_meta($vehicle->ID, 'vehicle_type', true);
                            $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
                            $license_plate = get_post_meta($vehicle->ID, 'license_plate', true);
                            $seating = get_post_meta($vehicle->ID, 'seating_capacity', true);
                            $vehicle_features = get_post_meta($vehicle->ID, 'vehicle_features', true);
                            $features_count = is_array($vehicle_features) ? count($vehicle_features) : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($type . ' ' . $model); ?></strong><br>
                                    <?php echo esc_html($license_plate); ?>
                                </td>
                                <td>
                                    <strong><?php _e('Seating:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($seating); ?> passengers<br>
                                    <strong><?php _e('Features:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($features_count); ?> features
                                </td>
                                <td>
                                    <div class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </div>
                                    
                                    <?php if ($status === 'pending') : ?>
                                        <a href="#" class="verify-vehicle-button svm-button svm-button-sm" data-id="<?php echo esc_attr($vehicle->ID); ?>">
                                            <?php _e('Verify', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($dates_count); ?> <?php _e('days', 'shuttle-vehicle-manager'); ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=shuttle-vehicle-owners&vehicle_id=' . $vehicle->ID)); ?>" class="svm-button">
                                        <?php _e('View Details', 'shuttle-vehicle-manager'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $vehicle->ID . '&action=edit')); ?>" class="svm-button svm-button-secondary">
                                        <?php _e('Edit', 'shuttle-vehicle-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php _e('No vehicles found for this owner.', 'shuttle-vehicle-manager'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the vehicle details page.
     */
    private function render_vehicle_details_page($vehicle_id) {
        $vehicle = get_post($vehicle_id);
        
        if (!$vehicle || $vehicle->post_type !== 'vehicle') {
            wp_die(__('Invalid vehicle.', 'shuttle-vehicle-manager'));
        }
        
        $owner_id = $vehicle->post_author;
        $owner = get_userdata($owner_id);
        $full_name = get_user_meta($owner->ID, 'full_name', true);
        $mobile_number = get_user_meta($owner->ID, 'mobile_number', true);
        
        // Get vehicle data
        $type = get_post_meta($vehicle->ID, 'vehicle_type', true);
        $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
        $year_manufacture = get_post_meta($vehicle->ID, 'year_manufacture', true);
        $year_registration = get_post_meta($vehicle->ID, 'year_registration', true);
        $license_plate = get_post_meta($vehicle->ID, 'license_plate', true);
        $seating_capacity = get_post_meta($vehicle->ID, 'seating_capacity', true);
        $vehicle_features = get_post_meta($vehicle->ID, 'vehicle_features', true);
        
        $rc_doc = get_post_meta($vehicle->ID, 'rc_document', true);
        $insurance_doc = get_post_meta($vehicle->ID, 'insurance_document', true);
        $emission_doc = get_post_meta($vehicle->ID, 'emission_document', true);
        $revenue_license_doc = get_post_meta($vehicle->ID, 'revenue_license_document', true);
        $fitness_doc = get_post_meta($vehicle->ID, 'fitness_document', true);
        
        $vehicle_images = get_post_meta($vehicle->ID, 'vehicle_images', true);
        $available_dates = get_post_meta($vehicle->ID, 'available_dates', true);
        
        // Get status
        $status = 'pending';
        $terms = get_the_terms($vehicle->ID, 'vehicle_status');
        if (!empty($terms) && !is_wp_error($terms)) {
            $status = $terms[0]->slug;
        }
        
        ?>
        <div class="wrap svm-admin-wrap">
            <h1>
                <?php printf(__('Vehicle Details: %s', 'shuttle-vehicle-manager'), esc_html($type . ' ' . $model . ' - ' . $license_plate)); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=shuttle-vehicle-owners&owner_id=' . $owner_id)); ?>" class="page-title-action"><?php _e('Back to Owner Vehicles', 'shuttle-vehicle-manager'); ?></a>
            </h1>
            
            <div class="svm-vehicle-status-banner status-<?php echo esc_attr($status); ?>">
                <div class="status-icon">
                    <i class="dashicons <?php echo $status === 'verified' ? 'dashicons-yes-alt' : 'dashicons-clock'; ?>"></i>
                </div>
                <div class="status-text">
                    <?php 
                    if ($status === 'verified') {
                        _e('This vehicle has been verified.', 'shuttle-vehicle-manager');
                    } else {
                        _e('This vehicle is pending verification.', 'shuttle-vehicle-manager');
                    }
                    ?>
                </div>
                
                <?php if ($status === 'pending') : ?>
                    <div class="status-actions">
                        <a href="#" class="verify-vehicle-button svm-button" data-id="<?php echo esc_attr($vehicle->ID); ?>">
                            <?php _e('Verify Vehicle', 'shuttle-vehicle-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="svm-admin-columns">
                <div class="svm-admin-column">
                    <div class="svm-admin-card">
                        <h2><?php _e('Vehicle Information', 'shuttle-vehicle-manager'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Owner', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php echo esc_html($full_name ? $full_name : $mobile_number); ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=shuttle-vehicle-owners&owner_id=' . $owner_id)); ?>" class="svm-button svm-button-sm svm-button-secondary">
                                        <?php _e('View Owner', 'shuttle-vehicle-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Vehicle Type', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($type); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Vehicle Model', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($model); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Year of Manufacture', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($year_manufacture); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Year of Registration', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($year_registration); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('License Plate', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($license_plate); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Seating Capacity', 'shuttle-vehicle-manager'); ?></th>
                                <td><?php echo esc_html($seating_capacity); ?> passengers</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="svm-admin-column">
                    <div class="svm-admin-card">
                        <h2><?php _e('Vehicle Documents', 'shuttle-vehicle-manager'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('RC Document', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php if (!empty($rc_doc)) : ?>
                                        <a href="<?php echo esc_url($rc_doc); ?>" target="_blank" class="svm-button">
                                            <?php _e('View Document', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="description"><?php _e('Not uploaded', 'shuttle-vehicle-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Insurance Document', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php if (!empty($insurance_doc)) : ?>
                                        <a href="<?php echo esc_url($insurance_doc); ?>" target="_blank" class="svm-button">
                                            <?php _e('View Document', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="description"><?php _e('Not uploaded', 'shuttle-vehicle-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Emission Document', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php if (!empty($emission_doc)) : ?>
                                        <a href="<?php echo esc_url($emission_doc); ?>" target="_blank" class="svm-button">
                                            <?php _e('View Document', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="description"><?php _e('Not uploaded', 'shuttle-vehicle-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Revenue License Document', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php if (!empty($revenue_license_doc)) : ?>
                                        <a href="<?php echo esc_url($revenue_license_doc); ?>" target="_blank" class="svm-button">
                                            <?php _e('View Document', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="description"><?php _e('Not uploaded', 'shuttle-vehicle-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Fitness Document', 'shuttle-vehicle-manager'); ?></th>
                                <td>
                                    <?php if (!empty($fitness_doc)) : ?>
                                        <a href="<?php echo esc_url($fitness_doc); ?>" target="_blank" class="svm-button">
                                            <?php _e('View Document', 'shuttle-vehicle-manager'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="description"><?php _e('Not uploaded', 'shuttle-vehicle-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="svm-admin-card">
                    <h2><?php _e('Vehicle Features', 'shuttle-vehicle-manager'); ?></h2>
                    <?php
                    if (!empty($vehicle_features) && is_array($vehicle_features)) {
                        $all_features = array(
                            'high_back_seats' => 'High-back Adjustable Seats',
                            'adjustable_armrests' => 'Adjustable Armrests',
                            'extra_legroom' => 'Extra legroom / spacious interior',
                            'individual_seatbelts' => 'Individual seat belts',
                            'full_ac' => 'Full air-conditioning',
                            'tinted_windows' => 'Tinted / curtained windows for privacy & sun protection',
                            'coolbox' => 'Coolbox / refrigerator for refreshments',
                            'overhead_racks' => 'Overhead luggage racks / bottle holders',
                            'boot_luggage' => 'Boot (Trunk) Luggage Space',
                            'overhead_racks_space' => 'Overhead Racks Space',
                            'underfloor_luggage' => 'Underfloor Luggage Space',
                            'lcd_screens' => 'LCD / LED TV screens',
                            'audio_system' => 'High-quality audio system with Bluetooth/USB',
                            'wifi_free' => 'WiFi connectivity Free',
                            'microphone_pa' => 'Microphone / PA system (for guides & tour leaders)',
                            'usb_charging' => 'USB charging ports / 230V power outlets',
                            'abs_ebs' => 'ABS / EBS braking systems',
                            'airbags' => 'Airbags (in newer models)',
                            'fire_extinguisher' => 'Fire extinguisher & first aid kit',
                            'gps_tracking' => 'GPS tracking for route safety',
                            'emergency_exits' => 'Emergency exits & hammers',
                            'led_mood_lights' => 'LED mood / ceiling lights',
                            'reading_lamps' => 'Reading lamps for individual seats',
                            'air_suspension' => 'Air suspension for smoother ride',
                            'onboard_restroom' => 'Onboard restroom (in select long-distance coaches)',
                            'panoramic_windows' => 'Panoramic windows with wide viewing angles'
                        );
                        ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px; margin-top: 15px;">
                            <?php foreach ($vehicle_features as $feature) : 
                                if (isset($all_features[$feature])) : ?>
                                    <div style="padding: 8px; background: #f0f0f0; border-radius: 4px;">
                                        <span class="dashicons dashicons-yes" style="color: #4CAF50;"></span>
                                        <?php echo esc_html($all_features[$feature]); ?>
                                    </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    <?php } else { ?>
                        <p class="description"><?php _e('No features specified for this vehicle.', 'shuttle-vehicle-manager'); ?></p>
                    <?php } ?>
                </div>
            </div>
            
            <div class="svm-admin-card">
                <h2><?php _e('Vehicle Images', 'shuttle-vehicle-manager'); ?></h2>
                <div class="vehicle-images-gallery">
                    <?php if (!empty($vehicle_images) && is_array($vehicle_images)) : ?>
                        <?php foreach ($vehicle_images as $image) : ?>
                            <div class="vehicle-image">
                                <img src="<?php echo esc_url($image); ?>" alt="<?php _e('Vehicle Image', 'shuttle-vehicle-manager'); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="description"><?php _e('No images uploaded', 'shuttle-vehicle-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="svm-admin-card">
                <h2><?php _e('Vehicle Availability Schedule', 'shuttle-vehicle-manager'); ?></h2>
                <?php
                $availability_data = get_post_meta($vehicle->ID, 'availability_data', true);
                $availability_array = $availability_data ? json_decode($availability_data, true) : array();
                
                if (!empty($availability_array) && is_array($availability_array)) : ?>
                    <!-- existing code -->
                <?php else : ?>
                    <p><?php _e('No availability schedule has been set for this vehicle.', 'shuttle-vehicle-manager'); ?></p>
                <?php endif; ?>
            </div>
        <?php
    }

    /**
 * Render the available vehicles page.
 */
public function render_available_vehicles_page() {
    ?>
    <style>
    /* Admin Available Vehicles Page Styles */
    #wpwrap #wpcontent #wpbody-content {
        padding: 0 !important;
    }
    
    #wpwrap #wpcontent #wpbody-content .wrap {
        margin: 10px 20px 0 2px !important;
        max-width: none !important;
    }
    
    /* Date Filter Section */
    .svm-date-filter-section {
        background: #fff !important;
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        padding: 25px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    }
    
    .svm-date-filter-section h2 {
        margin: 0 0 20px 0 !important;
        font-size: 20px !important;
        color: #000 !important;
        border-bottom: 2px solid #FFCB05 !important;
        padding-bottom: 10px !important;
    }
    
    .date-filter-form {
        display: flex !important;
        gap: 20px !important;
        align-items: flex-end !important;
        flex-wrap: wrap !important;
    }
    
    .filter-group {
        flex: 1 !important;
        min-width: 200px !important;
    }
    
    .filter-group label {
        display: block !important;
        margin-bottom: 8px !important;
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 14px !important;
    }
    
    .filter-group input[type="date"] {
        width: 100% !important;
        padding: 8px 12px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        font-size: 14px !important;
    }
    
    .filter-button {
        background: #FFCB05 !important;
        color: #000 !important;
        border: none !important;
        padding: 10px 30px !important;
        border-radius: 4px !important;
        font-weight: bold !important;
        cursor: pointer !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
    }
    
    .filter-button:hover {
        background: #000 !important;
        color: #FFCB05 !important;
    }
    
    .clear-filter {
        background: #666 !important;
        color: #fff !important;
    }
    
    .clear-filter:hover {
        background: #333 !important;
    }
    
    /* Results Sections */
    .svm-results-wrapper {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 30px !important;
    }
    
    .svm-results-section {
        background: #fff !important;
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        padding: 25px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    }
    
    .results-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 20px !important;
        padding-bottom: 15px !important;
        border-bottom: 2px solid #eee !important;
    }
    
    .results-header h3 {
        margin: 0 !important;
        font-size: 18px !important;
        color: #333 !important;
    }
    
    .results-count {
        padding: 5px 15px !important;
        border-radius: 20px !important;
        font-size: 14px !important;
        font-weight: bold !important;
        color: #fff !important;
    }
    
    .results-count.available {
        background: #4CAF50 !important;
    }
    
    .results-count.reserved {
        background: #FF4D4D !important;
    }
    
    /* Vehicle Cards */
    .vehicles-list {
        display: flex !important;
        flex-direction: column !important;
        gap: 15px !important;
        max-height: 600px !important;
        overflow-y: auto !important;
    }
    
    .vehicle-card {
        background: #f9f9f9 !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 8px !important;
        padding: 20px !important;
        transition: all 0.3s ease !important;
    }
    
    .vehicle-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .vehicle-card.available {
        border-left: 4px solid #4CAF50 !important;
    }
    
    .vehicle-card.reserved {
        border-left: 4px solid #FF4D4D !important;
    }
    
    .vehicle-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: start !important;
        margin-bottom: 15px !important;
    }
    
    .vehicle-title h4 {
        margin: 0 0 5px 0 !important;
        font-size: 18px !important;
        color: #000 !important;
    }
    
    .vehicle-license {
        background: #FFCB05 !important;
        color: #000 !important;
        padding: 4px 10px !important;
        border-radius: 4px !important;
        font-size: 12px !important;
        font-weight: bold !important;
    }
    
    .vehicle-details {
        margin-bottom: 15px !important;
    }
    
    .vehicle-details p {
        margin: 5px 0 !important;
        font-size: 14px !important;
        color: #555 !important;
    }
    
    .vehicle-details strong {
        color: #333 !important;
        margin-right: 5px !important;
    }
    
    .reservation-info {
        background: #ffebee !important;
        padding: 10px !important;
        border-radius: 4px !important;
        margin-bottom: 15px !important;
    }
    
    .reservation-info p {
        margin: 5px 0 !important;
        font-size: 13px !important;
        color: #c62828 !important;
    }
    
    .owner-section {
        border-top: 1px solid #e0e0e0 !important;
        padding-top: 15px !important;
        margin-top: 15px !important;
    }
    
    .owner-section h5 {
        margin: 0 0 10px 0 !important;
        font-size: 16px !important;
        color: #333 !important;
    }
    
    .action-buttons {
        display: flex !important;
        gap: 10px !important;
        margin-top: 15px !important;
    }
    
    .action-buttons a {
        flex: 1 !important;
        text-align: center !important;
        padding: 8px 15px !important;
        background: #FFCB05 !important;
        color: #000 !important;
        text-decoration: none !important;
        border-radius: 4px !important;
        font-size: 13px !important;
        font-weight: bold !important;
        transition: all 0.3s ease !important;
    }
    
    .action-buttons a:hover {
        background: #000 !important;
        color: #FFCB05 !important;
    }

    /* Export Buttons */
    .export-buttons {
        display: flex !important;
        gap: 10px !important;
    }

    .export-csv {
        background: #4CAF50 !important;
        color: #fff !important;
    }

    .export-csv:hover {
        background: #45a049 !important;
    }

    .export-pdf {
        background: #f44336 !important;
        color: #fff !important;
    }

    .export-pdf:hover {
        background: #da190b !important;
    }

    .filter-button .dashicons {
        margin-right: 5px !important;
        vertical-align: middle !important;
    }
    
    /* Loading State */
    .loading-spinner {
        text-align: center !important;
        padding: 40px !important;
    }
    
    .loading-spinner::after {
        content: "" !important;
        display: inline-block !important;
        width: 40px !important;
        height: 40px !important;
        border: 4px solid #f3f3f3 !important;
        border-top: 4px solid #FFCB05 !important;
        border-radius: 50% !important;
        animation: spin 1s linear infinite !important;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* No Results */
    .no-results {
        text-align: center !important;
        padding: 40px 20px !important;
        color: #666 !important;
    }
    
    .no-results i {
        font-size: 48px !important;
        color: #ddd !important;
        margin-bottom: 20px !important;
        display: block !important;
    }
    
    .no-results p {
        font-size: 16px !important;
        margin: 0 !important;
    }
    
    /* Info Box */
    .info-box {
        background: #e3f2fd !important;
        border-left: 4px solid #2196F3 !important;
        padding: 15px !important;
        margin-bottom: 20px !important;
        border-radius: 4px !important;
    }
    
    .info-box p {
        margin: 0 !important;
        color: #1976D2 !important;
        font-size: 14px !important;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .svm-results-wrapper {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    
    <div class="wrap svm-admin-wrap">
        <h1><?php _e('Vehicle Availability Management', 'shuttle-vehicle-manager'); ?></h1>
        
        <!-- Date Filter Section -->
        <div class="svm-date-filter-section">
            <h2><?php _e('Filter by Date Range', 'shuttle-vehicle-manager'); ?></h2>
            
            <div class="info-box">
                <p><?php _e('Select a date range to view vehicle availability. The system will show both available and reserved vehicles for the selected period.', 'shuttle-vehicle-manager'); ?></p>
            </div>
            
            <form class="date-filter-form" id="availability-filter-form">
                <div class="filter-group">
                    <label for="start-date"><?php _e('Start Date', 'shuttle-vehicle-manager'); ?></label>
                    <input type="date" id="start-date" name="start_date" required>
                </div>
                
                <div class="filter-group">
                    <label for="end-date"><?php _e('End Date', 'shuttle-vehicle-manager'); ?></label>
                    <input type="date" id="end-date" name="end_date" required>
                </div>
                
                <button type="submit" class="filter-button">
                    <?php _e('Search Vehicles', 'shuttle-vehicle-manager'); ?>
                </button>

                <button type="button" class="filter-button clear-filter" id="clear-filter">
                    <?php _e('Clear', 'shuttle-vehicle-manager'); ?>
                </button>

                <!-- Export buttons - initially hidden -->
                <div class="export-buttons" id="export-buttons" style="display: none;">
                    <button type="button" class="filter-button export-csv" id="export-csv">
                        <span class="dashicons dashicons-download"></span> <?php _e('Export CSV', 'shuttle-vehicle-manager'); ?>
                    </button>
                    <!-- <button type="button" class="filter-button export-pdf" id="export-pdf">
                        <span class="dashicons dashicons-pdf"></span> <?php _e('Export PDF', 'shuttle-vehicle-manager'); ?>
                    </button> -->
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="svm-results-wrapper" id="results-wrapper" style="display: none;">
            <!-- Available Vehicles -->
            <div class="svm-results-section">
                <div class="results-header">
                    <h3><?php _e('Available Vehicles', 'shuttle-vehicle-manager'); ?></h3>
                    <span class="results-count available" id="available-count">0 vehicles</span>
                </div>
                <div id="available-vehicles-container" class="vehicles-list">
                    <!-- Available vehicles will be loaded here -->
                </div>
            </div>
            
            <!-- Reserved Vehicles -->
            <div class="svm-results-section">
                <div class="results-header">
                    <h3><?php _e('Reserved Vehicles', 'shuttle-vehicle-manager'); ?></h3>
                    <span class="results-count reserved" id="reserved-count">0 vehicles</span>
                </div>
                <div id="reserved-vehicles-container" class="vehicles-list">
                    <!-- Reserved vehicles will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
        <script>
            jQuery(document).ready(function($) {
                // Store the last search results globally
                var lastSearchResults = null;
                
                // Set minimum date to today
                var today = new Date().toISOString().split('T')[0];
                $('#start-date').attr('min', today);
                $('#end-date').attr('min', today);
                
                // Update end date minimum when start date changes
                $('#start-date').on('change', function() {
                    $('#end-date').attr('min', $(this).val());
                    if ($('#end-date').val() && $('#end-date').val() < $(this).val()) {
                        $('#end-date').val($(this).val());
                    }
                });
                
                // Handle form submission
                $('#availability-filter-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var startDate = $('#start-date').val();
                    var endDate = $('#end-date').val();
                    
                    if (!startDate || !endDate) {
                        alert('<?php _e("Please select both start and end dates.", "shuttle-vehicle-manager"); ?>');
                        return;
                    }
                    
                    searchVehicles(startDate, endDate);
                });
                
                // Clear filter
                $('#clear-filter').on('click', function() {
                    $('#start-date').val('');
                    $('#end-date').val('');
                    $('#results-wrapper').hide();
                    $('#export-buttons').hide();
                    lastSearchResults = null;
                });
                
                // Search vehicles
                function searchVehicles(startDate, endDate) {
                    $('#results-wrapper').show();
                    $('#available-vehicles-container').html('<div class="loading-spinner"></div>');
                    $('#reserved-vehicles-container').html('<div class="loading-spinner"></div>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'search_vehicles_by_availability',
                            start_date: startDate,
                            end_date: endDate,
                            nonce: '<?php echo wp_create_nonce('search_vehicles_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                displayResults(response.data);
                            } else {
                                var errorHtml = '<div class="no-results">' +
                                    '<i class="dashicons dashicons-info"></i>' +
                                    '<p>' + response.data.message + '</p>' +
                                    '</div>';
                                $('#available-vehicles-container').html(errorHtml);
                                $('#reserved-vehicles-container').html(errorHtml);
                                $('#export-buttons').hide();
                            }
                        },
                        error: function() {
                            var errorHtml = '<div class="no-results">' +
                                '<i class="dashicons dashicons-warning"></i>' +
                                '<p><?php _e("An error occurred. Please try again.", "shuttle-vehicle-manager"); ?></p>' +
                                '</div>';
                            $('#available-vehicles-container').html(errorHtml);
                            $('#reserved-vehicles-container').html(errorHtml);
                            $('#export-buttons').hide();
                        }
                    });
                }
                
                // Display search results
                function displayResults(data) {
                    // Store results for export
                    lastSearchResults = data;
                    
                    // Show export buttons
                    $('#export-buttons').show();
                    
                    // Update counts
                    $('#available-count').text(data.available.length + ' vehicle' + (data.available.length !== 1 ? 's' : ''));
                    $('#reserved-count').text(data.reserved.length + ' vehicle' + (data.reserved.length !== 1 ? 's' : ''));
                    
                    // Display available vehicles
                    if (data.available.length === 0) {
                        $('#available-vehicles-container').html(
                            '<div class="no-results">' +
                            '<i class="dashicons dashicons-calendar-alt"></i>' +
                            '<p><?php _e("No available vehicles for this date range.", "shuttle-vehicle-manager"); ?></p>' +
                            '</div>'
                        );
                    } else {
                        var availableHtml = '';
                        data.available.forEach(function(vehicle) {
                            availableHtml += createVehicleCard(vehicle, 'available');
                        });
                        $('#available-vehicles-container').html(availableHtml);
                    }
                    
                    // Display reserved vehicles
                    if (data.reserved.length === 0) {
                        $('#reserved-vehicles-container').html(
                            '<div class="no-results">' +
                            '<i class="dashicons dashicons-calendar-alt"></i>' +
                            '<p><?php _e("No reserved vehicles for this date range.", "shuttle-vehicle-manager"); ?></p>' +
                            '</div>'
                        );
                    } else {
                        var reservedHtml = '';
                        data.reserved.forEach(function(vehicle) {
                            reservedHtml += createVehicleCard(vehicle, 'reserved');
                        });
                        $('#reserved-vehicles-container').html(reservedHtml);
                    }
                }
                
                // Create vehicle card HTML
                function createVehicleCard(vehicle, status) {
                    var html = '<div class="vehicle-card ' + status + '">';
    
                    html += '<div class="vehicle-header">';
                    html += '<div class="vehicle-title">';
                    html += '<h4>' + vehicle.type + ' ' + vehicle.model + '</h4>';
                    html += '</div>';
                    html += '<span class="vehicle-license">' + vehicle.license_plate + '</span>';
                    html += '</div>';
                    
                    html += '<div class="vehicle-details">';
                    html += '<p><strong>Seating:</strong> ' + vehicle.seating + ' passengers</p>';
                    if (vehicle.year_manufacture) {
                        html += '<p><strong>Year:</strong> ' + vehicle.year_manufacture + '</p>';
                    }
                    html += '</div>';
                    
                    // Show reservation info for reserved vehicles
                    if (status === 'reserved' && vehicle.reservation_info) {
                        html += '<div class="reservation-info">';
                        html += '<p><strong>Reserved Dates:</strong></p>';
                        vehicle.reservation_info.forEach(function(res) {
                            html += '<p>• ' + res.dates.join(', ');
                            if (res.note) {
                                html += ' (' + res.note + ')';
                            }
                            html += '</p>';
                        });
                        html += '</div>';
                    }
                    
                    html += '<div class="owner-section">';
                    html += '<h5>Owner Information</h5>';
                    html += '<p><strong>Name:</strong> ' + vehicle.owner_name + '</p>';
                    html += '<p><strong>Mobile:</strong> ' + vehicle.owner_mobile + '</p>';
                    if (vehicle.owner_email) {
                        html += '<p><strong>Email:</strong> ' + vehicle.owner_email + '</p>';
                    }
                    html += '</div>';
                    
                    html += '<div class="action-buttons">';
                    html += '<a href="' + vehicle.owner_url + '" target="_blank">View Owner</a>';
                    html += '<a href="' + vehicle.vehicle_url + '" target="_blank">View Vehicle</a>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    return html;
                }
                
                // Export to CSV
                $('#export-csv').on('click', function() {
                    if (!lastSearchResults) return;
                    
                    var startDate = $('#start-date').val();
                    var endDate = $('#end-date').val();
                    
                    // Create CSV content
                    var csv = [];
                    
                    // Headers
                    csv.push(['Vehicle Availability Report']);
                    csv.push(['Date Range: ' + startDate + ' to ' + endDate]);
                    csv.push(['Generated: ' + new Date().toLocaleString()]);
                    csv.push([]); // Empty row
                    
                    // Available Vehicles Section
                    csv.push(['AVAILABLE VEHICLES (' + lastSearchResults.available.length + ')']);
                    csv.push(['Vehicle Type', 'Model', 'License Plate', 'Seating', 'Year', 'Owner Name', 'Owner Mobile', 'Owner Email']);

                    lastSearchResults.available.forEach(function(vehicle) {
                        csv.push([
                            vehicle.type,
                            vehicle.model,
                            vehicle.license_plate,
                            vehicle.seating + ' passengers',
                            vehicle.year_manufacture || 'N/A',
                            vehicle.owner_name,
                            vehicle.owner_mobile,
                            vehicle.owner_email || 'N/A'
                        ]);
                    });
                    
                    csv.push([]); // Empty row
                    csv.push([]); // Empty row
                    
                    // Reserved Vehicles Section
                    csv.push(['RESERVED VEHICLES (' + lastSearchResults.reserved.length + ')']);
                    csv.push(['Make', 'Model', 'License Plate', 'Type', 'Seating', 'Year', 'Owner Name', 'Owner Mobile', 'Reserved Dates', 'Notes']);
                    
                    lastSearchResults.reserved.forEach(function(vehicle) {
                        var reservedDates = '';
                        var notes = '';
                        
                        if (vehicle.reservation_info) {
                            vehicle.reservation_info.forEach(function(res, index) {
                                if (index > 0) {
                                    reservedDates += ' | ';
                                    notes += ' | ';
                                }
                                reservedDates += res.dates.join(', ');
                                notes += res.note || 'No notes';
                            });
                        }
                        
                        csv.push([
                            vehicle.make,
                            vehicle.model,
                            vehicle.license_plate,
                            vehicle.type,
                            vehicle.seating + ' passengers',
                            vehicle.year || 'N/A',
                            vehicle.owner_name,
                            vehicle.owner_mobile,
                            reservedDates,
                            notes
                        ]);
                    });
                    
                    // Convert to CSV string
                    var csvContent = csv.map(function(row) {
                        return row.map(function(cell) {
                            // Escape quotes and wrap in quotes if contains comma
                            cell = String(cell).replace(/"/g, '""');
                            if (cell.indexOf(',') !== -1 || cell.indexOf('"') !== -1 || cell.indexOf('\n') !== -1) {
                                cell = '"' + cell + '"';
                            }
                            return cell;
                        }).join(',');
                    }).join('\n');
                    
                    // Download CSV
                    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    var link = document.createElement('a');
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'vehicle_availability_' + startDate + '_to_' + endDate + '.csv');
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
                
                // // Export to PDF (using server-side generation)
                // $('#export-pdf').on('click', function() {
                //     if (!lastSearchResults) return;
                    
                //     var startDate = $('#start-date').val();
                //     var endDate = $('#end-date').val();
                    
                //     // Show loading
                //     $(this).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generating PDF...');
                    
                //     $.ajax({
                //         url: ajaxurl,
                //         type: 'POST',
                //         data: {
                //             action: 'export_availability_pdf',
                //             data: JSON.stringify(lastSearchResults),
                //             start_date: startDate,
                //             end_date: endDate,
                //             nonce: '<?php echo wp_create_nonce('export_pdf_nonce'); ?>'
                //         },
                //         xhrFields: {
                //             responseType: 'blob'
                //         },
                //         success: function(blob) {
                //             // Create download link
                //             var link = document.createElement('a');
                //             var url = window.URL.createObjectURL(blob);
                //             link.href = url;
                //             link.download = 'vehicle_availability_' + startDate + '_to_' + endDate + '.pdf';
                //             link.click();
                //             window.URL.revokeObjectURL(url);
                            
                //             // Reset button
                //             $('#export-pdf').prop('disabled', false).html('<span class="dashicons dashicons-pdf"></span> Export PDF');
                //         },
                //         error: function() {
                //             alert('Error generating PDF. Please try again.');
                //             $('#export-pdf').prop('disabled', false).html('<span class="dashicons dashicons-pdf"></span> Export PDF');
                //         }
                //     });
                // });
            });
            </script>
        <?php
    }

/**
 * AJAX handler to search vehicles by availability status.
 */
public function search_vehicles_by_availability() {
    check_ajax_referer('search_vehicles_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'shuttle-vehicle-manager')));
        return;
    }
    
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    
    if (empty($start_date) || empty($end_date)) {
        wp_send_json_error(array('message' => __('Please provide both start and end dates.', 'shuttle-vehicle-manager')));
        return;
    }
    
    // Get all vehicles
    $args = array(
        'post_type' => 'vehicle',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    $vehicles = get_posts($args);
    $available_vehicles = array();
    $reserved_vehicles = array();
    
    // Generate date range
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $end->add($interval); // Include end date
    $date_range = new DatePeriod($start, $interval, $end);
    
    $check_dates = array();
    foreach ($date_range as $date) {
        $check_dates[] = $date->format('Y-m-d');
    }
    
    foreach ($vehicles as $vehicle) {
        $owner = get_userdata($vehicle->post_author);
        
        // Get reservation data
        $reservation_data = get_post_meta($vehicle->ID, 'availability_data', true);
        $reservations = $reservation_data ? json_decode($reservation_data, true) : array();
        
        // Check reservations for this date range
        $vehicle_reservations = array();
        $has_reservation = false;
        
        foreach ($reservations as $reservation) {
            if (isset($reservation['dates']) && is_array($reservation['dates'])) {
                $overlapping_dates = array_intersect($reservation['dates'], $check_dates);
                if (!empty($overlapping_dates)) {
                    $has_reservation = true;
                    $vehicle_reservations[] = array(
                        'dates' => array_values($overlapping_dates),
                        'note' => isset($reservation['note']) ? $reservation['note'] : ''
                    );
                }
            }
        }
        
        // Prepare vehicle data
        $vehicle_data = array(
            'id' => $vehicle->ID,
            'type' => get_post_meta($vehicle->ID, 'vehicle_type', true),
            'model' => get_post_meta($vehicle->ID, 'vehicle_model', true),
            'license_plate' => get_post_meta($vehicle->ID, 'license_plate', true),
            'seating' => get_post_meta($vehicle->ID, 'seating_capacity', true),
            'year_manufacture' => get_post_meta($vehicle->ID, 'year_manufacture', true),
            'owner_name' => get_user_meta($owner->ID, 'full_name', true) ?: $owner->user_login,
            'owner_mobile' => get_user_meta($owner->ID, 'mobile_number', true),
            'owner_email' => $owner->user_email,
            'owner_url' => admin_url('admin.php?page=shuttle-vehicle-owners&owner_id=' . $owner->ID),
            'vehicle_url' => admin_url('admin.php?page=shuttle-vehicle-owners&vehicle_id=' . $vehicle->ID),
        );
        
        if ($has_reservation) {
            $vehicle_data['reservation_info'] = $vehicle_reservations;
            $reserved_vehicles[] = $vehicle_data;
        } else {
            $available_vehicles[] = $vehicle_data;
        }
    }
    
    wp_send_json_success(array(
        'available' => $available_vehicles,
        'reserved' => $reserved_vehicles
    ));
}

/**
 * Get vehicles owned by a user.
 */
private function get_owner_vehicles($owner_id) {
    $args = array(
        'post_type' => 'vehicle',
        'author' => $owner_id,
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    $query = new WP_Query($args);
    
    return $query->posts;
}

/**
 * AJAX handler to get all vehicles availability data.
 */
public function get_all_vehicles_availability() {
    check_ajax_referer('admin_availability_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'shuttle-vehicle-manager')));
        return;
    }
    
    $args = array(
        'post_type' => 'vehicle',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    $vehicles = get_posts($args);
    $all_data = array();
    
    foreach ($vehicles as $vehicle) {
        $owner = get_userdata($vehicle->post_author);
        
        // Get reservation data instead of availability data
        $reservation_data = get_post_meta($vehicle->ID, 'availability_data', true);
        $reservation_array = $reservation_data ? json_decode($reservation_data, true) : array();
        
        // Build date map for reserved dates only
        $reserved_dates = array();
        foreach ($reservation_array as $entry) {
            if (isset($entry['dates']) && is_array($entry['dates'])) {
                foreach ($entry['dates'] as $date) {
                    $reserved_dates[$date] = array(
                        'available' => false, // Reserved dates are not available
                        'note' => isset($entry['note']) ? $entry['note'] : ''
                    );
                }
            }
        }
        
        // For admin view, we'll pass both reserved dates and the fact that all other dates are available
        $all_data[$vehicle->ID] = array(
            'make' => get_post_meta($vehicle->ID, 'vehicle_make', true),
            'model' => get_post_meta($vehicle->ID, 'vehicle_model', true),
            'license_plate' => get_post_meta($vehicle->ID, 'license_plate', true),
            'type' => get_post_meta($vehicle->ID, 'vehicle_type', true),
            'seating' => get_post_meta($vehicle->ID, 'seating_capacity', true),
            'owner_name' => get_user_meta($owner->ID, 'full_name', true) ?: $owner->user_login,
            'owner_mobile' => get_user_meta($owner->ID, 'mobile_number', true),
            'owner_url' => admin_url('admin.php?page=shuttle-vehicle-owners&owner_id=' . $owner->ID),
            'vehicle_url' => admin_url('admin.php?page=shuttle-vehicle-owners&vehicle_id=' . $vehicle->ID),
            'dates' => $reserved_dates,
            'default_available' => true // All unmarked dates are available
        );
    }
    
    wp_send_json_success($all_data);
}

/**
 * AJAX handler for verifying vehicles.
 */
public function verify_vehicle() {
    check_ajax_referer('shuttle_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'shuttle-vehicle-manager')));
        return;
    }
    
    $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
    
    if (empty($vehicle_id)) {
        wp_send_json_error(array('message' => __('Invalid vehicle ID.', 'shuttle-vehicle-manager')));
        return;
    }
    
    // Set vehicle status to verified
    wp_set_object_terms($vehicle_id, 'verified', 'vehicle_status');
    
    wp_send_json_success(array('message' => __('Vehicle has been verified successfully!', 'shuttle-vehicle-manager')));
}

/**
 * AJAX handler for verifying owners.
 */
public function verify_owner() {
    check_ajax_referer('shuttle_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'shuttle-vehicle-manager')));
        return;
    }
    
    $owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;
    
    if (empty($owner_id)) {
        wp_send_json_error(array('message' => __('Invalid owner ID.', 'shuttle-vehicle-manager')));
        return;
    }
    
    // Set owner status to verified
    update_user_meta($owner_id, 'profile_status', 'verified');
    
    wp_send_json_success(array('message' => __('Owner has been verified successfully!', 'shuttle-vehicle-manager')));
}

/**
 * AJAX handler for PDF export
 */
public function export_availability_pdf() {
    check_ajax_referer('export_pdf_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'shuttle-vehicle-manager')));
        return;
    }
    
    $data = json_decode(stripslashes($_POST['data']), true);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    
    // For PDF generation, we'll use a simple HTML to PDF approach
    // You can use libraries like TCPDF, mPDF, or Dompdf
    
    // For now, let's create a simple HTML that can be printed as PDF
    $html = $this->generate_pdf_html($data, $start_date, $end_date);
    
    // Send as downloadable HTML (user can print to PDF)
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="vehicle_availability_' . $start_date . '_to_' . $end_date . '.html"');
    echo $html;
    exit;
}

// /**
//  * Generate HTML for PDF export
//  */
// private function generate_pdf_html($data, $start_date, $end_date) {
//     $html = '<!DOCTYPE html>
//     <html>
//     <head>
//         <meta charset="UTF-8">
//         <title>Vehicle Availability Report</title>
//         <style>
//             body { font-family: Arial, sans-serif; margin: 20px; }
//             h1, h2 { color: #333; }
//             table { width: 100%; border-collapse: collapse; margin: 20px 0; }
//             th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
//             th { background-color: #f2f2f2; font-weight: bold; }
//             .header { margin-bottom: 30px; }
//             .section { margin: 30px 0; }
//             .available { color: #4CAF50; }
//             .reserved { color: #f44336; }
//             @media print {
//                 .page-break { page-break-after: always; }
//             }
//         </style>
//     </head>
//     <body>
//         <div class="header">
//             <h1>Vehicle Availability Report</h1>
//             <p><strong>Date Range:</strong> ' . esc_html($start_date) . ' to ' . esc_html($end_date) . '</p>
//             <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
//         </div>';
    
//     // Available Vehicles Section
//     $html .= '<div class="section">
//         <h2 class="available">Available Vehicles (' . count($data['available']) . ')</h2>
//         <table>
//             <thead>
//                 <tr>
//                     <th>Vehicle</th>
//                     <th>License Plate</th>
//                     <th>Type</th>
//                     <th>Seating</th>
//                     <th>Owner</th>
//                     <th>Contact</th>
//                 </tr>
//             </thead>
//             <tbody>';
    
//     foreach ($data['available'] as $vehicle) {
//         $html .= '<tr>
//             <td>' . esc_html($vehicle['make'] . ' ' . $vehicle['model']) . '</td>
//             <td>' . esc_html($vehicle['license_plate']) . '</td>
//             <td>' . esc_html($vehicle['type']) . '</td>
//             <td>' . esc_html($vehicle['seating']) . ' passengers</td>
//             <td>' . esc_html($vehicle['owner_name']) . '</td>
//             <td>' . esc_html($vehicle['owner_mobile']) . '<br>' . esc_html($vehicle['owner_email']) . '</td>
//         </tr>';
//     }
    
//     $html .= '</tbody></table></div>';
    
//     // Page break for printing
//     $html .= '<div class="page-break"></div>';
    
//     // Reserved Vehicles Section
//     $html .= '<div class="section">
//         <h2 class="reserved">Reserved Vehicles (' . count($data['reserved']) . ')</h2>
//         <table>
//             <thead>
//                 <tr>
//                     <th>Vehicle</th>
//                     <th>License Plate</th>
//                     <th>Type</th>
//                     <th>Owner</th>
//                     <th>Reserved Dates</th>
//                     <th>Notes</th>
//                 </tr>
//             </thead>
//             <tbody>';
    
//     foreach ($data['reserved'] as $vehicle) {
//         $reservation_info = '';
//         if (!empty($vehicle['reservation_info'])) {
//             foreach ($vehicle['reservation_info'] as $res) {
//                 $reservation_info .= implode(', ', $res['dates']);
//                 if (!empty($res['note'])) {
//                     $reservation_info .= ' (' . esc_html($res['note']) . ')';
//                 }
//                 $reservation_info .= '<br>';
//             }
//         }
        
//         $html .= '<tr>
//             <td>' . esc_html($vehicle['make'] . ' ' . $vehicle['model']) . '</td>
//             <td>' . esc_html($vehicle['license_plate']) . '</td>
//             <td>' . esc_html($vehicle['type']) . '</td>
//             <td>' . esc_html($vehicle['owner_name']) . '<br>' . esc_html($vehicle['owner_mobile']) . '</td>
//             <td>' . $reservation_info . '</td>
//             <td>' . (isset($vehicle['reservation_info'][0]['note']) ? esc_html($vehicle['reservation_info'][0]['note']) : '') . '</td>
//         </tr>';
//     }
    
//     $html .= '</tbody></table></div>';
    
//     $html .= '</body></html>';
    
//     return $html;
// }

} 