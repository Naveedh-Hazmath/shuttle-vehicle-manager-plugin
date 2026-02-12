<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Vehicle_Manager {

    /**
     * Initialize the plugin.
     */
    public function run() {
        $this->load_dependencies();
        $this->maybe_run_migrations();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-user-registration.php';
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-vehicle-post-type.php';
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-admin-menu.php';
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-frontend-dashboard.php';
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-owner-profile.php';
        require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-settings-page.php';
    }

    /**
     * Check and run database migrations if needed.
     */
    private function maybe_run_migrations() {
        $current_version = get_option('svm_db_version', '1.0.0');
        
        if (version_compare($current_version, '2.0.0', '<')) {
            $this->migrate_availability_data();
            update_option('svm_db_version', '2.0.0');
        }
    }

    /**
     * Migrate availability data from old format to new format.
     */
    private function migrate_availability_data() {
        $args = array(
            'post_type' => 'vehicle',
            'posts_per_page' => -1,
            'post_status' => 'any',
        );
        
        $vehicles = get_posts($args);
        
        foreach ($vehicles as $vehicle) {
            $availability_data = get_post_meta($vehicle->ID, 'availability_data', true);
            
            if (!empty($availability_data)) {
                $data = json_decode($availability_data, true);
                
                if (is_array($data)) {
                    $new_data = array();
                    
                    // Convert only unavailable dates to reserved dates
                    foreach ($data as $entry) {
                        if (isset($entry['available']) && $entry['available'] === false) {
                            // Keep only the unavailable (now reserved) dates
                            unset($entry['available']);
                            $new_data[] = $entry;
                        }
                    }
                    
                    // Update with new data structure
                    update_post_meta($vehicle->ID, 'availability_data', json_encode($new_data));
                }
            }
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $vehicle_post_type = new Shuttle_Vehicle_Post_Type();
        add_action('init', array($vehicle_post_type, 'register_post_type'));
        add_action('init', array($vehicle_post_type, 'register_taxonomies'));
        
        $admin_menu = new Shuttle_Admin_Menu();
        add_action('admin_menu', array($admin_menu, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add columns to admin lists
        add_filter('manage_vehicle_posts_columns', array($vehicle_post_type, 'add_custom_columns'));
        add_action('manage_vehicle_posts_custom_column', array($vehicle_post_type, 'display_custom_columns'), 10, 2);
        
        // AJAX actions for admin
        add_action('wp_ajax_verify_vehicle', array($admin_menu, 'verify_vehicle'));
        add_action('wp_ajax_verify_owner', array($admin_menu, 'verify_owner'));
        add_action('wp_ajax_get_all_vehicles_availability', array($admin_menu, 'get_all_vehicles_availability'));
        add_action('wp_ajax_search_vehicles_by_availability', array($admin_menu, 'search_vehicles_by_availability'));

        $settings_page = new Shuttle_Settings_Page();
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $user_registration = new Shuttle_User_Registration();
        add_action('init', array($user_registration, 'register_scripts'));
        add_action('wp_ajax_nopriv_shuttle_register_user', array($user_registration, 'register_user'));
        add_action('wp_ajax_nopriv_shuttle_login_user', array($user_registration, 'login_user'));
        
        $owner_profile = new Shuttle_Owner_Profile();
        add_action('wp_ajax_shuttle_save_profile', array($owner_profile, 'save_profile'));
        add_action('wp_ajax_shuttle_check_profile_completion', array($owner_profile, 'check_profile_completion'));
        
        $frontend_dashboard = new Shuttle_Frontend_Dashboard();
        add_shortcode('svm_dashboard', array($frontend_dashboard, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX actions for frontend
        add_action('wp_ajax_shuttle_save_vehicle', array($frontend_dashboard, 'save_vehicle'));
        add_action('wp_ajax_shuttle_delete_vehicle', array($frontend_dashboard, 'delete_vehicle'));
        add_action('wp_ajax_shuttle_update_availability', array($frontend_dashboard, 'update_availability'));
    }

    /**
     * Register admin styles.
     */
    public function enqueue_admin_styles($hook) {
        // Enqueue the main admin styles
        wp_enqueue_style('shuttle-vehicle-manager-admin', SHUTTLE_VEHICLE_MANAGER_URL . 'assets/css/shuttle-vehicle-manager-admin.css', array(), SHUTTLE_VEHICLE_MANAGER_VERSION, 'all');
        
        // Add calendar fix for vehicle availability page
        if ($hook === 'vehicle-owners_page_shuttle-available-vehicles') {
            wp_enqueue_style(
                'shuttle-vehicle-manager-calendar-fix', 
                SHUTTLE_VEHICLE_MANAGER_URL . 'assets/css/calendar-fix.css',
                array('shuttle-vehicle-manager-admin'),
                SHUTTLE_VEHICLE_MANAGER_VERSION,
                'all'
            );
        }
    }

    /**
     * Register admin scripts.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), '4.6.9', true);
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9');
        wp_enqueue_script('shuttle-vehicle-manager-admin', SHUTTLE_VEHICLE_MANAGER_URL . 'assets/js/admin-scripts.js', array('jquery', 'flatpickr'), SHUTTLE_VEHICLE_MANAGER_VERSION, true);
        
        wp_localize_script('shuttle-vehicle-manager-admin', 'shuttle_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shuttle_admin_nonce'),
        ));
    }

    /**
     * Register frontend styles.
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style('shuttle-vehicle-manager-frontend', SHUTTLE_VEHICLE_MANAGER_URL . 'assets/css/shuttle-vehicle-manager.css', array(), SHUTTLE_VEHICLE_MANAGER_VERSION, 'all');
        // Flatpickr - ensure correct version and multiple selection support
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js', array('jquery'), '4.6.13', true);
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css', array(), '4.6.13');
    }

    /**
     * Register frontend scripts.
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), '4.6.9', true);
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9');
        wp_enqueue_script('shuttle-vehicle-manager-frontend', SHUTTLE_VEHICLE_MANAGER_URL . 'assets/js/frontend-dashboard.js', array('jquery', 'flatpickr'), SHUTTLE_VEHICLE_MANAGER_VERSION, true);
        
        // Get redirect path from settings or use default
        $redirect_path = get_option('svm_redirect_path', '/my-account/');
        
        wp_localize_script('shuttle-vehicle-manager-frontend', 'shuttle_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shuttle_vehicle_nonce'),
            'redirect_url' => home_url($redirect_path),
        ));
    }
}