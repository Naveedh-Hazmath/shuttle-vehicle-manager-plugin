<?php
/**
 * Plugin Name: Shuttle Vehicle Manager
 * Plugin URI: https://nvd.lk/#
 * Description: A plugin to manage shuttle vehicles, owners, and availability with profile management.
 * Version: 1.4.5
 * Author: Next Vision Digital
 * Author URI: https://nvd.lk/
 * Text Domain: shuttle-vehicle-manager
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('SHUTTLE_VEHICLE_MANAGER_VERSION', '1.5.0');
define('SHUTTLE_VEHICLE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('SHUTTLE_VEHICLE_MANAGER_URL', plugin_dir_url(__FILE__));

// Include required files
require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-shuttle-vehicle-manager.php';
require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-shuttle-rest-api.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'shuttle_vehicle_manager_activate');
register_deactivation_hook(__FILE__, 'shuttle_vehicle_manager_deactivate');

/**
 * The code that runs during plugin activation.
 */
function shuttle_vehicle_manager_activate() {
    // Create vehicle_owner role if it doesn't exist
    add_role(
        'vehicle_owner',
        __('Vehicle Owner', 'shuttle-vehicle-manager'),
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
        )
    );

    // Load the vehicle post type class for activation
    require_once SHUTTLE_VEHICLE_MANAGER_PATH . 'includes/class-vehicle-post-type.php';
    
    // Register custom post types
    $vehicle_post_type = new Shuttle_Vehicle_Post_Type();
    $vehicle_post_type->register_post_type();
    $vehicle_post_type->register_taxonomies();
    
    // Flush rewrite rules AFTER registering post types
    flush_rewrite_rules();
    
    // Create status terms after taxonomies are registered
    // We need to do this after flush_rewrite_rules to ensure taxonomy exists
    wp_schedule_single_event(time() + 1, 'shuttle_vehicle_manager_create_terms');
}

/**
 * Create default terms (runs after activation)
 */
function shuttle_vehicle_manager_create_default_terms() {
    if (!term_exists('pending', 'vehicle_status')) {
        wp_insert_term('Pending', 'vehicle_status', array('slug' => 'pending'));
    }
    if (!term_exists('verified', 'vehicle_status')) {
        wp_insert_term('Verified', 'vehicle_status', array('slug' => 'verified'));
    }
}
add_action('shuttle_vehicle_manager_create_terms', 'shuttle_vehicle_manager_create_default_terms');

/**
 * The code that runs during plugin deactivation.
 */
function shuttle_vehicle_manager_deactivate() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('shuttle_vehicle_manager_create_terms');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Begins execution of the plugin.
 */
function run_shuttle_vehicle_manager() {
    $plugin = new Shuttle_Vehicle_Manager();
    $plugin->run();
}

// Initialize the plugin
add_action('plugins_loaded', 'run_shuttle_vehicle_manager');

// Initialize REST API
$rest_api = new Shuttle_Vehicle_REST_API();