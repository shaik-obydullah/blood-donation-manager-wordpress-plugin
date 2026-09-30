<?php
/**
 * Uninstall handler for Obydullah Blood Bank Manager.
 *
 * Runs only when the plugin is deleted from the Plugins screen. By default it
 * removes nothing that a site owner would miss, because the donor registry
 * holds personal data and a reinstall should restore the site exactly as it
 * was. Set "Delete all data on uninstall" in the plugin settings to also drop
 * the custom tables and the plugin options.
 *
 * This file runs without the main plugin file, so it cannot rely on the
 * Obdm_Blood_Bank_Manager class or the OBDM_* constants.
 *
 * @package Obydullah_Blood_Bank_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Removes the plugin's non-user-data leftovers for the current site.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function obdm_uninstall_current_site() {
    global $wpdb;

    // The plugin caches reads in a dedicated object cache group. Dropping the
    // group on every uninstall avoids serving cached lists from before the
    // plugin was removed.
    if ( function_exists( 'wp_cache_flush_group' ) ) {
        wp_cache_flush_group( 'obdm' );
    }

    // Re-register the rewrite rules without the obdm_campaign post type.
    if ( function_exists( 'flush_rewrite_rules' ) ) {
        flush_rewrite_rules();
    }

    $settings = get_option( 'obdm_settings' );

    if ( ! is_array( $settings ) || empty( $settings['delete_data_on_uninstall'] ) ) {
        return;
    }

    $tables = array( 'obdm_donors', 'obdm_requests', 'obdm_blood_banks' );

    foreach ( $tables as $table ) {
        // Table identifiers cannot be bound as query params, but %i escapes an
        // identifier safely and is available from the required WordPress 6.2.
        $full_name = $wpdb->prefix . preg_replace( '/[^a-z0-9_]/', '', $table );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Teardown only. WordPress exposes no API to drop a table, and the identifier is bound with %i from a hard-coded list plus the core table prefix. This runs only when the site owner opts in to "Delete all data on uninstall".
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $full_name ) );
    }

    $options = array( 'obdm_settings', 'obdm_cache_version', 'obdm_db_version', 'obdm_legacy_migrated' );

    foreach ( $options as $option ) {
        delete_option( $option );
    }
}

if ( is_multisite() ) {
    $obdm_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );

    foreach ( $obdm_site_ids as $obdm_site_id ) {
        switch_to_blog( $obdm_site_id );
        obdm_uninstall_current_site();
        restore_current_blog();
    }
} else {
    obdm_uninstall_current_site();
}
