<?php
/**
 * Plugin Name: Blood Donation Manager
 * Plugin URI: https://example.com/blood-donation-manager
 * Description: Complete blood donation management system with donor registration, donation requests, blood bank listings, and compatibility matching.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: blood-donation
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BDM_VERSION', '1.0.0');
define('BDM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BDM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BDM_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Blood_Donation_Manager {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        add_shortcode('bdm_donor_registration', array($this, 'donor_registration_form'));
        add_shortcode('bdm_donation_request', array($this, 'donation_request_form'));
        add_shortcode('bdm_blood_banks', array($this, 'blood_banks_list'));
        add_shortcode('bdm_donors_list', array($this, 'donors_list'));
        add_shortcode('bdm_blood_requests', array($this, 'blood_requests_list'));
        add_shortcode('bdm_dashboard', array($this, 'dashboard_shortcode'));
    }

    public function activate() {
        $this->create_tables();
        $this->create_post_types();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        $this->create_post_types();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_donors = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bdm_donors (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            blood_type VARCHAR(5) NOT NULL,
            date_of_birth DATE DEFAULT NULL,
            gender VARCHAR(10) DEFAULT NULL,
            weight DECIMAL(5,2) DEFAULT NULL,
            address TEXT,
            city VARCHAR(100) DEFAULT NULL,
            state VARCHAR(100) DEFAULT NULL,
            zip_code VARCHAR(20) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            last_donation_date DATE DEFAULT NULL,
            medical_conditions TEXT,
            is_available TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY blood_type (blood_type),
            KEY city (city),
            KEY is_available (is_available)
        ) $charset_collate;";

        $sql_requests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bdm_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            requester_name VARCHAR(200) NOT NULL,
            requester_email VARCHAR(100) NOT NULL,
            requester_phone VARCHAR(20) NOT NULL,
            patient_name VARCHAR(200) NOT NULL,
            blood_type_needed VARCHAR(5) NOT NULL,
            units_needed INT(3) NOT NULL DEFAULT 1,
            hospital_name VARCHAR(200) NOT NULL,
            hospital_address TEXT,
            city VARCHAR(100) DEFAULT NULL,
            urgency ENUM('normal', 'urgent', 'critical') DEFAULT 'normal',
            needed_by DATE DEFAULT NULL,
            additional_info TEXT,
            status ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY blood_type_needed (blood_type_needed),
            KEY status (status),
            KEY urgency (urgency)
        ) $charset_collate;";

        $sql_blood_banks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bdm_blood_banks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) DEFAULT NULL,
            zip_code VARCHAR(20) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            latitude DECIMAL(10,8) DEFAULT NULL,
            longitude DECIMAL(11,8) DEFAULT NULL,
            operating_hours TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY city (city),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_donors);
        dbDelta($sql_requests);
        dbDelta($sql_blood_banks);

        update_option('bdm_db_version', BDM_VERSION);
    }

    private function create_post_types() {
        register_post_type('bdm_blood_bank', array(
            'labels' => array(
                'name' => __('Blood Banks', 'blood-donation'),
                'singular_name' => __('Blood Bank', 'blood-donation'),
                'add_new_item' => __('Add New Blood Bank', 'blood-donation'),
                'edit_item' => __('Edit Blood Bank', 'blood-donation'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-heart',
            'rewrite' => array('slug' => 'blood-banks'),
            'show_in_rest' => true,
        ));

        register_post_type('bdm_campaign', array(
            'labels' => array(
                'name' => __('Blood Donation Campaigns', 'blood-donation'),
                'singular_name' => __('Campaign', 'blood-donation'),
                'add_new_item' => __('Add New Campaign', 'blood-donation'),
                'edit_item' => __('Edit Campaign', 'blood-donation'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-megaphone',
            'rewrite' => array('slug' => 'campaigns'),
            'show_in_rest' => true,
        ));
    }

    public function admin_menu() {
        add_menu_page(
            __('Blood Donation', 'blood-donation'),
            __('Blood Donation', 'blood-donation'),
            'manage_options',
            'bdm-dashboard',
            array($this, 'admin_dashboard_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'bdm-dashboard',
            __('Dashboard', 'blood-donation'),
            __('Dashboard', 'blood-donation'),
            'manage_options',
            'bdm-dashboard',
            array($this, 'admin_dashboard_page')
        );

        add_submenu_page(
            'bdm-dashboard',
            __('Donors', 'blood-donation'),
            __('Donors', 'blood-donation'),
            'manage_options',
            'bdm-donors',
            array($this, 'admin_donors_page')
        );

        add_submenu_page(
            'bdm-dashboard',
            __('Blood Requests', 'blood-donation'),
            __('Blood Requests', 'blood-donation'),
            'manage_options',
            'bdm-requests',
            array($this, 'admin_requests_page')
        );

        add_submenu_page(
            'bdm-dashboard',
            __('Blood Banks', 'blood-donation'),
            __('Blood Banks', 'blood-donation'),
            'manage_options',
            'bdm-blood-banks',
            array($this, 'admin_blood_banks_page')
        );

        add_submenu_page(
            'bdm-dashboard',
            __('Settings', 'blood-donation'),
            __('Settings', 'blood-donation'),
            'manage_options',
            'bdm-settings',
            array($this, 'admin_settings_page')
        );
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'bdm-') === false && $hook !== 'toplevel_page_bdm-dashboard') {
            return;
        }
        wp_enqueue_style('bdm-admin', BDM_PLUGIN_URL . 'assets/css/admin.css', array(), BDM_VERSION);
        wp_enqueue_script('bdm-admin', BDM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BDM_VERSION, true);
        wp_localize_script('bdm-admin', 'bdmAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bdm_admin_nonce'),
        ));
    }

    public function frontend_scripts() {
        wp_enqueue_style('bdm-frontend', BDM_PLUGIN_URL . 'assets/css/frontend.css', array(), BDM_VERSION);
        wp_enqueue_script('bdm-frontend', BDM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BDM_VERSION, true);
        wp_localize_script('bdm-frontend', 'bdmFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bdm_frontend_nonce'),
        ));
    }

    public function admin_dashboard_page() {
        include BDM_PLUGIN_DIR . 'admin/dashboard.php';
    }

    public function admin_donors_page() {
        include BDM_PLUGIN_DIR . 'admin/donors.php';
    }

    public function admin_requests_page() {
        include BDM_PLUGIN_DIR . 'admin/requests.php';
    }

    public function admin_blood_banks_page() {
        include BDM_PLUGIN_DIR . 'admin/blood-banks.php';
    }

    public function admin_settings_page() {
        include BDM_PLUGIN_DIR . 'admin/settings.php';
    }

    public function donor_registration_form($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/donor-registration.php';
        return ob_get_clean();
    }

    public function donation_request_form($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/donation-request.php';
        return ob_get_clean();
    }

    public function blood_banks_list($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/blood-banks-list.php';
        return ob_get_clean();
    }

    public function donors_list($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/donors-list.php';
        return ob_get_clean();
    }

    public function blood_requests_list($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/blood-requests-list.php';
        return ob_get_clean();
    }

    public function dashboard_shortcode($atts) {
        ob_start();
        include BDM_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    public static function get_blood_types() {
        return array(
            'A+' => 'A+',
            'A-' => 'A-',
            'B+' => 'B+',
            'B-' => 'B-',
            'AB+' => 'AB+',
            'AB-' => 'AB-',
            'O+' => 'O+',
            'O-' => 'O-',
        );
    }

    public static function get_compatible_blood_types($blood_type) {
        $compatibility = array(
            'A+'  => array('A+', 'A-', 'O+', 'O-'),
            'A-'  => array('A-', 'O-'),
            'B+'  => array('B+', 'B-', 'O+', 'O-'),
            'B-'  => array('B-', 'O-'),
            'AB+' => array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
            'AB-' => array('A-', 'B-', 'AB-', 'O-'),
            'O+'  => array('O+', 'O-'),
            'O-'  => array('O-'),
        );
        return isset($compatibility[$blood_type]) ? $compatibility[$blood_type] : array();
    }

    public static function get_urgency_labels() {
        return array(
            'normal'  => __('Normal', 'blood-donation'),
            'urgent'  => __('Urgent', 'blood-donation'),
            'critical' => __('Critical', 'blood-donation'),
        );
    }

    public static function get_status_labels() {
        return array(
            'pending'   => __('Pending', 'blood-donation'),
            'fulfilled' => __('Fulfilled', 'blood-donation'),
            'cancelled' => __('Cancelled', 'blood-donation'),
        );
    }
}

function blood_donation_manager() {
    return Blood_Donation_Manager::instance();
}

blood_donation_manager();

require_once BDM_PLUGIN_DIR . 'includes/ajax-handlers.php';
