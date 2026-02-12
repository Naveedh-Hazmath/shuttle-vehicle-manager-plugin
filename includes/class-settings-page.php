<?php
/**
 * Settings Page for Shuttle Vehicle Manager
 *
 * @since      1.0.0
 * @package    Shuttle_Vehicle_Manager
 */

class Shuttle_Settings_Page {

    /**
     * Initialize the settings page
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'shuttle-vehicle-owners',
            __('Settings', 'shuttle-vehicle-manager'),
            __('Settings', 'shuttle-vehicle-manager'),
            'manage_options',
            'shuttle-vehicle-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General Settings Section
        add_settings_section(
            'svm_general_settings',
            __('General Settings', 'shuttle-vehicle-manager'),
            array($this, 'render_general_section'),
            'shuttle-vehicle-settings'
        );

        // Redirect Path Setting
        add_settings_field(
            'svm_redirect_path',
            __('Redirect Path', 'shuttle-vehicle-manager'),
            array($this, 'render_redirect_path_field'),
            'shuttle-vehicle-settings',
            'svm_general_settings'
        );

        register_setting(
            'shuttle-vehicle-settings',
            'svm_redirect_path',
            array(
                'default' => '/my-account/',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }

    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general settings for Shuttle Vehicle Manager.', 'shuttle-vehicle-manager') . '</p>';
    }

    /**
     * Render redirect path field
     */
    public function render_redirect_path_field() {
        $redirect_path = get_option('svm_redirect_path', '/my-account/');
        echo '<input type="text" name="svm_redirect_path" value="' . esc_attr($redirect_path) . '" class="regular-text" />';
        echo '<p class="description">' . __('Path to redirect users after login/registration (e.g., /my-account/)', 'shuttle-vehicle-manager') . '</p>';
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('shuttle-vehicle-settings');
                do_settings_sections('shuttle-vehicle-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}