<?php
/**
 * Removes what the plugin stored, when it is DELETED — not when it is
 * deactivated. Deactivating is what somebody does while debugging, and losing
 * their site ID for it would be a small cruelty.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// ⚠ Both generations of the names. The plugin's slug changed from
// skomi-analytics to skomi-wordpress and its options changed with it, but a site
// installed before that still has the old rows — and uninstall is the one moment
// they can be cleaned up.
$skomi_wordpress_options = array(
	'skomi_wordpress_enabled',
	'skomi_wordpress_site_id',
	'skomi_wordpress_origin',
	'skomi_wordpress_track_logged_in',

	'skomi_analytics_enabled',
	'skomi_analytics_site_id',
	'skomi_analytics_origin',
	'skomi_analytics_track_logged_in',
);

foreach ( $skomi_wordpress_options as $skomi_wordpress_option ) {
	delete_option( $skomi_wordpress_option );
}

// Multisite: each site in the network kept its own settings, because each one
// is a separate domain and therefore a separate site in Skomi.
if ( is_multisite() ) {
	$skomi_wordpress_sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $skomi_wordpress_sites as $skomi_wordpress_site ) {
		switch_to_blog( $skomi_wordpress_site );

		foreach ( $skomi_wordpress_options as $skomi_wordpress_option ) {
			delete_option( $skomi_wordpress_option );
		}

		restore_current_blog();
	}
}
