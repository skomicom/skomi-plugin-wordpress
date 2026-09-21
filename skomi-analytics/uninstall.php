<?php
/**
 * Removes what the plugin stored, when it is DELETED — not when it is
 * deactivated. Deactivating is what somebody does while debugging, and losing
 * their site ID for it would be a small cruelty.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// ⚠ BOTH generations of the names, and the second is not dead history: the slug
// went skomi-analytics → skomi-wordpress → skomi-analytics again, so a site can
// hold rows under either prefix depending on when it was installed and whether an
// administrator has loaded a page since the last upgrade. Uninstall is the one
// moment all of them can be cleaned up.
$skomi_analytics_options = array(
	'skomi_analytics_enabled',
	'skomi_analytics_site_id',
	'skomi_analytics_origin',
	'skomi_analytics_track_logged_in',

	'skomi_wordpress_enabled',
	'skomi_wordpress_site_id',
	'skomi_wordpress_origin',
	'skomi_wordpress_track_logged_in',
);

foreach ( $skomi_analytics_options as $skomi_analytics_option ) {
	delete_option( $skomi_analytics_option );
}

// Multisite: each site in the network kept its own settings, because each one
// is a separate domain and therefore a separate site in Skomi.
if ( is_multisite() ) {
	$skomi_analytics_sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $skomi_analytics_sites as $skomi_analytics_site ) {
		switch_to_blog( $skomi_analytics_site );

		foreach ( $skomi_analytics_options as $skomi_analytics_option ) {
			delete_option( $skomi_analytics_option );
		}

		restore_current_blog();
	}
}
