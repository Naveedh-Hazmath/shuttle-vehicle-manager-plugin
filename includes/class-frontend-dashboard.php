<?php
/**
 * Handles the frontend dashboard functionality.
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Frontend_Dashboard {

    /**
     * Render the frontend dashboard.
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return $this->render_login_form();
        }
        
        $current_user = wp_get_current_user();
        
        // Check if user is a vehicle owner, but don't change admin roles
        if (!in_array('vehicle_owner', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            // Only add vehicle_owner role for non-admins without removing existing roles
            $current_user->add_role('vehicle_owner');
        }
        
        ob_start();
        
        // Tabs navigation
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'profile';
        
        ?>
        <div class="svm-dashboard-container">
            <div class="svm-dashboard-tabs">
                <a href="?tab=profile" class="tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"><?php _e('My Profile', 'shuttle-vehicle-manager'); ?></a>
                <a href="?tab=vehicles" class="tab <?php echo $active_tab === 'vehicles' ? 'active' : ''; ?>"><?php _e('My Vehicles', 'shuttle-vehicle-manager'); ?></a>
                <a href="?tab=availability" class="tab <?php echo $active_tab === 'availability' ? 'active' : ''; ?>"><?php _e('Availability', 'shuttle-vehicle-manager'); ?></a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="tab logout"><?php _e('Logout', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <div class="svm-dashboard-content">
                <?php
                switch ($active_tab) {
                    case 'profile':
                        $this->render_profile_tab();
                        break;
                    case 'vehicles':
                        $this->render_vehicles_tab();
                        break;
                    case 'add':
                        $this->render_add_vehicle_form();
                        break;
                    case 'edit':
                        $vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;
                        $this->render_edit_vehicle_form($vehicle_id);
                        break;
                    case 'availability':
                        $vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;
                        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

                        if ($vehicle_id > 0 && $action === 'calendar') {
                            // If vehicle_id and action=calendar are present, show the specific vehicle's calendar
                            $this->render_availability_calendar($vehicle_id);
                        } else {
                            // Otherwise, show the list of vehicles for availability management
                            $this->render_availability_tab();
                        }
                        break;
                }
                ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render the login/registration form.
     */
    private function render_login_form() {
    ob_start();
    
    ?>
        <style>
            /* Font import for Open Sans */
            @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap');

            /* Apply Open Sans to all form inputs and their placeholders in both login and register forms */
            .svm-auth-container input[type="text"],
            .svm-auth-container input[type="password"],
            .svm-auth-container input[type="email"],
            .svm-auth-container input[type="tel"],
            .svm-auth-container textarea,
            .svm-auth-container select {
                font-family: "Open Sans", Sans-serif !important;
            }

            /* Placeholder specific styling for all browsers */
            .svm-auth-container input::-webkit-input-placeholder {
                font-family: "Open Sans", Sans-serif !important;
            }

            .svm-auth-container input::-moz-placeholder {
                font-family: "Open Sans", Sans-serif !important;
            }

            .svm-auth-container input:-ms-input-placeholder {
                font-family: "Open Sans", Sans-serif !important;
            }

            .svm-auth-container input:-moz-placeholder {
                font-family: "Open Sans", Sans-serif !important;
            }

            .svm-auth-container input::placeholder {
                font-family: "Open Sans", Sans-serif !important;
            }

            /* Apply to all text elements in both forms for consistency */
            .svm-auth-container label,
            .svm-auth-container h2,
            .svm-auth-container p,
            .svm-auth-container .tab,
            .svm-auth-container .svm-button,
            .svm-auth-container .form-notice {
                font-family: "Open Sans", Sans-serif !important;
            }
        </style>

        <div class="svm-auth-container">
            <div class="svm-auth-tabs">
                <a href="#login" class="tab active"><?php _e('Login', 'shuttle-vehicle-manager'); ?></a>
                <a href="#register" class="tab"><?php _e('Register', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <div class="svm-auth-content">
                <div id="login" class="auth-form active">
                    <h2><?php _e('Login', 'shuttle-vehicle-manager'); ?></h2>
                    <form id="shuttle-login-form" method="post">
                        <div class="form-group">
                            <label for="login-mobile"><?php _e('Mobile Number', 'shuttle-vehicle-manager'); ?></label>
                            <input type="text" id="login-mobile" name="mobile_number" placeholder="Enter your mobile number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="login-password"><?php _e('Password', 'shuttle-vehicle-manager'); ?></label>
                            <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="remember" value="1">
                                <?php _e('Remember Me', 'shuttle-vehicle-manager'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="svm-button"><?php _e('Login', 'shuttle-vehicle-manager'); ?></button>
                        </div>
                        
                        <div class="form-message"></div>
                        <?php wp_nonce_field('shuttle_vehicle_nonce', 'nonce'); ?>
                    </form>
                </div>
                
                <div id="register" class="auth-form">
                    <h2><?php _e('Register', 'shuttle-vehicle-manager'); ?></h2>
                    <form id="shuttle-register-form" method="post">
                        <div class="form-group">
                            <label for="register-mobile"><?php _e('Mobile Number', 'shuttle-vehicle-manager'); ?></label>
                            <input type="text" id="register-mobile" name="mobile_number" placeholder="Enter your mobile number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-password"><?php _e('Password', 'shuttle-vehicle-manager'); ?></label>
                            <input type="password" id="register-password" name="password" placeholder="Create a password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-confirm-password"><?php _e('Confirm Password', 'shuttle-vehicle-manager'); ?></label>
                            <input type="password" id="register-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="svm-button"><?php _e('Register', 'shuttle-vehicle-manager'); ?></button>
                        </div>

                        <div class="form-notice">
                            <p><span class="required-star">*</span> <?php _e('Registration is 100% free. No hidden charges, no membership fees or payments required to join our platform.', 'shuttle-vehicle-manager'); ?></p>
                        </div>
                        
                        <div class="form-message"></div>
                        <?php wp_nonce_field('shuttle_vehicle_nonce', 'nonce'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
        }
    
    /**
     * Render the profile tab.
     */
    private function render_profile_tab() {
        $owner_profile = new Shuttle_Owner_Profile();
        $owner_profile->render_profile_form();
    }

    /**
     * Render the vehicles tab.
     */
    private function render_vehicles_tab() {
        $current_user = wp_get_current_user();
        
        // Check if profile is complete
        $owner_profile = new Shuttle_Owner_Profile();
        $profile_fields = $owner_profile->get_profile_fields();
        $profile_complete = true;
        
        foreach ($profile_fields as $key => $field) {
            if ($field['required']) {
                $value = get_user_meta($current_user->ID, $key, true);
                if (empty($value)) {
                    $profile_complete = false;
                    break;
                }
            }
        }
        
        if (!$profile_complete) {
            ?>
            <div class="svm-notice svm-notice-warning">
                <p><?php _e('Please complete your profile before adding vehicles.', 'shuttle-vehicle-manager'); ?></p>
                <a href="?tab=profile" class="svm-button"><?php _e('Complete Profile', 'shuttle-vehicle-manager'); ?></a>
            </div>
            <?php
            return;
        }
        
        $args = array(
            'post_type' => 'vehicle',
            'author' => $current_user->ID,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $vehicles = get_posts($args);
        
        ?>
        <div class="svm-vehicles-list">
            <div class="svm-section-header">
                <h2><?php _e('My Vehicles', 'shuttle-vehicle-manager'); ?></h2>
                <a href="?tab=add" class="svm-button"><?php _e('Add New Vehicle', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <?php if (!empty($vehicles)) : ?>
                <div class="svm-vehicle-cards">
                    <?php foreach ($vehicles as $vehicle) : 
                        $type = get_post_meta($vehicle->ID, 'vehicle_type', true);
                        $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
                        $license_plate = get_post_meta($vehicle->ID, 'license_plate', true);
                        $seating = get_post_meta($vehicle->ID, 'seating_capacity', true);
                        $vehicle_images = get_post_meta($vehicle->ID, 'vehicle_images', true);
                        $featured_image = !empty($vehicle_images) && is_array($vehicle_images) ? $vehicle_images[0] : '';
                        
                        // Get status
                        $status = 'pending';
                        $terms = get_the_terms($vehicle->ID, 'vehicle_status');
                        if (!empty($terms) && !is_wp_error($terms)) {
                            $status = $terms[0]->slug;
                        }
                    ?>
                        <div class="svm-vehicle-card">
                            <div class="svm-vehicle-status status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </div>
                            
                            <div class="svm-vehicle-image">
                                <?php if (!empty($featured_image)) : ?>
                                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($type . ' ' . $model); ?>">
                                <?php else : ?>
                                    <div class="svm-vehicle-placeholder">
                                        <i class="dashicons dashicons-car"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="svm-vehicle-details">
                                <h3><?php echo esc_html($type . ' ' . $model); ?></h3>
                                <p><strong><?php _e('License Plate:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($license_plate); ?></p>
                                <p><strong><?php _e('Seating:', 'shuttle-vehicle-manager'); ?></strong> <?php echo esc_html($seating); ?> passengers</p>
                            </div>
                            
                            <div class="svm-vehicle-actions">
                                <a href="?tab=edit&vehicle_id=<?php echo $vehicle->ID; ?>" class="svm-button svm-button-sm"><?php _e('Edit', 'shuttle-vehicle-manager'); ?></a>
                                <a href="?tab=availability&vehicle_id=<?php echo $vehicle->ID; ?>&action=calendar" class="svm-button svm-button-sm"><?php _e('Availability', 'shuttle-vehicle-manager'); ?></a>
                                <a href="#" class="svm-button svm-button-sm svm-button-delete delete-vehicle" data-id="<?php echo $vehicle->ID; ?>"><?php _e('Delete', 'shuttle-vehicle-manager'); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="svm-empty-state">
                    <div class="svm-empty-icon">
                        <i class="dashicons dashicons-car"></i>
                    </div>
                    <p><?php _e('You haven\'t registered any vehicles yet.', 'shuttle-vehicle-manager'); ?></p>
                    <a href="?tab=add" class="svm-button"><?php _e('Add Your First Vehicle', 'shuttle-vehicle-manager'); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the add vehicle form.
     */
    private function render_add_vehicle_form() {
        $current_user = wp_get_current_user();
        
        // Check if profile is complete
        $owner_profile = new Shuttle_Owner_Profile();
        $profile_fields = $owner_profile->get_profile_fields();
        $profile_complete = true;
        $missing_fields = array();
        
        foreach ($profile_fields as $key => $field) {
            if ($field['required']) {
                $value = get_user_meta($current_user->ID, $key, true);
                if (empty($value)) {
                    $profile_complete = false;
                    $missing_fields[] = $field['label'];
                }
            }
        }
        
        if (!$profile_complete) {
            ?>
            <div class="svm-notice svm-notice-warning">
                <p><?php _e('Please complete your profile before adding vehicles.', 'shuttle-vehicle-manager'); ?></p>
                <p><?php _e('Missing fields:', 'shuttle-vehicle-manager'); ?> <?php echo esc_html(implode(', ', $missing_fields)); ?></p>
                <a href="?tab=profile" class="svm-button"><?php _e('Complete Profile', 'shuttle-vehicle-manager'); ?></a>
            </div>
            <?php
            return;
        }
        
        ?>
        <style>
        /* Features checkbox styling */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .feature-item:hover {
            background: #f0f0f0;
        }
        
        .feature-item input[type="checkbox"] {
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .feature-item label {
            cursor: pointer;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
        }
        
        .features-section {
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .features-section h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <div class="svm-vehicle-form">
            <div class="svm-section-header">
                <h2><?php _e('Add New Vehicle', 'shuttle-vehicle-manager'); ?></h2>
                <a href="?tab=vehicles" class="svm-button svm-button-secondary"><?php _e('Back to Vehicles', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <form id="shuttle-add-vehicle-form" method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><?php _e('Vehicle Details', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="vehicle_type"><?php _e('Vehicle Type', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <select id="vehicle_type" name="vehicle_type" required>
                                <option value=""><?php _e('Select Vehicle Type', 'shuttle-vehicle-manager'); ?></option>
                                <option value="Alto">Alto</option>
                                <option value="Wagon R">Wagon R</option>
                                <option value="Axio / Prius">Axio / Prius</option>
                                <option value="SUV">SUV</option>
                                <option value="Flat Roof Van (Caravan/Dolphin)">Flat Roof Van (Caravan/Dolphin)</option>
                                <option value="High Roof Van (Dolphin)">High Roof Van (Dolphin)</option>
                                <option value="KDH Flat Roof">KDH Flat Roof</option>
                                <option value="KDH High Roof">KDH High Roof</option>
                                <option value="Toyota Coaster">Toyota Coaster</option>
                                <option value="Mitsubishi Fuso Rosa">Mitsubishi Fuso Rosa</option>
                                <option value="Super Luxury Tourist Coach">Super Luxury Tourist Coach</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="vehicle_model"><?php _e('Vehicle Model', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="text" id="vehicle_model" name="vehicle_model" placeholder="e.g., LX, EX, Touring" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="year_manufacture"><?php _e('Year of Manufacture', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="year_manufacture" name="year_manufacture" min="1900" max="2099" step="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year_registration"><?php _e('Year of Registration', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="year_registration" name="year_registration" min="1900" max="2099" step="1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="license_plate"><?php _e('License Plate Number', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="text" id="license_plate" name="license_plate" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="seating_capacity"><?php _e('Seating Capacity', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="seating_capacity" name="seating_capacity" min="1" max="99" step="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section features-section">
                    <h3><?php _e('Passenger Comfort & Safety Features', 'shuttle-vehicle-manager'); ?></h3>
                    <p class="description"><?php _e('Select all features available in your vehicle:', 'shuttle-vehicle-manager'); ?></p>
                    
                    <div class="features-grid">
                        <?php
                        $features = array(
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
                        
                        foreach ($features as $key => $label) {
                            ?>
                            <div class="feature-item">
                                <input type="checkbox" id="feature_<?php echo $key; ?>" name="vehicle_features[]" value="<?php echo esc_attr($key); ?>">
                                <label for="feature_<?php echo $key; ?>"><?php echo esc_html($label); ?></label>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><?php _e('Vehicle Documents', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <div class="form-group">
                        <label for="rc_document"><?php _e('RC Document', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="rc_document" name="rc_document" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="description"><?php _e('Upload RC document (optional).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="insurance_document"><?php _e('Insurance Document', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="insurance_document" name="insurance_document" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="description"><?php _e('Upload insurance document (optional).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="emission_document"><?php _e('Emission Document', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="emission_document" name="emission_document" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="description"><?php _e('Upload emission document (optional).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="revenue_license_document"><?php _e('Revenue License Document', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="revenue_license_document" name="revenue_license_document" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="description"><?php _e('Upload revenue license document (optional).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="fitness_document"><?php _e('Fitness Document', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="fitness_document" name="fitness_document" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="description"><?php _e('Upload fitness document (optional).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><?php _e('Vehicle Photos', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <div class="form-group">
                        <label for="vehicle_images"><?php _e('Vehicle Images (Exterior & Interior)', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                        <input type="file" id="vehicle_images" name="vehicle_images[]" accept=".jpg,.jpeg,.png" multiple required>
                        <p class="description"><?php _e('Upload multiple images of your vehicle (exterior and interior).', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="svm-button"><?php _e('Register Vehicle', 'shuttle-vehicle-manager'); ?></button>
                </div>
                
                <div class="form-message"></div>
                <?php wp_nonce_field('shuttle_vehicle_nonce', 'nonce'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the edit vehicle form.
     */
    private function render_edit_vehicle_form($vehicle_id) {
        $vehicle = get_post($vehicle_id);
        
        if (!$vehicle || $vehicle->post_type !== 'vehicle' || $vehicle->post_author != get_current_user_id()) {
            ?>
            <div class="svm-notice svm-notice-error">
                <p><?php _e('Invalid vehicle or you don\'t have permission to edit this vehicle.', 'shuttle-vehicle-manager'); ?></p>
            </div>
            <?php
            return;
        }
        
        $type = get_post_meta($vehicle->ID, 'vehicle_type', true);
        $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
        $year_manufacture = get_post_meta($vehicle->ID, 'year_manufacture', true);
        $year_registration = get_post_meta($vehicle->ID, 'year_registration', true);
        $license_plate = get_post_meta($vehicle->ID, 'license_plate', true);
        $seating_capacity = get_post_meta($vehicle->ID, 'seating_capacity', true);
        $vehicle_features = get_post_meta($vehicle->ID, 'vehicle_features', true);
        if (!is_array($vehicle_features)) {
            $vehicle_features = array();
        }
        
        $rc_doc = get_post_meta($vehicle->ID, 'rc_document', true);
        $insurance_doc = get_post_meta($vehicle->ID, 'insurance_document', true);
        $emission_doc = get_post_meta($vehicle->ID, 'emission_document', true);
        $revenue_license_doc = get_post_meta($vehicle->ID, 'revenue_license_document', true);
        $fitness_doc = get_post_meta($vehicle->ID, 'fitness_document', true);
        
        $vehicle_images = get_post_meta($vehicle->ID, 'vehicle_images', true);
        
        // Get status
        $status = 'pending';
        $terms = get_the_terms($vehicle->ID, 'vehicle_status');
        if (!empty($terms) && !is_wp_error($terms)) {
            $status = $terms[0]->slug;
        }
        
        ?>
        <style>
        /* Features checkbox styling */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .feature-item:hover {
            background: #f0f0f0;
        }
        
        .feature-item input[type="checkbox"] {
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .feature-item label {
            cursor: pointer;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
        }
        
        .features-section {
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .features-section h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <div class="svm-vehicle-form">
            <div class="svm-section-header">
                <h2><?php _e('Edit Vehicle', 'shuttle-vehicle-manager'); ?></h2>
                <a href="?tab=vehicles" class="svm-button svm-button-secondary"><?php _e('Back to Vehicles', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <div class="svm-vehicle-status-banner status-<?php echo esc_attr($status); ?>">
                <div class="status-icon">
                    <i class="dashicons <?php echo $status === 'verified' ? 'dashicons-yes-alt' : 'dashicons-clock'; ?>"></i>
                </div>
                <div class="status-text">
                    <?php if ($status === 'verified') : ?>
                        <?php _e('This vehicle has been verified.', 'shuttle-vehicle-manager'); ?>
                    <?php else : ?>
                        <?php _e('This vehicle is pending verification.', 'shuttle-vehicle-manager'); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <form id="shuttle-edit-vehicle-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle->ID; ?>">
                
                <div class="form-section">
                    <h3><?php _e('Vehicle Details', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="vehicle_type"><?php _e('Vehicle Type', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <select id="vehicle_type" name="vehicle_type" required>
                                <option value=""><?php _e('Select Vehicle Type', 'shuttle-vehicle-manager'); ?></option>
                                <option value="Alto" <?php selected($type, 'Alto'); ?>>Alto</option>
                                <option value="Wagon R" <?php selected($type, 'Wagon R'); ?>>Wagon R</option>
                                <option value="Axio / Prius" <?php selected($type, 'Axio / Prius'); ?>>Axio / Prius</option>
                                <option value="SUV" <?php selected($type, 'SUV'); ?>>SUV</option>
                                <option value="Flat Roof Van (Caravan/Dolphin)" <?php selected($type, 'Flat Roof Van (Caravan/Dolphin)'); ?>>Flat Roof Van (Caravan/Dolphin)</option>
                                <option value="High Roof Van (Dolphin)" <?php selected($type, 'High Roof Van (Dolphin)'); ?>>High Roof Van (Dolphin)</option>
                                <option value="KDH Flat Roof" <?php selected($type, 'KDH Flat Roof'); ?>>KDH Flat Roof</option>
                                <option value="KDH High Roof" <?php selected($type, 'KDH High Roof'); ?>>KDH High Roof</option>
                                <option value="Toyota Coaster" <?php selected($type, 'Toyota Coaster'); ?>>Toyota Coaster</option>
                                <option value="Mitsubishi Fuso Rosa" <?php selected($type, 'Mitsubishi Fuso Rosa'); ?>>Mitsubishi Fuso Rosa</option>
                                <option value="Super Luxury Tourist Coach" <?php selected($type, 'Super Luxury Tourist Coach'); ?>>Super Luxury Tourist Coach</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="vehicle_model"><?php _e('Vehicle Model', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="text" id="vehicle_model" name="vehicle_model" value="<?php echo esc_attr($model); ?>" placeholder="e.g., LX, EX, Touring" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year_manufacture"><?php _e('Year of Manufacture', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="year_manufacture" name="year_manufacture" value="<?php echo esc_attr($year_manufacture); ?>" min="1900" max="2099" step="1" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="year_registration"><?php _e('Year of Registration', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="year_registration" name="year_registration" value="<?php echo esc_attr($year_registration); ?>" min="1900" max="2099" step="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="license_plate"><?php _e('License Plate Number', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="text" id="license_plate" name="license_plate" value="<?php echo esc_attr($license_plate); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="seating_capacity"><?php _e('Seating Capacity', 'shuttle-vehicle-manager'); ?> <span class="required">*</span></label>
                            <input type="number" id="seating_capacity" name="seating_capacity" value="<?php echo esc_attr($seating_capacity); ?>" min="1" max="99" step="1" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section features-section">
                    <h3><?php _e('Passenger Comfort & Safety Features', 'shuttle-vehicle-manager'); ?></h3>
                    <p class="description"><?php _e('Select all features available in your vehicle:', 'shuttle-vehicle-manager'); ?></p>
                    
                    <div class="features-grid">
                        <?php
                        $features = array(
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
                        
                        foreach ($features as $key => $label) {
                            $checked = in_array($key, $vehicle_features) ? 'checked' : '';
                            ?>
                            <div class="feature-item">
                                <input type="checkbox" id="feature_<?php echo $key; ?>" name="vehicle_features[]" value="<?php echo esc_attr($key); ?>" <?php echo $checked; ?>>
                                <label for="feature_<?php echo $key; ?>"><?php echo esc_html($label); ?></label>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><?php _e('Vehicle Photos', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <?php if (!empty($vehicle_images) && is_array($vehicle_images)) : ?>
                        <div class="current-vehicle-images">
                            <p><?php _e('Current Images:', 'shuttle-vehicle-manager'); ?></p>
                            <div class="vehicle-images-gallery">
                                <?php foreach ($vehicle_images as $image) : ?>
                                    <div class="vehicle-image">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php _e('Vehicle Image', 'shuttle-vehicle-manager'); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="vehicle_images"><?php _e('Replace Vehicle Images', 'shuttle-vehicle-manager'); ?></label>
                        <input type="file" id="vehicle_images" name="vehicle_images[]" accept=".jpg,.jpeg,.png" multiple>
                        <p class="description"><?php _e('Upload new images only if you want to replace all current images.', 'shuttle-vehicle-manager'); ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="svm-button"><?php _e('Update Vehicle', 'shuttle-vehicle-manager'); ?></button>
                </div>
                
                <div class="form-message"></div>
                <?php wp_nonce_field('shuttle_vehicle_nonce', 'nonce'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render the availability tab.
     */
    private function render_availability_tab() {
        $user_id = get_current_user_id();
        $vehicles = $this->get_user_vehicles($user_id);
        
        if (empty($vehicles)) {
            ?>
            <div class="svm-notice svm-notice-info">
                <p><?php _e('You have not registered any vehicles yet.', 'shuttle-vehicle-manager'); ?></p>
                <a href="?tab=vehicles" class="svm-button"><?php _e('Add Your First Vehicle', 'shuttle-vehicle-manager'); ?></a>
            </div>
            <?php
            return;
        }
        ?>
        
        <div class="svm-availability-section">
            <h2><?php _e('Manage Vehicle Reservations', 'shuttle-vehicle-manager'); ?></h2>
            <p><?php _e('Click on a vehicle to manage its reservation calendar. All dates are available by default - you only need to mark dates when your vehicle is reserved.', 'shuttle-vehicle-manager'); ?></p>
            
            <div class="svm-vehicles-grid">
                <?php foreach ($vehicles as $vehicle) : 
                    $make = get_post_meta($vehicle->ID, 'vehicle_make', true);
                    $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
                    $license = get_post_meta($vehicle->ID, 'license_plate', true);
                    
                    // Get reservation count for this month
                    $reservation_data = get_post_meta($vehicle->ID, 'availability_data', true);
                    $reservations = $reservation_data ? json_decode($reservation_data, true) : array();
                    $current_month_reservations = 0;
                    
                    if (!empty($reservations)) {
                        $current_month = date('Y-m');
                        foreach ($reservations as $reservation) {
                            if (isset($reservation['dates'])) {
                                foreach ($reservation['dates'] as $date) {
                                    if (strpos($date, $current_month) === 0) {
                                        $current_month_reservations++;
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <div class="svm-vehicle-card">
                        <div class="vehicle-header">
                            <h3><?php echo esc_html($type . ' ' . $model); ?></h3>
                            <span class="vehicle-license"><?php echo esc_html($license); ?></span>
                        </div>
                        
                        <?php if ($current_month_reservations > 0) : ?>
                            <div class="reservation-status">
                                <span class="status-badge reserved">
                                    <?php printf(_n('%d day reserved this month', '%d days reserved this month', $current_month_reservations, 'shuttle-vehicle-manager'), $current_month_reservations); ?>
                                </span>
                            </div>
                        <?php else : ?>
                            <div class="reservation-status">
                                <span class="status-badge available">
                                    <?php _e('Fully available this month', 'shuttle-vehicle-manager'); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vehicle-actions">
                            <a href="?tab=availability&action=calendar&vehicle_id=<?php echo $vehicle->ID; ?>" class="svm-button">
                                <?php _e('Manage Calendar', 'shuttle-vehicle-manager'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .svm-availability-section {
            padding: 20px 0;
        }
        
        .svm-availability-section h2 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .svm-availability-section > p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .svm-vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .svm-vehicle-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        
        .svm-vehicle-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .vehicle-header {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .vehicle-header h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 18px;
        }
        
        .vehicle-license {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        
        .reservation-status {
            margin: 15px 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-badge.available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-badge.reserved {
            background: #ffebee;
            color: #c62828;
        }
        
        .vehicle-actions {
            margin-top: 15px;
        }
        
        .vehicle-actions .svm-button {
            display: block;
            width: 100%;
            text-align: center;
            background: #FFCB05;
            color: #000;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .vehicle-actions .svm-button:hover {
            background: #000;
            color: #FFCB05;
        }
        
        @media (max-width: 768px) {
            .svm-vehicles-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .svm-availability-section > p {
                font-size: 14px;
            }
        }
        </style>
        <?php
    }

    /**
 * Render the availability calendar.
 */
    private function render_availability_calendar($vehicle_id) {
        $vehicle = get_post($vehicle_id);
        
        if (!$vehicle || $vehicle->post_type !== 'vehicle' || $vehicle->post_author != get_current_user_id()) {
            ?>
            <div class="svm-notice svm-notice-error">
                <p><?php _e('Invalid vehicle or you don\'t have permission to edit this vehicle.', 'shuttle-vehicle-manager'); ?></p>
            </div>
            <?php
            return;
        }
        
        $make = get_post_meta($vehicle->ID, 'vehicle_make', true);
        $model = get_post_meta($vehicle->ID, 'vehicle_model', true);
        
        // Get reservation data (using same meta key for backward compatibility)
        $reservation_data_raw = get_post_meta($vehicle->ID, 'availability_data', true);
        $reservation_data = array();
        
        if (!empty($reservation_data_raw)) {
            $decoded = json_decode($reservation_data_raw, true);
            if (is_array($decoded)) {
                $reservation_data = $decoded;
            }
        }
        
        ?>
        <style>
            /* Frontend Calendar Inline Styles */
            .svm-availability-calendar {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                max-width: 100% !important;
                overflow-x: hidden !important;
            }

            /* Calendar Legend */
            .svm-calendar-legend {
                display: flex !important;
                gap: 20px !important;
                margin-bottom: 20px !important;
                padding: 15px !important;
                background: #f5f5f5 !important;
                border-radius: 6px !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
            }

            .legend-item {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                font-size: 14px !important;
            }

            .legend-color {
                width: 16px !important;
                height: 16px !important;
                border-radius: 4px !important;
                display: inline-block !important;
            }

            .legend-color.available {
                background-color: #4CAF50 !important;
            }

            .legend-color.reserved {
                background-color: #FF4D4D !important;
            }

            .legend-color.today {
                background-color: #FFCB05 !important;
                border: 2px solid #000 !important;
            }

            /* Calendar container */
            .svm-calendar-container {
                padding: 20px;
                background: #fff;
                border-radius: 8px;
            }

            .svm-calendar-instructions {
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 6px;
            }

            .svm-calendar-instructions p {
                margin: 0;
                color: #666;
            }

            /* Date range mode */
            .date-range-mode {
                margin: 20px 0 !important;
                padding: 15px !important;
                background: #f8f9fa !important;
                border-radius: 6px !important;
                display: flex !important;
                align-items: center !important;
                gap: 20px !important;
                flex-wrap: wrap !important;
            }

            .date-range-toggle {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
            }

            .date-range-toggle input[type="checkbox"] {
                width: 20px !important;
                height: 20px !important;
                cursor: pointer !important;
            }

            .range-selection-info {
                font-size: 14px !important;
                color: #666 !important;
                font-style: italic !important;
                flex-grow: 1 !important;
            }

            /* Calendar navigation */
            .calendar-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                margin-bottom: 20px !important;
                gap: 10px !important;
            }

            .cal-nav {
                background: #FFCB05 !important;
                border: none !important;
                color: #000 !important;
                padding: 10px 20px !important;
                cursor: pointer !important;
                border-radius: 4px !important;
                font-weight: bold !important;
                font-size: 18px !important;
                transition: all 0.3s ease !important;
                flex-shrink: 0 !important;
            }

            .cal-nav:hover {
                background: #000 !important;
                color: #FFCB05 !important;
            }

            .calendar-header h3 {
                margin: 0 !important;
                font-size: 24px !important;
                color: #333 !important;
                text-align: center !important;
                flex-grow: 1 !important;
            }

            /* Calendar table - FIXED GRID LAYOUT */
            .svm-interactive-calendar {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .calendar-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 20px !important;
                background: #fff !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                table-layout: fixed !important; /* Ensures equal column widths */
            }

            .calendar-table thead {
                background: #FFCB05 !important;
            }

            .calendar-table th {
                background: #FFCB05 !important;
                color: #000 !important;
                font-weight: bold !important;
                padding: 12px 8px !important;
                font-size: 14px !important;
                text-transform: uppercase !important;
                border: 1px solid #e0e0e0 !important;
                width: 14.28% !important; /* 100% / 7 days */
            }

            .calendar-table td {
                border: 1px solid #e0e0e0 !important;
                padding: 0 !important;
                height: 100px !important;
                width: 14.28% !important; /* 100% / 7 days */
                position: relative !important;
                vertical-align: top !important;
            }

            .calendar-table td.empty-cell {
                background: #fafafa !important;
            }

            .calendar-date {
                cursor: pointer !important;
                width: 100% !important;
                height: 100% !important;
                min-height: 100px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.2s ease !important;
                background: #e8f5e9 !important;
                position: relative !important;
                padding: 10px 5px !important;
                box-sizing: border-box !important;
            }

            .calendar-date:hover:not(.date-past) {
                background: #c8e6c9 !important;
                transform: scale(1.02) !important;
            }

            .date-reserved {
                background: #ffebee !important;
            }

            .date-reserved:hover {
                background: #ffcdd2 !important;
            }

            .date-today {
                box-shadow: inset 0 0 0 3px #FFCB05 !important;
            }

            .date-past {
                background: #f5f5f5 !important;
                color: #999 !important;
                cursor: not-allowed !important;
            }

            .date-past:hover {
                transform: none !important;
            }

            .date-number {
                font-weight: bold !important;
                font-size: 18px !important;
                margin-bottom: 5px !important;
                color: #333 !important;
            }

            .date-note-indicator {
                font-size: 16px !important;
                position: absolute !important;
                bottom: 5px !important;
                right: 5px !important;
            }

            /* Date range selection */
            .calendar-date.selecting {
                background: #fff3cd !important;
                box-shadow: inset 0 0 0 2px #FFCB05 !important;
            }

            .calendar-date.in-range {
                background: #fff3cd !important;
                opacity: 0.7 !important;
            }

            /* Modal Styles */
            #reservation-modal {
                display: none;
                position: fixed !important;
                z-index: 999999 !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0,0,0,0.5) !important;
            }

            #reservation-modal[style*="display: flex"] {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #reservation-modal .svm-modal-content {
                position: relative !important;
                background: #fff !important;
                width: 90% !important;
                max-width: 500px !important;
                border-radius: 8px !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
                padding: 30px !important;
                max-height: 90vh !important;
                overflow-y: auto !important;
            }

            #reservation-modal h3 {
                margin: 0 0 20px 0 !important;
                font-size: 24px !important;
                color: #333 !important;
            }

            #reservation-modal .svm-modal-close {
                position: absolute !important;
                right: 15px !important;
                top: 15px !important;
                font-size: 30px !important;
                cursor: pointer !important;
                color: #999 !important;
                line-height: 1 !important;
                background: none !important;
                border: none !important;
                padding: 0 !important;
                width: 30px !important;
                height: 30px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #reservation-modal .svm-modal-close:hover {
                color: #000 !important;
            }

            /* Form styles */
            #reservation-modal .form-group {
                margin-bottom: 20px !important;
            }

            #reservation-modal .form-group label {
                display: block !important;
                margin-bottom: 8px !important;
                font-weight: 600 !important;
                color: #333 !important;
                font-size: 14px !important;
            }

            #selected-dates-display {
                font-size: 16px !important;
                color: #333 !important;
                margin: 0 !important;
                line-height: 1.6 !important;
            }

            #selected-dates-display strong {
                color: #FF4D4D !important;
            }

            #reservation-note {
                width: 100% !important;
                padding: 10px !important;
                border: 1px solid #ddd !important;
                border-radius: 4px !important;
                font-size: 14px !important;
                resize: vertical !important;
                box-sizing: border-box !important;
                min-height: 80px !important;
            }

            /* Buttons */
            .modal-buttons {
                display: flex !important;
                gap: 10px !important;
                margin-top: 25px !important;
                justify-content: flex-end !important;
                flex-wrap: wrap !important;
            }

            .svm-button {
                padding: 10px 20px !important;
                border: none !important;
                border-radius: 4px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                transition: all 0.3s ease !important;
                text-decoration: none !important;
                display: inline-block !important;
            }

            .svm-button-reserve {
                background: #FF4D4D !important;
                color: #fff !important;
            }

            .svm-button-reserve:hover {
                background: #d32f2f !important;
            }

            .svm-button-secondary {
                background: #f5f5f5 !important;
                color: #333 !important;
            }

            .svm-button-secondary:hover {
                background: #e0e0e0 !important;
            }

            .svm-button-delete {
                background: #666 !important;
                color: #fff !important;
            }

            .svm-button-delete:hover {
                background: #333 !important;
            }

            /* Messages */
            .form-message p {
                padding: 10px 15px !important;
                border-radius: 4px !important;
                margin: 10px 0 !important;
            }

            .form-message .success {
                background: #d4edda !important;
                color: #155724 !important;
                border: 1px solid #c3e6cb !important;
            }

            .form-message .error {
                background: #f8d7da !important;
                color: #721c24 !important;
                border: 1px solid #f5c6cb !important;
            }

            .form-message .loading {
                background: #cce5ff !important;
                color: #004085 !important;
                border: 1px solid #b8daff !important;
            }

            #save-reservation[style*="display: none"],
            #delete-reservation[style*="display: none"] {
                display: none !important;
            }

            /* Tablet Responsive (768px - 1024px) */
            @media (max-width: 1024px) {
                .calendar-table td {
                    height: 80px !important;
                }
                
                .calendar-date {
                    min-height: 80px !important;
                }
                
                .date-number {
                    font-size: 16px !important;
                }
            }

            /* Mobile Responsive Styles */
            @media (max-width: 768px) {
                .svm-calendar-container {
                    padding: 10px;
                }
                
                .calendar-header {
                    flex-wrap: wrap !important;
                }
                
                .calendar-header h3 {
                    width: 100% !important;
                    order: -1 !important;
                    margin-bottom: 15px !important;
                    font-size: 20px !important;
                }
                
                .cal-nav {
                    flex: 1 !important;
                    padding: 8px 15px !important;
                    font-size: 16px !important;
                }
                
                .svm-calendar-legend {
                    gap: 10px !important;
                    padding: 10px !important;
                }
                
                .legend-item {
                    font-size: 12px !important;
                }
                
                .date-range-mode {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 10px !important;
                }
                
                /* Mobile calendar - Option A: Maintain grid with smaller cells */
                .calendar-table {
                    font-size: 11px !important;
                }
                
                .calendar-table th {
                    padding: 8px 2px !important;
                    font-size: 11px !important;
                }
                
                .calendar-table td {
                    height: 60px !important;
                }
                
                .calendar-date {
                    min-height: 60px !important;
                    padding: 5px 2px !important;
                }
                
                .date-number {
                    font-size: 14px !important;
                }
                
                .date-note-indicator {
                    font-size: 12px !important;
                    bottom: 2px !important;
                    right: 2px !important;
                }
                
                /* Modal adjustments */
                #reservation-modal .svm-modal-content {
                    width: 95% !important;
                    padding: 20px !important;
                }
                
                #reservation-modal h3 {
                    font-size: 20px !important;
                }
                
                .modal-buttons {
                    flex-direction: column !important;
                }
                
                .modal-buttons .svm-button {
                    width: 100% !important;
                }
            }

            /* Very small mobile devices */
            @media (max-width: 300px) {
                /* Option B: Scrollable calendar for very small screens */
                .svm-interactive-calendar {
                    margin: 0 -10px !important;
                    padding: 0 !important;
                }
                
                .calendar-table {        
                    min-width: 450px !important; /* Force minimum width for scrolling */
                }
                
                .calendar-table th {
                    font-size: 10px !important;
                    padding: 6px 2px !important;
                }
                
                .calendar-table td {
                    height: 50px !important;
                }
                
                .calendar-date {
                    min-height: 50px !important;
                    padding: 3px !important;
                }
                
                .date-number {
                    font-size: 12px !important;
                    margin-bottom: 2px !important;
                }
                
                .date-note-indicator {
                    font-size: 10px !important;
                }
            }

            /* Ensure proper display across browsers */
            @supports (-webkit-appearance: none) {
                /* Safari specific fixes */
                .calendar-table {
                    -webkit-border-horizontal-spacing: 0;
                    -webkit-border-vertical-spacing: 0;
                }
            }

            @-moz-document url-prefix() {
                /* Firefox specific fixes */
                .calendar-table td {
                    box-sizing: border-box;
                }
            }

            /* Print styles */
            @media print {
                .cal-nav,
                .svm-button,
                .date-range-mode {
                    display: none !important;
                }
                
                .calendar-table {
                    box-shadow: none !important;
                    border: 1px solid #000 !important;
                }
            }
        </style>
        
        <div class="svm-availability-calendar">
            <div class="svm-section-header">
                <h2><?php printf(__('Reservation Calendar: %s %s', 'shuttle-vehicle-manager'), esc_html($type), esc_html($model)); ?></h2>
                <a href="?tab=availability" class="svm-button svm-button-secondary"><?php _e('Back to Vehicles', 'shuttle-vehicle-manager'); ?></a>
            </div>
            
            <div class="svm-calendar-container">
                <div class="svm-calendar-legend">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <span><?php _e('Available', 'shuttle-vehicle-manager'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color reserved"></span>
                        <span><?php _e('Reserved', 'shuttle-vehicle-manager'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color today"></span>
                        <span><?php _e('Today', 'shuttle-vehicle-manager'); ?></span>
                    </div>
                </div>
                
                <!-- Date Range Mode Toggle -->
                <div class="date-range-mode">
                    <div class="date-range-toggle">
                        <input type="checkbox" id="enable-date-range" />
                        <label for="enable-date-range"><?php _e('Select Date Range', 'shuttle-vehicle-manager'); ?></label>
                    </div>
                    <div class="range-selection-info" style="display: none;">
                        <?php _e('Click on start date, then click on end date to select a range', 'shuttle-vehicle-manager'); ?>
                    </div>
                </div>
                
                <div class="svm-calendar-instructions">
                    <p><?php _e('All dates are available by default. Click on dates to mark them as reserved.', 'shuttle-vehicle-manager'); ?></p>
                </div>
                
                <!-- Interactive Calendar -->
                <div class="svm-interactive-calendar">
                    <div id="calendar-container"></div>
                </div>
            </div>
            
            <div class="form-message"></div>
            
            <!-- Modal for reservations -->
            <div id="reservation-modal" class="svm-modal" style="display: none;">
                <div class="svm-modal-content">
                    <span class="svm-modal-close">&times;</span>
                    <h3 id="modal-title"><?php _e('Manage Reservation', 'shuttle-vehicle-manager'); ?></h3>
                    
                    <form id="reservation-form">
                        <input type="hidden" id="modal-dates" value="">
                        <input type="hidden" id="modal-index" value="">
                        
                        <div class="form-group">
                            <label><?php _e('Selected Date(s):', 'shuttle-vehicle-manager'); ?></label>
                            <p id="selected-dates-display"></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="reservation-note"><?php _e('Reservation Note (Optional):', 'shuttle-vehicle-manager'); ?></label>
                            <textarea id="reservation-note" rows="3" placeholder="<?php _e('e.g., Client name, booking reference, trip purpose...', 'shuttle-vehicle-manager'); ?>"></textarea>
                        </div>
                        
                        <div class="modal-buttons">
                            <button type="button" id="save-reservation" class="svm-button svm-button-reserve"><?php _e('Mark as Reserved', 'shuttle-vehicle-manager'); ?></button>
                            <button type="button" id="delete-reservation" class="svm-button svm-button-delete" style="display: none;"><?php _e('Remove Reservation', 'shuttle-vehicle-manager'); ?></button>
                            <button type="button" id="cancel-modal" class="svm-button svm-button-secondary"><?php _e('Cancel', 'shuttle-vehicle-manager'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                var reservationData = <?php echo !empty($reservation_data) ? json_encode($reservation_data) : '[]'; ?>;
                var vehicleId = <?php echo $vehicle->ID; ?>;
                var currentMonth = new Date().getMonth();
                var currentYear = new Date().getFullYear();
                
                // Date range selection variables
                var isRangeMode = false;
                var startDate = null;
                var endDate = null;
                
                // Convert reservation data to date map
                var dateMap = {};
                
                function rebuildDateMap() {
                    dateMap = {};
                    // Clean up reservation data first
                    reservationData = reservationData.filter(function(item) {
                        return item.dates && Array.isArray(item.dates) && item.dates.length > 0;
                    });
                    
                    reservationData.forEach(function(item, index) {
                        if (item.dates && Array.isArray(item.dates)) {
                            item.dates.forEach(function(date) {
                                dateMap[date] = {
                                    note: item.note || '',
                                    index: index
                                };
                            });
                        }
                    });
                }
                
                rebuildDateMap();
                
                // Toggle date range mode
                $('#enable-date-range').on('change', function() {
                    isRangeMode = $(this).is(':checked');
                    startDate = null;
                    endDate = null;
                    
                    if (isRangeMode) {
                        $('.range-selection-info').show();
                        $('.svm-calendar-instructions p').text('<?php _e("Select a date range to mark as reserved.", "shuttle-vehicle-manager"); ?>');
                    } else {
                        $('.range-selection-info').hide();
                        $('.svm-calendar-instructions p').text('<?php _e("All dates are available by default. Click on dates to mark them as reserved.", "shuttle-vehicle-manager"); ?>');
                    }
                    
                    $('.calendar-date').removeClass('selecting in-range');
                });
                
                // Render calendar
                function renderCalendar() {
                    var monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];
                    
                    var dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                    
                    var today = new Date();
                    var firstDay = new Date(currentYear, currentMonth, 1).getDay();
                    var daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                    
                    var calendarHtml = '<div class="calendar-header">';
                    calendarHtml += '<button class="cal-nav" id="prev-month">&lt;</button>';
                    calendarHtml += '<h3>' + monthNames[currentMonth] + ' ' + currentYear + '</h3>';
                    calendarHtml += '<button class="cal-nav" id="next-month">&gt;</button>';
                    calendarHtml += '</div>';
                    
                    calendarHtml += '<table class="calendar-table">';
                    calendarHtml += '<thead><tr>';
                    
                    // Add day headers
                    for (var i = 0; i < 7; i++) {
                        calendarHtml += '<th>' + dayNames[i] + '</th>';
                    }
                    
                    calendarHtml += '</tr></thead>';
                    calendarHtml += '<tbody>';
                    
                    var date = 1;
                    var rowsNeeded = Math.ceil((daysInMonth + firstDay) / 7);
                    
                    // Generate calendar rows
                    for (var row = 0; row < rowsNeeded; row++) {
                        calendarHtml += '<tr>';
                        
                        // Generate 7 cells for each row
                        for (var col = 0; col < 7; col++) {
                            if (row === 0 && col < firstDay) {
                                // Empty cells before first day of month
                                calendarHtml += '<td class="empty-cell"></td>';
                            } else if (date > daysInMonth) {
                                // Empty cells after last day of month
                                calendarHtml += '<td class="empty-cell"></td>';
                            } else {
                                // Regular date cells
                                var dateStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(date).padStart(2, '0');
                                var cellClass = 'calendar-date';
                                
                                // Check if date is reserved
                                if (dateMap[dateStr]) {
                                    cellClass += ' date-reserved';
                                }
                                
                                // Check if it's today
                                if (date === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                                    cellClass += ' date-today';
                                }
                                
                                // Check if date is in the past
                                var cellDate = new Date(currentYear, currentMonth, date);
                                if (cellDate < today && !isSameDay(cellDate, today)) {
                                    cellClass += ' date-past';
                                }
                                
                                calendarHtml += '<td>';
                                calendarHtml += '<div class="' + cellClass + '" data-date="' + dateStr + '">';
                                calendarHtml += '<div class="date-number">' + date + '</div>';
                                
                                if (dateMap[dateStr] && dateMap[dateStr].note) {
                                    calendarHtml += '<div class="date-note-indicator" title="' + escapeHtml(dateMap[dateStr].note) + '">📝</div>';
                                }
                                
                                calendarHtml += '</div>';
                                calendarHtml += '</td>';
                                date++;
                            }
                        }
                        
                        calendarHtml += '</tr>';
                    }
                    
                    calendarHtml += '</tbody></table>';
                    
                    $('#calendar-container').html(calendarHtml);
                }
                
                // Helper functions
                function isSameDay(date1, date2) {
                    return date1.getDate() === date2.getDate() &&
                        date1.getMonth() === date2.getMonth() &&
                        date1.getFullYear() === date2.getFullYear();
                }
                
                function escapeHtml(text) {
                    if (!text) return '';
                    var map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
                }
                
                function getDatesBetween(start, end) {
                    var dates = [];
                    var currentDate = new Date(start);
                    var endDate = new Date(end);
                    
                    while (currentDate <= endDate) {
                        dates.push(currentDate.toISOString().split('T')[0]);
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    
                    return dates;
                }
                
                function highlightRange(start, end) {
                    $('.calendar-date').removeClass('in-range selecting');
                    
                    var dates = getDatesBetween(start, end);
                    dates.forEach(function(date) {
                        $('.calendar-date[data-date="' + date + '"]').addClass('in-range');
                    });
                    
                        $('.calendar-date[data-date="' + end + '"]').addClass('selecting');
                    }
                    
                    // Calendar navigation
                    $(document).on('click', '#prev-month', function() {
                        currentMonth--;
                        if (currentMonth < 0) {
                            currentMonth = 11;
                            currentYear--;
                        }
                        renderCalendar();
                    });
                    
                    $(document).on('click', '#next-month', function() {
                        currentMonth++;
                        if (currentMonth > 11) {
                            currentMonth = 0;
                            currentYear++;
                        }
                        renderCalendar();
                    });
                    
                    // Click on calendar date
                    $(document).on('click', '.calendar-date:not(.date-past)', function() {
                        var dateStr = $(this).data('date');
                        var $clickedDate = $(this);
                        
                        if (isRangeMode) {
                            // Range selection mode
                            if (!startDate) {
                                startDate = dateStr;
                                $(this).addClass('selecting');
                                $('.range-selection-info').text('<?php _e("Now click on the end date", "shuttle-vehicle-manager"); ?>');
                            } else if (!endDate) {
                                endDate = dateStr;
                                
                                // Ensure start date is before end date
                                if (new Date(startDate) > new Date(endDate)) {
                                    var temp = startDate;
                                    startDate = endDate;
                                    endDate = temp;
                                }
                                
                                highlightRange(startDate, endDate);
                                
                                // Show modal for the range
                                var dateRange = getDatesBetween(startDate, endDate);
                                $('#modal-dates').val(JSON.stringify(dateRange));
                                
                                $('#selected-dates-display').html(
                                    '<strong>From:</strong> ' + startDate + '<br>' +
                                    '<strong>To:</strong> ' + endDate + '<br>' +
                                    '<strong>Total days:</strong> ' + dateRange.length
                                );
                                
                                $('#modal-title').text('<?php _e("Reserve Date Range", "shuttle-vehicle-manager"); ?>');
                                $('#reservation-note').val('');
                                $('#modal-index').val('');
                                
                                // For new range reservations, show only save button
                                $('#save-reservation').show();
                                $('#delete-reservation').hide();
                                
                                $('#reservation-modal').css('display', 'flex').hide().fadeIn();
                                
                                // Reset for next selection
                                startDate = null;
                                endDate = null;
                                $('.range-selection-info').text('<?php _e("Click on start date, then click on end date to select a range", "shuttle-vehicle-manager"); ?>');
                            }
                        } else {
                            // Single date mode
                            var isReserved = $clickedDate.hasClass('date-reserved');
                            var existingData = dateMap[dateStr];
                            
                            $('#modal-dates').val(JSON.stringify([dateStr]));
                            $('#selected-dates-display').html('<strong>Date:</strong> ' + dateStr);
                            
                            if (isReserved && existingData) {
                                // This date is already reserved
                                $('#modal-title').text('<?php _e("Edit Reservation", "shuttle-vehicle-manager"); ?>');
                                $('#reservation-note').val(existingData.note || '');
                                $('#modal-index').val(existingData.index);
                                
                                // Hide save button, show delete button
                                $('#save-reservation').hide();
                                $('#delete-reservation').show();
                            } else {
                                // This date is not reserved
                                $('#modal-title').text('<?php _e("Reserve Date", "shuttle-vehicle-manager"); ?>');
                                $('#reservation-note').val('');
                                $('#modal-index').val('');
                                
                                // Show save button, hide delete button
                                $('#save-reservation').show();
                                $('#delete-reservation').hide();
                            }
                            
                            $('#reservation-modal').css('display', 'flex').hide().fadeIn();
                        }
                    });

                    // Cancel modal - Fixed version
                    $(document).on('click', '#cancel-modal', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        $('#reservation-modal').fadeOut(function() {
                            $(this).css('display', 'none');
                        });
                        
                        $('.calendar-date').removeClass('selecting in-range');
                        startDate = null;
                        endDate = null;
                    });

                    // Close button (X) - Fixed version
                    $(document).on('click', '.svm-modal-close', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        $('#reservation-modal').fadeOut(function() {
                            $(this).css('display', 'none');
                        });
                        
                        $('.calendar-date').removeClass('selecting in-range');
                        startDate = null;
                        endDate = null;
                    });

                    // Close modal when clicking outside
                    $(document).on('click', '#reservation-modal', function(event) {
                        if (event.target.id === 'reservation-modal') {
                            $(this).fadeOut(function() {
                                $(this).css('display', 'none');
                            });
                            
                            $('.calendar-date').removeClass('selecting in-range');
                            startDate = null;
                            endDate = null;
                        }
                    });

                    // Prevent modal content clicks from closing the modal
                    $(document).on('click', '.svm-modal-content', function(e) {
                        e.stopPropagation();
                    });
                    
                    // Save reservation
                    $('#save-reservation').off('click').on('click', function() {
                        var dates = JSON.parse($('#modal-dates').val());
                        var note = $('#reservation-note').val();
                        
                        // Add new reservation entry
                        reservationData.push({
                            dates: dates,
                            note: note,
                            created: new Date().toISOString()
                        });
                        
                        // Save to database
                        saveReservationData();
                        $('#reservation-modal').fadeOut(function() {
                            $(this).css('display', 'none');
                        });
                        
                        $('.calendar-date').removeClass('selecting in-range');
                        
                        // Reset range mode if it was a range selection
                        if (isRangeMode && dates.length > 1) {
                            $('#enable-date-range').prop('checked', false);
                            isRangeMode = false;
                            $('.range-selection-info').hide();
                            $('.svm-calendar-instructions p').text('<?php _e("All dates are available by default. Click on dates to mark them as reserved.", "shuttle-vehicle-manager"); ?>');
                        }
                    });

                    // Delete reservation
                    $('#delete-reservation').off('click').on('click', function() {
                        if (confirm('<?php _e("Are you sure you want to remove this reservation?", "shuttle-vehicle-manager"); ?>')) {
                            var dates = JSON.parse($('#modal-dates').val());
                            
                            // Create a new array without the dates we're removing
                            var newReservationData = [];
                            
                            reservationData.forEach(function(entry) {
                                if (entry.dates) {
                                    // Filter out the dates we're removing
                                    var remainingDates = entry.dates.filter(function(date) {
                                        return !dates.includes(date);
                                    });
                                    
                                    // Only keep the entry if it still has dates
                                    if (remainingDates.length > 0) {
                                        newReservationData.push({
                                            dates: remainingDates,
                                            note: entry.note,
                                            created: entry.created
                                        });
                                    }
                                }
                            });
                            
                            // Update the reservation data
                            reservationData = newReservationData;
                            
                            saveReservationData();
                            $('#reservation-modal').fadeOut(function() {
                                $(this).css('display', 'none');
                            });
                        }
                    });
                    
                    // Save reservation data to database - Updated
                    function saveReservationData() {
                        // Clean the data before saving
                        var cleanedData = reservationData.filter(function(item) {
                            return item.dates && Array.isArray(item.dates) && item.dates.length > 0;
                        });
                        
                        $.ajax({
                            url: shuttle_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'shuttle_update_availability',
                                vehicle_id: vehicleId,
                                availability_data: JSON.stringify(cleanedData),
                                nonce: shuttle_ajax.nonce
                            },
                            beforeSend: function() {
                                $('.form-message').html('<p class="loading"><?php _e("Saving...", "shuttle-vehicle-manager"); ?></p>');
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.form-message').html('<p class="success">' + response.data.message + '</p>');
                                    
                                    // Update local data with cleaned data
                                    reservationData = cleanedData;
                                    
                                    // Rebuild date map and re-render calendar
                                    rebuildDateMap();
                                    renderCalendar();
                                    
                                    // Clear message after 3 seconds
                                    setTimeout(function() {
                                        $('.form-message').html('');
                                    }, 3000);
                                } else {
                                    $('.form-message').html('<p class="error">' + response.data.message + '</p>');
                                }
                            },
                            error: function() {
                                $('.form-message').html('<p class="error"><?php _e("An error occurred. Please try again.", "shuttle-vehicle-manager"); ?></p>');
                            }
                        });
                    }
                    
                    // Initial render
                    renderCalendar();
                });
                </script>
            </div>
        <?php
    }

    /**
     * Save a new vehicle or update an existing one.
     */
    public function save_vehicle() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'shuttle-vehicle-manager')));
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Check if updating existing vehicle
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $is_update = $vehicle_id > 0;
        
        if ($is_update) {
            $vehicle = get_post($vehicle_id);
            
            if (!$vehicle || $vehicle->post_type !== 'vehicle' || $vehicle->post_author != $current_user->ID) {
                wp_send_json_error(array('message' => __('Invalid vehicle or you don\'t have permission to edit this vehicle.', 'shuttle-vehicle-manager')));
                return;
            }
        }
        
        // Get form data
        $type = sanitize_text_field($_POST['vehicle_type']);
        $model = sanitize_text_field($_POST['vehicle_model']);
        $year_manufacture = intval($_POST['year_manufacture']);
        $year_registration = intval($_POST['year_registration']);
        $license_plate = sanitize_text_field($_POST['license_plate']);
        $seating_capacity = intval($_POST['seating_capacity']);
        
         // Get selected features
        $vehicle_features = isset($_POST['vehicle_features']) ? array_map('sanitize_text_field', $_POST['vehicle_features']) : array();
        
        // Create or update vehicle post
        if ($is_update) {
            $post_id = $vehicle_id;
            wp_set_object_terms($post_id, 'pending', 'vehicle_status');
        } else {
            $post_data = array(
                'post_title'  => $type . ' ' . $model . ' - ' . $license_plate,
                'post_status' => 'publish',
                'post_type'   => 'vehicle',
                'post_author' => $current_user->ID,
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                wp_send_json_error(array('message' => $post_id->get_error_message()));
                return;
            }
            
            wp_set_object_terms($post_id, 'pending', 'vehicle_status');
        }
        
        // Save vehicle details as post meta
        update_post_meta($post_id, 'vehicle_type', $type);
        update_post_meta($post_id, 'vehicle_model', $model);
        update_post_meta($post_id, 'year_manufacture', $year_manufacture);
        update_post_meta($post_id, 'year_registration', $year_registration);
        update_post_meta($post_id, 'license_plate', $license_plate);
        update_post_meta($post_id, 'seating_capacity', $seating_capacity);
        update_post_meta($post_id, 'vehicle_features', $vehicle_features);
        
        // Handle document uploads
        $documents = array(
            'rc_document',
            'insurance_document',
            'emission_document',
            'revenue_license_document',
            'fitness_document',
        );
        
        foreach ($documents as $document) {
            if (!empty($_FILES[$document]['name'])) {
                $file = $_FILES[$document];
                
                $upload_overrides = array(
                    'test_form' => false,
                );
                
                $uploaded_file = wp_handle_upload($file, $upload_overrides);
                
                if (!isset($uploaded_file['error'])) {
                    update_post_meta($post_id, $document, $uploaded_file['url']);
                }
            }
        }
        
        // Handle vehicle images
        if (!empty($_FILES['vehicle_images']['name'][0])) {
            $files = $_FILES['vehicle_images'];
            $vehicle_images = array();
            
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if (!empty($files['name'][$i])) {
                    $file = array(
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    );
                    
                    $upload_overrides = array(
                        'test_form' => false,
                    );
                    
                    $uploaded_file = wp_handle_upload($file, $upload_overrides);
                    
                    if (!isset($uploaded_file['error'])) {
                        $vehicle_images[] = $uploaded_file['url'];
                    }
                }
            }
            
            if (!empty($vehicle_images)) {
                update_post_meta($post_id, 'vehicle_images', $vehicle_images);
            }
        }
        
        // Get the redirect URL from options, with fallback
        $redirect_url = home_url(get_option('svm_redirect_path', '/my-account/'));
        
        wp_send_json_success(array(
            'message' => $is_update 
                ? __('Vehicle updated successfully! It is now pending verification.', 'shuttle-vehicle-manager')
                : __('Vehicle added successfully! It is now pending verification.', 'shuttle-vehicle-manager'),
            'redirect' => $redirect_url
        ));
    }

    /**
     * Delete a vehicle.
     */
    public function delete_vehicle() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'shuttle-vehicle-manager')));
            return;
        }
        
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $vehicle = get_post($vehicle_id);
        
        if (!$vehicle || $vehicle->post_type !== 'vehicle' || $vehicle->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Invalid vehicle or you don\'t have permission to delete this vehicle.', 'shuttle-vehicle-manager')));
            return;
        }
        
        $result = wp_delete_post($vehicle_id, true);
        
        if ($result) {
            // Get the redirect URL from options, with fallback
            $redirect_url = home_url(get_option('svm_redirect_path', '/my-account/'));
            
            wp_send_json_success(array(
                'message' => __('Vehicle deleted successfully!', 'shuttle-vehicle-manager'),
                'redirect' => $redirect_url
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete vehicle. Please try again.', 'shuttle-vehicle-manager')));
        }
    }

    /**
     * Update vehicle availability.
     */
    public function update_availability() {
        check_ajax_referer('shuttle_vehicle_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'shuttle-vehicle-manager')));
            return;
        }
        
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $availability_data = isset($_POST['availability_data']) ? $_POST['availability_data'] : '';
        
        $vehicle = get_post($vehicle_id);
        
        if (!$vehicle || $vehicle->post_type !== 'vehicle' || $vehicle->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Invalid vehicle or you don\'t have permission to update this vehicle.', 'shuttle-vehicle-manager')));
            return;
        }
        
        // Save the availability data
        update_post_meta($vehicle_id, 'availability_data', $availability_data);
        
        // Don't send redirect URL
        wp_send_json_success(array(
            'message' => __('Availability updated successfully!', 'shuttle-vehicle-manager')
        ));
    }

    /**
     * Get vehicles for a specific user.
     */
    private function get_user_vehicles($user_id) {
        $args = array(
            'post_type' => 'vehicle',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        return get_posts($args);
    }
}