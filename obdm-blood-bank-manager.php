<?php
/**
 * Plugin Name: Obydullah Blood Donation Manager
 * Plugin URI: https://obydullah.com/project/blood-donation-management-system-a-complete-wordpress-solution
 * Description: Complete blood donation management system with donor registration, donation requests, blood bank listings, and compatibility matching.
 * Version: 1.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * Text Domain: obydullah-blood-bank-manager
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OBDM_VERSION', '1.0.0');
define('OBDM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OBDM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OBDM_PLUGIN_BASENAME', plugin_basename(__FILE__));

class Obdm_Blood_Bank_Manager {
    /**
     * Object cache group used for every read this plugin caches.
     */
    public const CACHE_GROUP = 'obdm';

    /**
     * Option holding the current cache generation.
     *
     * Every cached read key embeds this value, so bumping it invalidates all
     * cached reads at once. That is cheaper and far less error prone than
     * tracking individual keys, which cannot be enumerated reliably.
     */
    private const CACHE_VERSION_OPTION = 'obdm_cache_version';

    /**
     * How long a cached read is kept before it is treated as stale.
     *
     * The generation counter is what actually guarantees freshness; this only
     * bounds memory use for rows that are never written again.
     */
    private const CACHE_TTL = HOUR_IN_SECONDS;

    private static ?self $instance = null;

    public $donors_table;
    public $requests_table;
    public $blood_banks_table;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function __construct() {
        global $wpdb;

        $this->donors_table      = $wpdb->prefix . 'obdm_donors';
        $this->requests_table    = $wpdb->prefix . 'obdm_requests';
        $this->blood_banks_table = $wpdb->prefix . 'obdm_blood_banks';

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'handle_admin_actions']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_scripts']);
        add_action('add_meta_boxes', [$this, 'register_campaign_meta_boxes']);
        add_action('save_post_obdm_campaign', [$this, 'save_campaign_meta']);
        add_action('restrict_manage_posts', [$this, 'campaign_list_filters']);
        add_action('pre_get_posts', [$this, 'filter_campaign_query']);
        add_action('admin_notices', [$this, 'campaign_filter_notice']);

        add_shortcode('obdm_donor_registration', [$this, 'donor_registration_form']);
        add_shortcode('obdm_donation_request', [$this, 'donation_request_form']);
        add_shortcode('obdm_blood_banks', [$this, 'blood_banks_list']);
        add_shortcode('obdm_donors_list', [$this, 'donors_list']);
        add_shortcode('obdm_blood_requests', [$this, 'blood_requests_list']);
        add_shortcode('obdm_dashboard', [$this, 'dashboard_shortcode']);
    }

    public function activate() {
        $this->migrate_legacy_names();
        $this->create_tables();
        $this->create_post_types();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Loads the plugin translations from the languages directory.
     * Unnecessary since WordPress 6.7 for wordpress.org hosted plugins,
     * but required for bundled .mo files on any other host.
     */
    public function load_textdomain() {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Required for the bundled .mo files; this plugin is not hosted on wordpress.org, so nothing loads its translations automatically.
        load_plugin_textdomain(
            'obydullah-blood-bank-manager',
            false,
            dirname(OBDM_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Handles every admin form submission and row action.
     * Runs on admin_init so redirects happen before any markup is sent.
     */
    public function handle_admin_actions() {
        global $wpdb;

        if (empty($_POST) && empty($_GET['action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        $id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $bail   = function ($page, $args = []) {
            $args = array_merge(['page' => $page], $args);
            wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
            exit;
        };

        if (isset($_POST['obdm_donor_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['obdm_donor_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'obdm_donor_action')) {
                $bail('obdm-donors', ['message' => rawurlencode(__('Security verification failed', 'obydullah-blood-bank-manager'))]);
            }

            if ('delete' === $action && $id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->delete($this->donors_table, ['id' => $id], ['%d']);
                self::flush_query_cache();
                $bail('obdm-donors', ['message' => rawurlencode(
                    $result
                        ? __('Donor deleted.', 'obydullah-blood-bank-manager')
                        : __('Donor could not be deleted.', 'obydullah-blood-bank-manager')
                )]);
            }

            $first_name         = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
            $last_name          = sanitize_text_field(wp_unslash($_POST['last_name'] ?? ''));
            $email              = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $phone              = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
            $blood_type         = sanitize_text_field(wp_unslash($_POST['blood_type'] ?? ''));
            $date_of_birth      = sanitize_text_field(wp_unslash($_POST['date_of_birth'] ?? ''));
            $gender             = sanitize_text_field(wp_unslash($_POST['gender'] ?? ''));
            $weight             = floatval(wp_unslash($_POST['weight'] ?? ''));
            $address            = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
            $city               = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
            $state              = sanitize_text_field(wp_unslash($_POST['state'] ?? ''));
            $zip_code           = sanitize_text_field(wp_unslash($_POST['zip_code'] ?? ''));
            $country            = sanitize_text_field(wp_unslash($_POST['country'] ?? ''));
            $last_donation_date = sanitize_text_field(wp_unslash($_POST['last_donation_date'] ?? ''));
            $medical_conditions = sanitize_textarea_field(wp_unslash($_POST['medical_conditions'] ?? ''));
            $is_available       = isset($_POST['is_available']) ? 1 : 0;

            $data = [
                'first_name'         => $first_name,
                'last_name'          => $last_name,
                'email'              => $email,
                'phone'              => $phone,
                'blood_type'         => $blood_type,
                'date_of_birth'      => $date_of_birth,
                'gender'             => $gender,
                'weight'             => $weight,
                'address'            => $address,
                'city'               => $city,
                'state'              => $state,
                'zip_code'           => $zip_code,
                'country'            => $country,
                'last_donation_date' => $last_donation_date,
                'medical_conditions' => $medical_conditions,
                'is_available'       => $is_available,
            ];

            $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'];

            if ($id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->update($this->donors_table, $data, ['id' => $id], $formats, ['%d']);
                self::flush_query_cache();
                $message = $result
                    ? __('Donor updated successfully.', 'obydullah-blood-bank-manager')
                    : __('Donor could not be updated.', 'obydullah-blood-bank-manager');
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->insert($this->donors_table, $data, $formats);
                self::flush_query_cache();
                $message = $result
                    ? __('Donor added successfully.', 'obydullah-blood-bank-manager')
                    : __('Donor could not be added.', 'obydullah-blood-bank-manager');
            }

            $bail('obdm-donors', ['message' => rawurlencode($message)]);
        }

        if (isset($_POST['obdm_request_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['obdm_request_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'obdm_request_action')) {
                $bail('obdm-requests', ['message' => rawurlencode(__('Security verification failed', 'obydullah-blood-bank-manager'))]);
            }

            if ('delete' === $action && $id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->delete($this->requests_table, ['id' => $id], ['%d']);
                self::flush_query_cache();
                $bail('obdm-requests', ['message' => rawurlencode(
                    $result
                        ? __('Request deleted.', 'obydullah-blood-bank-manager')
                        : __('Request could not be deleted.', 'obydullah-blood-bank-manager')
                )]);
            }

            $requester_name    = sanitize_text_field(wp_unslash($_POST['requester_name'] ?? ''));
            $requester_email   = sanitize_email(wp_unslash($_POST['requester_email'] ?? ''));
            $requester_phone   = sanitize_text_field(wp_unslash($_POST['requester_phone'] ?? ''));
            $patient_name      = sanitize_text_field(wp_unslash($_POST['patient_name'] ?? ''));
            $blood_type_needed = sanitize_text_field(wp_unslash($_POST['blood_type_needed'] ?? ''));
            $units_needed      = intval(wp_unslash($_POST['units_needed'] ?? ''));
            $hospital_name     = sanitize_text_field(wp_unslash($_POST['hospital_name'] ?? ''));
            $hospital_address  = sanitize_textarea_field(wp_unslash($_POST['hospital_address'] ?? ''));
            $city              = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
            $urgency           = sanitize_text_field(wp_unslash($_POST['urgency'] ?? ''));
            $needed_by         = sanitize_text_field(wp_unslash($_POST['needed_by'] ?? ''));
            $additional_info   = sanitize_textarea_field(wp_unslash($_POST['additional_info'] ?? ''));
            $status            = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));

            $data = [
                'requester_name'    => $requester_name,
                'requester_email'   => $requester_email,
                'requester_phone'   => $requester_phone,
                'patient_name'      => $patient_name,
                'blood_type_needed' => $blood_type_needed,
                'units_needed'      => $units_needed,
                'hospital_name'     => $hospital_name,
                'hospital_address'  => $hospital_address,
                'city'              => $city,
                'urgency'           => $urgency,
                'needed_by'         => $needed_by,
                'additional_info'   => $additional_info,
                'status'            => $status,
            ];

            $formats = ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

            if ($id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result  = $wpdb->update($this->requests_table, $data, ['id' => $id], $formats, ['%d']);
                self::flush_query_cache();
                $message = $result
                    ? __('Request updated successfully.', 'obydullah-blood-bank-manager')
                    : __('Request could not be updated.', 'obydullah-blood-bank-manager');
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result  = $wpdb->insert($this->requests_table, $data, $formats);
                self::flush_query_cache();
                $message = $result
                    ? __('Request created successfully.', 'obydullah-blood-bank-manager')
                    : __('Request could not be created.', 'obydullah-blood-bank-manager');

                if ($result) {
                    obdm_send_request_notification($data);
                }
            }

            $bail('obdm-requests', ['message' => rawurlencode($message)]);
        }

        if (isset($_POST['obdm_bank_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['obdm_bank_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'obdm_bank_action')) {
                $bail('obdm-blood-banks', ['message' => rawurlencode(__('Security verification failed', 'obydullah-blood-bank-manager'))]);
            }

            if ('delete' === $action && $id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->delete($this->blood_banks_table, ['id' => $id], ['%d']);
                self::flush_query_cache();
                $bail('obdm-blood-banks', ['message' => rawurlencode(
                    $result
                        ? __('Blood bank deleted.', 'obydullah-blood-bank-manager')
                        : __('Blood bank could not be deleted.', 'obydullah-blood-bank-manager')
                )]);
            }

            $name            = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
            $description     = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
            $address         = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
            $city            = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
            $state           = sanitize_text_field(wp_unslash($_POST['state'] ?? ''));
            $zip_code        = sanitize_text_field(wp_unslash($_POST['zip_code'] ?? ''));
            $country         = sanitize_text_field(wp_unslash($_POST['country'] ?? ''));
            $phone           = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
            $email           = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $website         = esc_url_raw(wp_unslash($_POST['website'] ?? ''));
            $latitude        = floatval(wp_unslash($_POST['latitude'] ?? ''));
            $longitude       = floatval(wp_unslash($_POST['longitude'] ?? ''));
            $operating_hours = sanitize_textarea_field(wp_unslash($_POST['operating_hours'] ?? ''));
            $is_active       = isset($_POST['is_active']) ? 1 : 0;

            $data = [
                'name'            => $name,
                'description'     => $description,
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'zip_code'        => $zip_code,
                'country'         => $country,
                'phone'           => $phone,
                'email'           => $email,
                'website'         => $website,
                'latitude'        => $latitude,
                'longitude'       => $longitude,
                'operating_hours' => $operating_hours,
                'is_active'       => $is_active,
            ];

            $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d'];

            if ($id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->update($this->blood_banks_table, $data, ['id' => $id], $formats, ['%d']);
                self::flush_query_cache();
                $message = $result
                    ? __('Blood bank updated successfully.', 'obydullah-blood-bank-manager')
                    : __('Blood bank could not be updated.', 'obydullah-blood-bank-manager');
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->insert($this->blood_banks_table, $data, $formats);
                self::flush_query_cache();
                $message = $result
                    ? __('Blood bank added successfully.', 'obydullah-blood-bank-manager')
                    : __('Blood bank could not be added.', 'obydullah-blood-bank-manager');
            }

            $bail('obdm-blood-banks', ['message' => rawurlencode($message)]);
        }

        if (isset($_POST['obdm_settings_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['obdm_settings_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'obdm_settings_action')) {
                $bail('obdm-settings', ['message' => rawurlencode(__('Security verification failed', 'obydullah-blood-bank-manager'))]);
            }

            $notify_email         = sanitize_email(wp_unslash($_POST['notify_email'] ?? ''));
            $min_weight           = floatval(wp_unslash($_POST['min_weight'] ?? ''));
            $min_age              = intval(wp_unslash($_POST['min_age'] ?? ''));
            $max_days_between     = intval(wp_unslash($_POST['max_days_between'] ?? ''));
            $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
            $custom_message       = sanitize_textarea_field(wp_unslash($_POST['custom_message'] ?? ''));
            $donor_email_subject  = sanitize_text_field(wp_unslash($_POST['donor_email_subject'] ?? ''));
            $request_email_subject = sanitize_text_field(wp_unslash($_POST['request_email_subject'] ?? ''));

            update_option('obdm_settings', [
                'notify_email'         => $notify_email,
                'min_weight'           => $min_weight,
                'min_age'              => $min_age,
                'max_days_between'     => $max_days_between,
                'enable_notifications' => $enable_notifications,
                'custom_message'       => $custom_message,
                'donor_email_subject'  => $donor_email_subject,
                'request_email_subject' => $request_email_subject,
            ]);

            $bail('obdm-settings', ['message' => rawurlencode(__('Settings saved.', 'obydullah-blood-bank-manager'))]);
        }

        if ('delete' === $action && $id) {
            $map = [
                'obdm-donors'      => ['obdm-donors', $this->donors_table, 'obdm_delete_donor_', __('Donor deleted.', 'obydullah-blood-bank-manager'), __('Donor could not be deleted.', 'obydullah-blood-bank-manager')],
                'obdm-requests'    => ['obdm-requests', $this->requests_table, 'obdm_delete_request_', __('Request deleted.', 'obydullah-blood-bank-manager'), __('Request could not be deleted.', 'obydullah-blood-bank-manager')],
                'obdm-blood-banks' => ['obdm-blood-banks', $this->blood_banks_table, 'obdm_delete_blood_bank_', __('Blood bank deleted.', 'obydullah-blood-bank-manager'), __('Blood bank could not be deleted.', 'obydullah-blood-bank-manager')],
            ];

            $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

            if (isset($map[$page])) {
                list($redirect_page, $table, $nonce_action, $message, $failed) = $map[$page];
                $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';

                if (!wp_verify_nonce($nonce, $nonce_action . $id)) {
                    $bail($redirect_page, ['message' => rawurlencode(__('Security verification failed', 'obydullah-blood-bank-manager'))]);
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Write to a plugin table; the flush_query_cache() call below bumps the cache generation and flushes the object cache group.
                $result = $wpdb->delete($table, ['id' => $id], ['%d']);
                self::flush_query_cache();
                $bail($redirect_page, ['message' => rawurlencode($result ? $message : $failed)]);
            }
        }
    }

    public function init() {
        $this->create_post_types();
    }

    /**
     * Migrates legacy "bdm_" storage names to "obdm_" so existing data
     * survives the BDM -> OBDM rename. Runs once on activation.
     */
    /**
     * Renames the pre-rename bdm_* tables and rewrites bdm_* references in
     * post content, post meta and the db version option.
     *
     * This runs once, guarded by the obdm_legacy_migrated option. Its statements
     * are DDL, one-off bulk rewrites and schema introspection: there is nothing
     * reusable to cache and nothing reads the result twice, so the caching
     * sniffs do not apply to this routine.
     */
    private function migrate_legacy_names() {
        global $wpdb;

        /*
         * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
         * One-shot activation migration: DDL, bulk rewrites and SHOW TABLES.
         * Caching would be meaningless for statements that run exactly once.
         */
        if (get_option('obdm_legacy_migrated')) {
            return;
        }

        $renamed_tables = [
            'bdm_donors'      => 'obdm_donors',
            'bdm_requests'    => 'obdm_requests',
            'bdm_blood_banks' => 'obdm_blood_banks',
        ];

        foreach ($renamed_tables as $old => $new) {
            // Table identifiers cannot be bound as query params, so both names
            // are rebuilt from the hard-coded list and the site prefix only.
            $old_full = $wpdb->prefix . preg_replace('/[^a-z0-9_]/', '', $old);
            $new_full = $wpdb->prefix . preg_replace('/[^a-z0-9_]/', '', $new);

            $old_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $old_full));
            $new_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $new_full));

            if ($old_exists && !$new_exists) {
                $wpdb->query(
                    $wpdb->prepare(
                        'RENAME TABLE `%s` TO `%s`', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- identifiers are whitelisted above.
                        $old_full,
                        $new_full
                    )
                );
            }
        }

        $wpdb->query(
            "UPDATE {$wpdb->posts}
             SET post_type = 'obdm_campaign'
             WHERE post_type = 'bdm_campaign'"
        );
        $wpdb->query(
            "UPDATE {$wpdb->posts}
             SET post_type = 'obdm_blood_bank'
             WHERE post_type = 'bdm_blood_bank'"
        );

        $wpdb->query(
            "UPDATE {$wpdb->postmeta}
             SET meta_key = CONCAT('_obdm_', SUBSTRING(meta_key, 6))
             WHERE meta_key LIKE '\\_bdm\\_%'"
        );

        $wpdb->query(
            "UPDATE {$wpdb->posts}
             SET post_content = REPLACE(post_content, '[bdm_', '[obdm_')
             WHERE post_content LIKE '%[bdm_%'"
        );

        $wpdb->query(
            "UPDATE {$wpdb->options}
             SET option_name = 'obdm_db_version'
             WHERE option_name = 'bdm_db_version'"
        );

        update_option('obdm_legacy_migrated', 1);
        /* phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */

        // Cached reads were keyed against the pre-migration schema.
        self::flush_query_cache();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_donors = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}obdm_donors (
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

        $sql_requests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}obdm_requests (
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

        $sql_blood_banks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}obdm_blood_banks (
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

        update_option('obdm_db_version', OBDM_VERSION);
    }

    private function create_post_types() {
        register_post_type('obdm_blood_bank', [
            'labels' => [
                'name' => __('Blood Banks', 'obydullah-blood-bank-manager'),
                'singular_name' => __('Blood Bank', 'obydullah-blood-bank-manager'),
                'add_new_item' => __('Add New Blood Bank', 'obydullah-blood-bank-manager'),
                'edit_item' => __('Edit Blood Bank', 'obydullah-blood-bank-manager'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'blood-banks'],
            'show_in_rest' => true,
        ]);

        register_post_type('obdm_campaign', [
            'labels' => [
                'name' => __('Blood Donation Campaigns', 'obydullah-blood-bank-manager'),
                'singular_name' => __('Campaign', 'obydullah-blood-bank-manager'),
                'add_new_item' => __('Add New Campaign', 'obydullah-blood-bank-manager'),
                'edit_item' => __('Edit Campaign', 'obydullah-blood-bank-manager'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'campaigns'],
            'show_in_rest' => true,
        ]);
    }

    public function admin_menu() {
        add_menu_page(
            __('Blood Donation Management', 'obydullah-blood-bank-manager'),
            __('Blood Donation Management', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-dashboard',
            [$this, 'admin_dashboard_page'],
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Dashboard', 'obydullah-blood-bank-manager'),
            __('Dashboard', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-dashboard',
            [$this, 'admin_dashboard_page']
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Donors', 'obydullah-blood-bank-manager'),
            __('Donors', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-donors',
            [$this, 'admin_donors_page']
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Blood Requests', 'obydullah-blood-bank-manager'),
            __('Blood Requests', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-requests',
            [$this, 'admin_requests_page']
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Blood Banks', 'obydullah-blood-bank-manager'),
            __('Blood Banks', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-blood-banks',
            [$this, 'admin_blood_banks_page']
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Campaigns', 'obydullah-blood-bank-manager'),
            __('Campaigns', 'obydullah-blood-bank-manager'),
            'manage_options',
            'edit.php?post_type=obdm_campaign'
        );

        add_submenu_page(
            'obdm-dashboard',
            __('Settings', 'obydullah-blood-bank-manager'),
            __('Settings', 'obydullah-blood-bank-manager'),
            'manage_options',
            'obdm-settings',
            [$this, 'admin_settings_page']
        );
    }

    public function admin_scripts($hook) {
        $screen = get_current_screen();
        $is_obdm_post_type = $screen && in_array($screen->post_type, ['obdm_campaign', 'obdm_blood_bank'], true);

        if (!$is_obdm_post_type && !str_contains($hook, 'obdm-') && !str_contains($hook, 'obdm_')) {
            return;
        }
        wp_enqueue_style('obdm-admin', OBDM_PLUGIN_URL . 'assets/css/obdm-admin.css', ['dashicons'], self::asset_version('assets/css/obdm-admin.css'));
        wp_enqueue_script('obdm-admin', OBDM_PLUGIN_URL . 'assets/js/obdm-admin.js', ['jquery'], self::asset_version('assets/js/obdm-admin.js'), true);
        wp_localize_script('obdm-admin', 'obdmAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('obdm_admin_nonce'),
        ]);
    }

    public function frontend_scripts() {
        wp_enqueue_style('obdm-frontend', OBDM_PLUGIN_URL . 'assets/css/obdm-frontend.css', ['dashicons'], self::asset_version('assets/css/obdm-frontend.css'));
        wp_enqueue_script('obdm-frontend', OBDM_PLUGIN_URL . 'assets/js/obdm-frontend.js', ['jquery'], self::asset_version('assets/js/obdm-frontend.js'), true);
        wp_localize_script('obdm-frontend', 'obdmFrontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('obdm_frontend_nonce'),
        ]);
    }

    /* ------------------------------------------------------------
       Campaign meta and list filters
       ------------------------------------------------------------ */

    private function campaign_cities() {
        global $wpdb;

        $cities = wp_cache_get('obdm_campaign_cities', self::CACHE_GROUP);

        if (!is_array($cities)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Result is cached above and invalidated when campaign meta is saved.
            $rows = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = %s AND pm.meta_value <> '' AND p.post_type = 'obdm_campaign'
                 ORDER BY pm.meta_value ASC",
                '_obdm_campaign_city'
            ));

            $cities = array_values(array_filter(array_map('sanitize_text_field', (array) $rows)));
            wp_cache_set('obdm_campaign_cities', $cities, self::CACHE_GROUP, self::CACHE_TTL);
        }

        return $cities;
    }

    private function campaign_filter_values() {
        $blood_types = self::get_blood_types();
        $blood_type = self::read_request_param('obdm_blood_type');

        if ($blood_type !== '' && !in_array($blood_type, $blood_types, true)) {
            $blood_type = '';
        }

        return [
            'blood_type' => $blood_type,
            'city'       => self::read_request_param('obdm_city'),
        ];
    }

    public function register_campaign_meta_boxes() {
        add_meta_box(
            'obdm-campaign-details',
            __('Campaign details', 'obydullah-blood-bank-manager'),
            [$this, 'render_campaign_meta_box'],
            'obdm_campaign',
            'normal',
            'high'
        );
    }

    public function render_campaign_meta_box($post) {
        wp_nonce_field('obdm_campaign_meta', 'obdm_campaign_meta_nonce');

        $blood_type = get_post_meta($post->ID, '_obdm_campaign_blood_type', true);
        $city = get_post_meta($post->ID, '_obdm_campaign_city', true);
        $goal = get_post_meta($post->ID, '_obdm_campaign_goal', true);
        $blood_types = self::get_blood_types();
        $cities = $this->campaign_cities();
        ?>
<div class="obdm-form obdm-form__card obdm-form__card--flush">
    <div class="obdm-form__grid">
        <p class="obdm-field">
            <label
                for="obdm_campaign_blood_type"><?php esc_html_e('Blood type', 'obydullah-blood-bank-manager'); ?></label>
            <select name="obdm_campaign_blood_type" id="obdm_campaign_blood_type">
                <option value=""><?php esc_html_e('All blood types', 'obydullah-blood-bank-manager'); ?></option>
                <?php foreach ($blood_types as $type): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($blood_type, $type); ?>>
                    <?php echo esc_html($type); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="obdm-field">
            <label for="obdm_campaign_city"><?php esc_html_e('City', 'obydullah-blood-bank-manager'); ?></label>
            <input type="text" name="obdm_campaign_city" id="obdm_campaign_city" value="<?php echo esc_attr($city); ?>"
                list="obdm-campaign-city-options"
                placeholder="<?php esc_attr_e('Target city or area', 'obydullah-blood-bank-manager'); ?>">
            <datalist id="obdm-campaign-city-options">
                <?php foreach ($cities as $known_city): ?>
                <option value="<?php echo esc_attr($known_city); ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </p>
        <p class="obdm-field">
            <label for="obdm_campaign_goal"><?php esc_html_e('Target units', 'obydullah-blood-bank-manager'); ?></label>
            <input type="number" min="0" step="1" name="obdm_campaign_goal" id="obdm_campaign_goal"
                value="<?php echo esc_attr($goal); ?>">
            <span
                class="obdm-field__hint"><?php esc_html_e('How many donations you are aiming to collect.', 'obydullah-blood-bank-manager'); ?></span>
        </p>
    </div>
</div>
<?php
    }

    public function save_campaign_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (!isset($_POST['obdm_campaign_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['obdm_campaign_meta_nonce'])), 'obdm_campaign_meta')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $blood_type = isset($_POST['obdm_campaign_blood_type']) ? sanitize_text_field(wp_unslash($_POST['obdm_campaign_blood_type'])) : '';
        if ($blood_type !== '' && !in_array($blood_type, self::get_blood_types(), true)) {
            $blood_type = '';
        }
        $this->update_campaign_meta($post_id, '_obdm_campaign_blood_type', $blood_type);

        $city = isset($_POST['obdm_campaign_city']) ? sanitize_text_field(wp_unslash($_POST['obdm_campaign_city'])) : '';
        $this->update_campaign_meta($post_id, '_obdm_campaign_city', $city);

        $goal = isset($_POST['obdm_campaign_goal']) ? absint($_POST['obdm_campaign_goal']) : 0;
        $this->update_campaign_meta($post_id, '_obdm_campaign_goal', $goal ? $goal : '');

        wp_cache_delete('obdm_campaign_cities', self::CACHE_GROUP);
    }

    private function update_campaign_meta($post_id, $key, $value) {
        if ('' === $value || null === $value) {
            delete_post_meta($post_id, $key);
            return;
        }

        update_post_meta($post_id, $key, $value);
    }

    public function campaign_list_filters($post_type) {
        if ('obdm_campaign' !== $post_type) {
            return;
        }

        $filters = $this->campaign_filter_values();
        $blood_types = self::get_blood_types();
        $cities = $this->campaign_cities();
        ?>
<select name="obdm_blood_type"
    aria-label="<?php esc_attr_e('Filter campaigns by blood type', 'obydullah-blood-bank-manager'); ?>">
    <option value=""><?php esc_html_e('All blood types', 'obydullah-blood-bank-manager'); ?></option>
    <?php foreach ($blood_types as $type): ?>
    <option value="<?php echo esc_attr($type); ?>" <?php selected($filters['blood_type'], $type); ?>>
        <?php echo esc_html($type); ?></option>
    <?php endforeach; ?>
</select>
<select name="obdm_city" aria-label="<?php esc_attr_e('Filter campaigns by city', 'obydullah-blood-bank-manager'); ?>">
    <option value=""><?php esc_html_e('All cities', 'obydullah-blood-bank-manager'); ?></option>
    <?php foreach ($cities as $known_city): ?>
    <option value="<?php echo esc_attr($known_city); ?>" <?php selected($filters['city'], $known_city); ?>>
        <?php echo esc_html($known_city); ?></option>
    <?php endforeach; ?>
</select>
<?php
        // No submit button here: WP_Posts_List_Table::extra_tablenav() already
        // appends its own "Filter" button whenever this hook outputs markup.
        // Adding one here would render two buttons sharing name="filter_action".
    }

    public function filter_campaign_query($query) {
        if (is_admin() && !$query->is_main_query()) {
            return;
        }
        if (!in_array('obdm_campaign', (array) $query->get('post_type'), true)) {
            return;
        }

        $filters = $this->campaign_filter_values();
        $meta_query = (array) $query->get('meta_query');

        if ('' !== $filters['blood_type']) {
            $meta_query[] = [
                'key'     => '_obdm_campaign_blood_type',
                'value'   => $filters['blood_type'],
                'compare' => '=',
            ];
        }

        if ('' !== $filters['city']) {
            $meta_query[] = [
                'key'     => '_obdm_campaign_city',
                'value'   => $filters['city'],
                'compare' => '=',
            ];
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    public function campaign_filter_notice() {
        if ('obdm_campaign' !== self::read_request_param('post_type')) {
            return;
        }

        $filters = $this->campaign_filter_values();
        $labels = [];

        if ('' !== $filters['blood_type']) {
            $labels[] = sprintf(
                /* translators: %s: blood type. */
                __('blood type %s', 'obydullah-blood-bank-manager'),
                $filters['blood_type']
            );
        }

        if ('' !== $filters['city']) {
            $labels[] = sprintf(
                /* translators: %s: city name. */
                __('city %s', 'obydullah-blood-bank-manager'),
                $filters['city']
            );
        }

        if (empty($labels)) {
            return;
        }

        $reset = wp_nonce_url(
            admin_url('edit.php?post_type=obdm_campaign'),
            'bulk-posts'
        );
        ?>
<div class="notice notice-info is-dismissible">
    <p>
        <?php
                echo esc_html(sprintf(
                    /* translators: %s: list of active filters. */
                    __('Filtered by %s.', 'obydullah-blood-bank-manager'),
                    implode(', ', $labels)
                ));
                ?>
        <a
            href="<?php echo esc_url($reset); ?>"><?php esc_html_e('Reset filters', 'obydullah-blood-bank-manager'); ?></a>
    </p>
</div>
<?php
    }

    public function admin_dashboard_page() {
        include OBDM_PLUGIN_DIR . 'admin/obdm-dashboard.php';
    }

    public function admin_donors_page() {
        include OBDM_PLUGIN_DIR . 'admin/obdm-donors.php';
    }

    public function admin_requests_page() {
        include OBDM_PLUGIN_DIR . 'admin/obdm-requests.php';
    }

    public function admin_blood_banks_page() {
        include OBDM_PLUGIN_DIR . 'admin/obdm-blood-banks.php';
    }

    public function admin_settings_page() {
        include OBDM_PLUGIN_DIR . 'admin/obdm-settings.php';
    }

    public function donor_registration_form($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-donor-registration.php';
        return ob_get_clean();
    }

    public function donation_request_form($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-donation-request.php';
        return ob_get_clean();
    }

    public function blood_banks_list($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-blood-banks-list.php';
        return ob_get_clean();
    }

    public function donors_list($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-donors-list.php';
        return ob_get_clean();
    }

    public function blood_requests_list($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-blood-requests-list.php';
        return ob_get_clean();
    }

    public function dashboard_shortcode($atts) {
        ob_start();
        include OBDM_PLUGIN_DIR . 'templates/obdm-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Table names for use outside the class (AJAX handlers, admin pages,
     * templates). Inside the class use $this->donors_table and friends.
     */
    public static function donors_table() {
        global $wpdb;
        return $wpdb->prefix . 'obdm_donors';
    }

    public static function requests_table() {
        global $wpdb;
        return $wpdb->prefix . 'obdm_requests';
    }

    public static function blood_banks_table() {
        global $wpdb;
        return $wpdb->prefix . 'obdm_blood_banks';
    }

    /* ------------------------------------------------------------
       Shared list query layer
       ------------------------------------------------------------ */

    /**
     * Rows shown per page on the admin list screens.
     */
    const ADMIN_PER_PAGE = 20;

    /**
     * Reads one read-only parameter from the current request.
     *
     * Admin list filters, record ids and the public shortcode filters all
     * arrive as plain GET parameters, exactly like WordPress' own list tables
     * do. None of these reads change state: the admin screens have already
     * passed a capability check by the time they render, the frontend lists
     * are public, and every value is sanitised here and then bound through
     * $wpdb->prepare() before it is allowed anywhere near a query. Destructive
     * actions (add, edit, delete) are all protected by their own nonces in
     * handle_admin_actions() instead.
     *
     * @param string $key Request key to read.
     * @return string Sanitised value, or an empty string when it is absent.
     */
    private static function read_request_param( $key ) {
        /*
         * phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
         * Read-only request parameters, see the docblock above: the value is
         * sanitised on the next line and is never echoed or used as markup.
         */
        $value = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
        /* phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */

        return is_string( $value ) ? $value : '';
    }

    /**
     * Returns the page number requested by a paginated list screen.
     *
     * @return int Page number, never below 1.
     */
    private static function list_page_number() {
        return max( 1, (int) self::read_request_param( 'paged' ) );
    }

    /**
     * Returns the admin sub-action requested by the current record screen URL.
     *
     * @return string One of 'add', 'edit', 'delete' or 'list'.
     */
    public static function admin_record_action() {
        $action = self::read_request_param( 'action' );

        return in_array( $action, [ 'add', 'edit', 'delete' ], true ) ? $action : 'list';
    }

    /**
     * Returns the record id requested by the current admin record screen URL.
     *
     * @return int Record id, or 0 when the request does not name a record.
     */
    public static function admin_record_id() {
        $id = self::read_request_param( 'id' );

        return ctype_digit( $id ) ? (int) $id : 0;
    }

    /**
     * Returns the one-off notice message left behind by an admin redirect.
     *
     * The message is set by this plugin's own redirect helper once a
     * nonce-protected action has been verified, and is escaped on output.
     *
     * @return string Notice text, or an empty string when there is none.
     */
    public static function admin_notice_message() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice written by a verified admin action.
        $message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

        return is_string( $message ) ? $message : '';
    }

    /**
     * Joins prepared conditions into a WHERE clause.
     *
     * @param array $clauses Conditions, each already escaped by its own prepare() call.
     * @return string SQL WHERE clause, or 'WHERE 1=1' when there is nothing to filter on.
     */
    private static function where_sql( $clauses ) {
        $clauses = array_values( array_filter( (array) $clauses, 'strlen' ) );

        return 'WHERE ' . ( empty( $clauses ) ? '1=1' : implode( ' AND ', $clauses ) );
    }

    /**
     * Returns the current cache generation.
     *
     * @return int Generation number.
     */
    private static function cache_version() {
        return max( 1, (int) get_option( self::CACHE_VERSION_OPTION, 1 ) );
    }

    /**
     * Builds the object cache key for one read.
     *
     * Every input that can change the returned rows is folded into the key, so
     * two different queries can never collide. The generation is included, which
     * is what makes flush_query_cache() invalidate all reads at once.
     *
     * @param string $table   Fully prefixed table name.
     * @param string $columns Comma separated list of columns to select.
     * @param array  $clauses Prepared conditions the query is filtered by.
     * @param array  $shape   Remaining query shaping, such as order and paging.
     * @return string Cache key, of fixed length regardless of input size.
     */
    private static function query_cache_key( $table, $columns, $clauses, $shape = [] ) {
        return 'q_' . md5(
            wp_json_encode(
                [
                    self::cache_version(),
                    $table,
                    $columns,
                    array_values( (array) $clauses ),
                    $shape,
                ]
            )
        );
    }

    /**
     * Invalidates every cached read.
     *
     * Called after any write to the plugin tables, including the direct
     * $wpdb->insert() calls in the AJAX handlers, which bypass this class.
     *
     * Two mechanisms are used together on purpose. Bumping the generation makes
     * every previously computed key unreachable, which is what guarantees
     * freshness. Flushing the group then reclaims the memory those unreachable
     * keys would otherwise hold until they expire.
     */
    public static function flush_query_cache() {
        $next = self::cache_version() + 1;

        update_option( self::CACHE_VERSION_OPTION, $next, false );
        wp_cache_set( self::CACHE_VERSION_OPTION, $next, self::CACHE_GROUP );
        wp_cache_flush_group( self::CACHE_GROUP );
    }

    /**
     * Reads one page of rows from one of the plugin tables.
     *
     * Table names, column lists and ORDER BY clauses are hard-coded literals
     * supplied by the callers. Request values only ever reach SQL through the
     * $clauses array, where each entry has already been escaped by its own
     * $wpdb->prepare() call, and the table name itself is bound as an
     * identifier with the %i placeholder rather than interpolated.
     *
     * @param string $table   Fully prefixed table name.
     * @param string $columns Comma separated list of columns to select.
     * @param array  $clauses Prepared conditions to AND together.
     * @param string $order   ORDER BY clause, or '' to leave the rows unordered.
     * @param int    $limit   Rows to read, or 0 to read every matching row.
     * @param int    $offset  Rows to skip, used with a non-zero $limit.
     * @return array Matching rows.
     */
    private static function select_rows( $table, $columns, $clauses, $order = '', $limit = 0, $offset = 0 ) {
        global $wpdb;

        $cache_key = self::query_cache_key(
            $table,
            $columns,
            $clauses,
            [
                'order' => $order,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        $where_sql = self::where_sql( $clauses );

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * $columns, $where_sql and $order are literals assembled by this plugin,
         * never by request input. Every request value was bound by the prepare()
         * calls that produced $clauses, and the table name is bound as an
         * identifier by the %i placeholder.
         */
        if ( $limit > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$columns} FROM %i {$where_sql} {$order} LIMIT %d OFFSET %d", $table, $limit, $offset ) );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$columns} FROM %i {$where_sql} {$order}", $table ) );
        }
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        $rows = is_array( $rows ) ? $rows : [];

        wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, self::CACHE_TTL );

        return $rows;
    }

    /**
     * Counts the rows in one of the plugin tables.
     *
     * @param string $table   Fully prefixed table name.
     * @param array  $clauses Prepared conditions to AND together.
     * @return int Number of matching rows.
     */
    private static function select_count( $table, $clauses = [] ) {
        global $wpdb;

        $cache_key = self::query_cache_key( $table, 'COUNT(*)', $clauses );

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

        // A cached count of 0 is 0, not false, so this only misses on a real miss.
        if ( false !== $cached ) {
            return (int) $cached;
        }

        $where_sql = self::where_sql( $clauses );

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * See select_rows(): every value in $clauses is already bound.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i {$where_sql}", $table ) );
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        wp_cache_set( $cache_key, $count, self::CACHE_GROUP, self::CACHE_TTL );

        return $count;
    }

    /**
     * Counts rows grouped by a single column, for the dashboard breakdowns.
     *
     * @param string $table   Fully prefixed table name.
     * @param string $column  Column to group by.
     * @param array  $clauses Prepared conditions to AND together.
     * @return array Rows of column => count pairs, as objects.
     */
    private static function select_group_counts( $table, $column, $clauses = [] ) {
        global $wpdb;

        $allowed = [ 'blood_type', 'status', 'urgency', 'city' ];

        if ( ! in_array( $column, $allowed, true ) ) {
            return [];
        }

        $cache_key = self::query_cache_key( $table, "{$column} AS label, COUNT(*) AS total", $clauses, [ 'group' => $column ] );

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        $where_sql = self::where_sql( $clauses );

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * See select_rows(): $column is checked against a whitelist above and
         * every value in $clauses is already bound.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$column} AS label, COUNT(*) AS total FROM %i {$where_sql} GROUP BY {$column} ORDER BY {$column}", $table ) );
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        $rows = is_array( $rows ) ? $rows : [];

        wp_cache_set( $cache_key, $rows, self::CACHE_GROUP, self::CACHE_TTL );

        return $rows;
    }

    /**
     * Returns the distinct, non-empty cities stored in a table.
     *
     * @param string $table   Fully prefixed table name.
     * @param array  $clauses Prepared conditions to AND together.
     * @return array City names, alphabetically ordered.
     */
    private static function select_cities( $table, $clauses = [] ) {
        global $wpdb;

        $cache_key = self::query_cache_key( $table, 'DISTINCT city', $clauses );

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

        if ( is_array( $cached ) ) {
            return $cached;
        }

        $where_sql = self::where_sql( array_merge( [ "city IS NOT NULL AND city != ''" ], (array) $clauses ) );

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * See select_rows(): every value in $clauses is already bound.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
        $cities = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT city FROM %i {$where_sql} ORDER BY city", $table ) );
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        $cities = is_array( $cities ) ? $cities : [];

        wp_cache_set( $cache_key, $cities, self::CACHE_GROUP, self::CACHE_TTL );

        return $cities;
    }

    /**
     * Loads a single row by its primary key.
     *
     * @param string $table   Fully prefixed table name.
     * @param int    $id      Primary key to load.
     * @param string $columns Comma separated list of columns to select.
     * @return object|null The row, or null when it no longer exists.
     */
    private static function select_row( $table, $id, $columns = '*' ) {
        global $wpdb;

        $id = (int) $id;

        if ( $id < 1 ) {
            return null;
        }

        $cache_key = self::query_cache_key( $table, $columns, [ 'id' => $id ] );

        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );

        // A cached null row is null, not false, so a missing row stays missing.
        if ( false !== $cached ) {
            return $cached;
        }

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * $columns is a hard-coded literal supplied by the caller, and the row
         * id is bound as %d below.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Cached above under a generation-scoped key and invalidated by flush_query_cache().
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT {$columns} FROM %i WHERE id = %d", $table, $id ) );
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        wp_cache_set( $cache_key, $row, self::CACHE_GROUP, self::CACHE_TTL );

        return $row;
    }

    /**
     * Counts the donors registered with an email address.
     *
     * Used to keep self-registration from creating duplicate donor records.
     * The address is bound as a value and the table name as an identifier, so
     * neither reaches the query by interpolation.
     *
     * @param string $email Email address to look for.
     * @return int Number of donors using that address.
     */
    public static function donors_count_by_email( $email ) {
        global $wpdb;

        $email = sanitize_email( $email );

        if ( '' === $email ) {
            return 0;
        }

        return self::select_count(
            self::donors_table(),
            [ $wpdb->prepare( 'email = %s', $email ) ]
        );
    }

    /**
     * Collects the email addresses of available donors of the given types.
     *
     * @param array $blood_types Blood types to match.
     * @return array Rows exposing an email column.
     */
    public static function donor_emails_for_types( $blood_types ) {
        global $wpdb;

        $blood_types = array_values( array_filter( array_map( 'sanitize_text_field', (array) $blood_types ), 'strlen' ) );

        if ( empty( $blood_types ) ) {
            return [];
        }

        $placeholders = implode( ', ', array_fill( 0, count( $blood_types ), '%s' ) );
        $values       = array_merge( [ self::donors_table() ], $blood_types );

        /*
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * $placeholders is a generated run of %s markers, one per bound value,
         * and the table name is bound as an identifier.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Runs once per request notification, not per page view.
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT email FROM %i WHERE blood_type IN ({$placeholders}) AND is_available = 1", $values ) );
        /* phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared */

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Builds the filtered, paginated donor list for the admin screen.
     *
     * @return array Rows, totals and the filter values currently in effect.
     */
    public static function admin_donors_data() {
        global $wpdb;

        $search     = self::read_request_param( 's' );
        $blood_type = self::read_request_param( 'blood_type' );
        $city       = self::read_request_param( 'city' );
        $page       = self::list_page_number();
        $per_page   = self::ADMIN_PER_PAGE;
        $table      = self::donors_table();
        $clauses    = [];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)', $like, $like, $like, $like );
        }

        if ( '' !== $blood_type ) {
            $clauses[] = $wpdb->prepare( 'blood_type = %s', $blood_type );
        }

        if ( '' !== $city ) {
            $clauses[] = $wpdb->prepare( 'city = %s', $city );
        }

        $total = self::select_count( $table, $clauses );

        return [
            'items'       => self::select_rows(
                $table,
                'id, first_name, last_name, email, phone, blood_type, city, is_available, created_at',
                $clauses,
                'ORDER BY created_at DESC',
                $per_page,
                ( $page - 1 ) * $per_page
            ),
            'total'       => $total,
            'total_pages' => (int) ceil( $total / $per_page ),
            'page'        => $page,
            'per_page'    => $per_page,
            'cities'      => self::select_cities( $table ),
            'search'      => $search,
            'blood_type'  => $blood_type,
            'city'        => $city,
        ];
    }

    /**
     * Builds the filtered, paginated donation request list for the admin screen.
     *
     * @return array Rows, totals and the filter values currently in effect.
     */
    public static function admin_requests_data() {
        global $wpdb;

        $search   = self::read_request_param( 's' );
        $blood    = self::read_request_param( 'blood_type' );
        $status   = self::read_request_param( 'status' );
        $urgency  = self::read_request_param( 'urgency' );
        $page     = self::list_page_number();
        $per_page = self::ADMIN_PER_PAGE;
        $table    = self::requests_table();
        $clauses  = [];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(patient_name LIKE %s OR requester_name LIKE %s OR hospital_name LIKE %s)', $like, $like, $like );
        }

        if ( '' !== $blood ) {
            $clauses[] = $wpdb->prepare( 'blood_type_needed = %s', $blood );
        }

        if ( '' !== $status ) {
            $clauses[] = $wpdb->prepare( 'status = %s', $status );
        }

        if ( '' !== $urgency ) {
            $clauses[] = $wpdb->prepare( 'urgency = %s', $urgency );
        }

        $total = self::select_count( $table, $clauses );

        return [
            'items'         => self::select_rows(
                $table,
                'id, requester_name, patient_name, blood_type_needed, units_needed, hospital_name, city, urgency, status, created_at',
                $clauses,
                'ORDER BY created_at DESC',
                $per_page,
                ( $page - 1 ) * $per_page
            ),
            'total'         => $total,
            'total_pages'   => (int) ceil( $total / $per_page ),
            'page'          => $page,
            'per_page'      => $per_page,
            'pending_count' => self::select_count( $table, [ "status = 'pending'" ] ),
            'search'        => $search,
            'blood_type'    => $blood,
            'status'        => $status,
            'urgency'       => $urgency,
        ];
    }

    /**
     * Builds the filtered, paginated blood bank list for the admin screen.
     *
     * @return array Rows, totals and the filter values currently in effect.
     */
    public static function admin_blood_banks_data() {
        global $wpdb;

        $search  = self::read_request_param( 's' );
        $city    = self::read_request_param( 'city' );
        $status  = self::read_request_param( 'status' );
        $page    = self::list_page_number();
        $per_page = self::ADMIN_PER_PAGE;
        $table   = self::blood_banks_table();
        $clauses = [];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(name LIKE %s OR city LIKE %s OR address LIKE %s)', $like, $like, $like );
        }

        if ( '' !== $city ) {
            $clauses[] = $wpdb->prepare( 'city = %s', $city );
        }

        if ( 'active' === $status ) {
            $clauses[] = 'is_active = 1';
        } elseif ( 'inactive' === $status ) {
            $clauses[] = 'is_active = 0';
        }

        $total = self::select_count( $table, $clauses );

        return [
            'items'       => self::select_rows(
                $table,
                'id, name, description, address, city, state, phone, email, operating_hours, is_active',
                $clauses,
                'ORDER BY name ASC',
                $per_page,
                ( $page - 1 ) * $per_page
            ),
            'total'       => $total,
            'total_pages' => (int) ceil( $total / $per_page ),
            'page'        => $page,
            'per_page'    => $per_page,
            'cities'      => self::select_cities( $table ),
            'search'      => $search,
            'city'        => $city,
            'status'      => $status,
        ];
    }

    /**
     * Collects everything the admin dashboard screen renders.
     *
     * @return array Counters, blood type breakdown and recent activity.
     */
    public static function admin_dashboard_data() {
        $donors      = self::donors_table();
        $requests    = self::requests_table();
        $blood_banks = self::blood_banks_table();

        return [
            'total_donors'        => self::select_count( $donors ),
            'available_donors'    => self::select_count( $donors, [ 'is_available = 1' ] ),
            'total_requests'      => self::select_count( $requests ),
            'pending_requests'    => self::select_count( $requests, [ "status = 'pending'" ] ),
            'fulfilled_requests'  => self::select_count( $requests, [ "status = 'fulfilled'" ] ),
            'total_blood_banks'   => self::select_count( $blood_banks, [ 'is_active = 1' ] ),
            'blood_type_stats'    => self::select_group_counts( $donors, 'blood_type' ),
            'recent_donors'       => self::select_rows( $donors, 'id, first_name, last_name, email, blood_type, city, is_available', [], 'ORDER BY created_at DESC', 5 ),
            'recent_requests'     => self::select_rows( $requests, 'id, patient_name, blood_type_needed, units_needed, hospital_name, urgency', [], 'ORDER BY created_at DESC', 5 ),
        ];
    }

    /**
     * Collects everything the public dashboard shortcode renders.
     *
     * @return array Counters, blood type breakdown and open requests.
     */
    public static function public_dashboard_data() {
        $donors      = self::donors_table();
        $requests    = self::requests_table();
        $blood_banks = self::blood_banks_table();

        return [
            'total_donors'      => self::select_count( $donors, [ 'is_available = 1' ] ),
            'total_requests'    => self::select_count( $requests, [ "status = 'pending'" ] ),
            'total_blood_banks' => self::select_count( $blood_banks, [ 'is_active = 1' ] ),
            'blood_type_stats'  => self::select_group_counts( $donors, 'blood_type', [ 'is_available = 1' ] ),
            'recent_requests'   => self::select_rows(
                $requests,
                'id, patient_name, blood_type_needed, units_needed, hospital_name, urgency',
                [ "status = 'pending'" ],
                "ORDER BY CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END, created_at DESC",
                6
            ),
        ];
    }

    /**
     * Builds the publicly listed donor directory.
     *
     * @return array Donor rows, filter options and the filters in effect.
     */
    public static function public_donors_data() {
        global $wpdb;

        $search     = self::read_request_param( 'obdm_search' );
        $blood_type = self::read_request_param( 'obdm_blood_type' );
        $city       = self::read_request_param( 'obdm_city' );
        $table      = self::donors_table();
        $clauses    = [ 'is_available = 1' ];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(first_name LIKE %s OR last_name LIKE %s OR city LIKE %s)', $like, $like, $like );
        }

        if ( '' !== $blood_type ) {
            $clauses[] = $wpdb->prepare( 'blood_type = %s', $blood_type );
        }

        if ( '' !== $city ) {
            $clauses[] = $wpdb->prepare( 'city = %s', $city );
        }

        return [
            'items'      => self::select_rows(
                $table,
                'id, first_name, last_name, blood_type, city, last_donation_date',
                $clauses,
                'ORDER BY created_at DESC'
            ),
            'cities'     => self::select_cities( $table, [ 'is_available = 1' ] ),
            'search'     => $search,
            'blood_type' => $blood_type,
            'city'       => $city,
        ];
    }

    /**
     * Builds the publicly listed donation requests.
     *
     * @return array Request rows, and the filters in effect.
     */
    public static function public_requests_data() {
        global $wpdb;

        $search   = self::read_request_param( 'obdm_search' );
        $blood    = self::read_request_param( 'obdm_blood_type' );
        $status   = self::read_request_param( 'obdm_status' );
        $urgency  = self::read_request_param( 'obdm_urgency' );
        $table    = self::requests_table();
        $clauses  = [ "status != 'cancelled'" ];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(patient_name LIKE %s OR hospital_name LIKE %s)', $like, $like );
        }

        if ( '' !== $blood ) {
            $clauses[] = $wpdb->prepare( 'blood_type_needed = %s', $blood );
        }

        if ( '' !== $status ) {
            $clauses[] = $wpdb->prepare( 'status = %s', $status );
        }

        if ( '' !== $urgency ) {
            $clauses[] = $wpdb->prepare( 'urgency = %s', $urgency );
        }

        return [
            'items'      => self::select_rows(
                $table,
                'id, patient_name, blood_type_needed, units_needed, hospital_name, city, requester_email, requester_phone, urgency, needed_by, status',
                $clauses,
                "ORDER BY CASE urgency WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END, created_at DESC"
            ),
            'search'     => $search,
            'blood_type' => $blood,
            'status'     => $status,
            'urgency'    => $urgency,
        ];
    }

    /**
     * Builds the publicly listed blood bank directory.
     *
     * @return array Blood bank rows, city options and the filters in effect.
     */
    public static function public_blood_banks_data() {
        global $wpdb;

        $search  = self::read_request_param( 'obdm_search' );
        $city    = self::read_request_param( 'obdm_city' );
        $table   = self::blood_banks_table();
        $clauses = [ 'is_active = 1' ];

        if ( '' !== $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare( '(name LIKE %s OR address LIKE %s)', $like, $like );
        }

        if ( '' !== $city ) {
            $clauses[] = $wpdb->prepare( 'city = %s', $city );
        }

        return [
            'items'  => self::select_rows(
                $table,
                'id, name, description, address, city, state, zip_code, phone, email, website, operating_hours',
                $clauses,
                'ORDER BY name ASC'
            ),
            'cities' => self::select_cities( $table, [ 'is_active = 1' ] ),
            'search' => $search,
            'city'   => $city,
        ];
    }

    /**
     * Returns the admin donor record being edited, or a blank one.
     *
     * @param int $id Primary key of the donor to load.
     * @return object The stored row, or a blank row for the "add" form.
     */
    public static function admin_donor_record( $id ) {
        $donor = self::select_row(
            self::donors_table(),
            $id,
            'id, first_name, last_name, email, phone, blood_type, date_of_birth, gender, weight, address, city, state, zip_code, country, last_donation_date, medical_conditions, is_available'
        );

        if ( $donor ) {
            return $donor;
        }

        $blank              = new stdClass();
        $blank->id          = 0;
        $blank->first_name  = '';
        $blank->last_name   = '';
        $blank->email       = '';
        $blank->phone       = '';
        $blank->blood_type  = '';
        $blank->date_of_birth = '';
        $blank->gender      = '';
        $blank->weight      = '';
        $blank->address     = '';
        $blank->city        = '';
        $blank->state       = '';
        $blank->zip_code    = '';
        $blank->country     = '';
        $blank->last_donation_date   = '';
        $blank->medical_conditions  = '';
        $blank->is_available = 1;

        return $blank;
    }

    /**
     * Returns the admin blood request record being edited, or a blank one.
     *
     * @param int $id Primary key of the request to load.
     * @return object The stored row, or a blank row for the "add" form.
     */
    public static function admin_request_record( $id ) {
        $request = self::select_row(
            self::requests_table(),
            $id,
            'id, requester_name, requester_email, requester_phone, patient_name, blood_type_needed, units_needed, hospital_name, hospital_address, city, urgency, needed_by, additional_info, status'
        );

        if ( $request ) {
            return $request;
        }

        $blank                  = new stdClass();
        $blank->id              = 0;
        $blank->requester_name  = '';
        $blank->requester_email = '';
        $blank->requester_phone = '';
        $blank->patient_name    = '';
        $blank->blood_type_needed = '';
        $blank->units_needed    = 1;
        $blank->hospital_name   = '';
        $blank->hospital_address = '';
        $blank->city            = '';
        $blank->urgency         = 'normal';
        $blank->needed_by       = '';
        $blank->additional_info = '';
        $blank->status          = 'pending';

        return $blank;
    }

    /**
     * Returns the admin blood bank record being edited, or a blank one.
     *
     * @param int $id Primary key of the blood bank to load.
     * @return object The stored row, or a blank row for the "add" form.
     */
    public static function admin_blood_bank_record( $id ) {
        $bank = self::select_row(
            self::blood_banks_table(),
            $id,
            'id, name, description, address, city, state, zip_code, country, phone, email, website, latitude, longitude, operating_hours, is_active'
        );

        if ( $bank ) {
            return $bank;
        }

        $blank                = new stdClass();
        $blank->id            = 0;
        $blank->name          = '';
        $blank->description   = '';
        $blank->address       = '';
        $blank->city          = '';
        $blank->state         = '';
        $blank->zip_code      = '';
        $blank->country       = '';
        $blank->phone         = '';
        $blank->email         = '';
        $blank->website       = '';
        $blank->latitude      = '';
        $blank->longitude     = '';
        $blank->operating_hours = '';
        $blank->is_active     = 1;

        return $blank;
    }

    public static function get_blood_types() {
        return [
            'A+' => 'A+',
            'A-' => 'A-',
            'B+' => 'B+',
            'B-' => 'B-',
            'AB+' => 'AB+',
            'AB-' => 'AB-',
            'O+' => 'O+',
            'O-' => 'O-',
        ];
    }

    public static function get_compatible_blood_types($blood_type) {
        $compatibility = [
            'A+'  => ['A+', 'A-', 'O+', 'O-'],
            'A-'  => ['A-', 'O-'],
            'B+'  => ['B+', 'B-', 'O+', 'O-'],
            'B-'  => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
            'O+'  => ['O+', 'O-'],
            'O-'  => ['O-'],
        ];
        return isset($compatibility[$blood_type]) ? $compatibility[$blood_type] : [];
    }

    public static function get_urgency_labels() {
        return [
            'normal'  => __('Normal', 'obydullah-blood-bank-manager'),
            'urgent'  => __('Urgent', 'obydullah-blood-bank-manager'),
            'critical' => __('Critical', 'obydullah-blood-bank-manager'),
        ];
    }

    public static function get_status_labels() {
        return [
            'pending'   => __('Pending', 'obydullah-blood-bank-manager'),
            'fulfilled' => __('Fulfilled', 'obydullah-blood-bank-manager'),
            'cancelled' => __('Cancelled', 'obydullah-blood-bank-manager'),
        ];
    }

    /**
     * Cache-busting asset version based on the file modification time.
     */
    public static function asset_version($relative_path) {
        $file = OBDM_PLUGIN_DIR . ltrim($relative_path, '/');
        return file_exists($file) ? (string) filemtime($file) : OBDM_VERSION;
    }

    public static function ui_icon($icon, $class = '') {
        return '<span class="dashicons ' . esc_attr($icon) . ' ' . esc_attr($class) . '" aria-hidden="true"></span>';
    }

    public static function ui_page_header($args = []) {
        $args = wp_parse_args($args, [
            'icon'         => '',
            'title'        => '',
            'subtitle'     => '',
            'action_url'   => '',
            'action_label' => '',
            'action_icon'  => 'dashicons-plus-alt2',
            'back_url'     => '',
        ]);

        echo '<header class="obdm-page-header">';
        echo '<div class="obdm-page-header__lead">';
        if (!empty($args['icon'])) {
            echo '<span class="obdm-page-header__icon dashicons ' . esc_attr($args['icon']) . '" aria-hidden="true"></span>';
        }
        echo '<div class="obdm-page-header__text">';
        echo '<h1 class="obdm-page-header__title">' . esc_html($args['title']) . '</h1>';
        if (!empty($args['subtitle'])) {
            echo '<p class="obdm-page-header__subtitle">' . esc_html($args['subtitle']) . '</p>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="obdm-page-header__actions">';
        if (!empty($args['back_url'])) {
            echo '<a class="obdm-button obdm-button--ghost" href="' . esc_url($args['back_url']) . '">';
            echo wp_kses_post(self::ui_icon('dashicons-arrow-left-alt'));
            echo esc_html__('Back', 'obydullah-blood-bank-manager');
            echo '</a>';
        }
        if (!empty($args['action_url']) && !empty($args['action_label'])) {
            echo '<a class="obdm-button obdm-button--primary" href="' . esc_url($args['action_url']) . '">';
            echo wp_kses_post(self::ui_icon($args['action_icon']));
            echo esc_html($args['action_label']);
            echo '</a>';
        }
        echo '</div>';
        echo '</header>';
    }

    public static function ui_empty_state($args = []) {
        $args = wp_parse_args($args, [
            'icon'         => 'dashicons-marker',
            'title'        => __('Nothing to show yet', 'obydullah-blood-bank-manager'),
            'description'  => '',
            'action_url'   => '',
            'action_label' => '',
        ]);

        echo '<div class="obdm-empty">';
        echo '<span class="obdm-empty__icon dashicons ' . esc_attr($args['icon']) . '" aria-hidden="true"></span>';
        echo '<p class="obdm-empty__title">' . esc_html($args['title']) . '</p>';
        if (!empty($args['description'])) {
            echo '<p class="obdm-empty__text">' . esc_html($args['description']) . '</p>';
        }
        if (!empty($args['action_url']) && !empty($args['action_label'])) {
            echo '<a class="obdm-button obdm-button--primary" href="' . esc_url($args['action_url']) . '">';
            echo wp_kses_post(self::ui_icon('dashicons-plus-alt2'));
            echo esc_html($args['action_label']);
            echo '</a>';
        }
        echo '</div>';
    }

    public static function ui_blood_badge($blood_type) {
        $modifier = strtolower(str_replace(['-', '+'], ['neg', 'pos'], $blood_type));
        return '<span class="obdm-badge obdm-badge--blood obdm-blood-' . esc_attr($modifier) . '">' . esc_html($blood_type) . '</span>';
    }

    public static function ui_status_badge($status) {
        $labels = self::get_status_labels();
        $label  = $labels[$status] ?? match ($status) {
            'active'   => __('Active', 'obydullah-blood-bank-manager'),
            'inactive' => __('Inactive', 'obydullah-blood-bank-manager'),
            default    => ucfirst($status),
        };
        return '<span class="obdm-badge obdm-badge--status is-' . esc_attr($status) . '"><span class="obdm-badge__dot"></span>' . esc_html($label) . '</span>';
    }

    public static function ui_urgency_badge($urgency) {
        $labels = self::get_urgency_labels();
        $label = isset($labels[$urgency]) ? $labels[$urgency] : $urgency;
        return '<span class="obdm-badge obdm-badge--urgency is-' . esc_attr($urgency) . '"><span class="obdm-badge__dot"></span>' . esc_html($label) . '</span>';
    }

    public static function ui_avatar($name) {
        $name = trim((string) $name);
        $parts = preg_split('/\s+/', $name, 2);
        $initials = '';
        if (!empty($parts[0])) {
            $initials = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
            if (isset($parts[1]) && '' !== $parts[1]) {
                $initials .= function_exists('mb_substr') ? mb_substr($parts[1], 0, 1) : substr($parts[1], 0, 1);
            }
        }
        if ('' === $initials) {
            $initials = '?';
        }
        $hue = function_exists('crc32') ? (crc32(strtolower($name)) % 360) : 0;
        return '<span class="obdm-avatar" style="--obdm-hue:' . (int) $hue . '" aria-hidden="true">' . esc_html(strtoupper($initials)) . '</span>';
    }

    public static function ui_row_actions($edit_url, $delete_url, $confirm_message, $labels = []) {
        $labels = wp_parse_args($labels, [
            'edit'   => __('Edit', 'obydullah-blood-bank-manager'),
            'delete' => __('Delete', 'obydullah-blood-bank-manager'),
        ]);

        echo '<div class="obdm-row-actions">';
        echo '<a class="obdm-iconbtn" href="' . esc_url($edit_url) . '" aria-label="' . esc_attr($labels['edit']) . '" title="' . esc_attr($labels['edit']) . '">';
        echo wp_kses_post(self::ui_icon('dashicons-edit'));
        echo '</a>';
        echo '<a class="obdm-iconbtn obdm-iconbtn--danger obdm-delete-btn" href="' . esc_url($delete_url) . '" aria-label="' . esc_attr($labels['delete']) . '" title="' . esc_attr($labels['delete']) . '" data-obdm-confirm="' . esc_attr($confirm_message) . '">';
        echo wp_kses_post(self::ui_icon('dashicons-trash'));
        echo '</a>';
        echo '</div>';
    }

    public static function ui_pagination($current_page, $total_pages, $total_items = 0) {
        if ($total_pages < 2) {
            return;
        }

        $links = paginate_links([
            'base'      => add_query_arg('paged', '%#%'),
            'format'    => '',
            'current'   => max(1, (int) $current_page),
            'total'     => (int) $total_pages,
            'mid_size'  => 2,
            'prev_text' => self::ui_icon('dashicons-arrow-left-alt'),
            'next_text' => self::ui_icon('dashicons-arrow-right-alt'),
            'type'      => 'array',
        ]);

        echo '<div class="obdm-pagination">';
        if ($total_items > 0) {
            echo '<p class="obdm-pagination__count">';
            echo esc_html(
                sprintf(
                    /* translators: %s: number of items. */
                    _n('%s item', '%s items', (int) $total_items, 'obydullah-blood-bank-manager'),
                    number_format_i18n($total_items)
                )
            );
            echo '</p>';
        }
        if (is_array($links)) {
            echo '<ul class="page-numbers">';
            foreach ($links as $link) {
                echo '<li>' . wp_kses_post($link) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }
}

function obdm_blood_bank_manager() {
    return Obdm_Blood_Bank_Manager::instance();
}

obdm_blood_bank_manager();

require_once OBDM_PLUGIN_DIR . 'includes/obdm-ajax-handlers.php';