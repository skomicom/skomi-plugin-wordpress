<?php
/**
 * Plugin Name:       Skomi Analytics
 * Plugin URI:        https://skomi.com/docs/installing-on-wordpress
 * Description:       Adds the Skomi tracking snippet to your site. Analytics, heatmaps and feedback from one script, with nothing stored on the visitor's device by default.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Skomi
 * Author URI:        https://skomi.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       skomi-wordpress
 *
 * The whole plugin is one script tag put in the right place. What it adds over
 * pasting that tag into a theme is that it survives a theme change — which is
 * the way analytics silently stops on WordPress, with no error and no sign that
 * anything happened.
 */

defined( 'ABSPATH' ) || exit;

define( 'SKOMI_WORDPRESS_VERSION', '1.0.0' );

/** Where Edge is published. Overridable for a self-hosted or staging tracker. */
define( 'SKOMI_WORDPRESS_DEFAULT_ORIGIN', 'https://t.skomi.com' );

/**
 * One stored setting, by its short name.
 *
 * ⚠ <b>Reads the current key and falls back to the one used before the plugin
 * was renamed.</b> The options are rows in `wp_options` on somebody's site, not
 * identifiers in a file: renaming them outright would have blanked the settings
 * of every install that already had them, and looked to the owner like the
 * plugin had forgotten its own configuration. Saving always writes the new key;
 * `uninstall.php` removes both.
 *
 * There is no migration step and nothing is written on a front-end request. The
 * old row simply stops being read once a new one exists.
 */
function skomi_wordpress_option( $name, $default ) {
	$value = get_option( 'skomi_wordpress_' . $name, null );

	if ( null !== $value ) {
		return $value;
	}

	return get_option( 'skomi_analytics_' . $name, $default );
}

/**
 * Whether collection is switched on at all.
 *
 * Separate from having a site ID on purpose: this is the switch somebody reaches
 * for while debugging, or during a migration, or when a client asks for
 * measurement to stop for a fortnight. Clearing the site ID would do it too and
 * would lose the id, which is the thing they would then have to go and find
 * again.
 */
function skomi_wordpress_enabled() {
	return '1' === skomi_wordpress_option( 'enabled', '1' );
}

/**
 * The site ID, or an empty string.
 *
 * ⚠ Validated on the way in AND on the way out. A malformed id in the database —
 * from a migration, a search-and-replace, or an earlier version of this plugin —
 * would otherwise be printed into a script URL, and a script URL is the one
 * place a bad value becomes markup on every page.
 */
function skomi_wordpress_site_id() {
	$stored = skomi_wordpress_option( 'site_id', '' );

	return skomi_wordpress_is_site_id( $stored ) ? strtolower( $stored ) : '';
}

/**
 * Whether a string is a site ID. Skomi issues a UUID, and the id is part of the
 * bundle's path rather than a query parameter, so anything else cannot be a
 * mistyped id — it is a different URL.
 */
function skomi_wordpress_is_site_id( $value ) {
	return is_string( $value ) && 1 === preg_match(
		'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
		trim( $value )
	);
}

/** The tracker origin, without a trailing slash. */
function skomi_wordpress_origin() {
	$origin = skomi_wordpress_option( 'origin', SKOMI_WORDPRESS_DEFAULT_ORIGIN );

	if ( ! is_string( $origin ) || '' === trim( $origin ) ) {
		$origin = SKOMI_WORDPRESS_DEFAULT_ORIGIN;
	}

	return untrailingslashit( esc_url_raw( trim( $origin ) ) );
}

/** The bundle URL for this site, in the shape Skomi's own snippet uses. */
function skomi_wordpress_script_url() {
	$id = skomi_wordpress_site_id();

	return '' === $id ? '' : skomi_wordpress_origin() . '/s/' . $id . '.js';
}

/**
 * Whether this request should be measured.
 *
 * ⚠ Logged-in users are excluded by DEFAULT, and that is the opposite of most
 * plugins. A site's own staff reload their own pages far more than any visitor
 * does, and a small site's numbers are mostly its owner until somebody notices.
 * Turning it off is one checkbox; discovering it months later is a rebuild of
 * every conclusion drawn in between.
 */
function skomi_wordpress_should_track() {
	if ( ! skomi_wordpress_enabled() ) {
		return false;
	}

	if ( '' === skomi_wordpress_script_url() ) {
		return false;
	}

	// Never in an admin screen, a feed, a REST response or a preview.
	if ( is_admin() || is_feed() || is_preview() || wp_doing_ajax() ) {
		return false;
	}

	if ( skomi_wordpress_option( 'track_logged_in', '0' ) !== '1' && is_user_logged_in() ) {
		return false;
	}

	/**
	 * Filters whether the snippet is printed for this request.
	 *
	 * @param bool $track Whether to track.
	 */
	return (bool) apply_filters( 'skomi_wordpress_should_track', true );
}

/**
 * Enqueue the bundle in the footer, deferred.
 *
 * ⚠ `null` as the version, deliberately. WordPress appends `?ver=…` to an
 * enqueued script unless the version is null, and the bundle is served at a
 * fixed path whose contents already carry their own hash — a query string only
 * splits the cache and makes the URL stop matching what the dashboard shows
 * somebody to paste.
 */
function skomi_wordpress_enqueue() {
	if ( ! skomi_wordpress_should_track() ) {
		return;
	}

	wp_enqueue_script(
		'skomi-wordpress',
		skomi_wordpress_script_url(),
		array(),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'skomi_wordpress_enqueue' );

// ---------------------------------------------------------------- settings

function skomi_wordpress_settings_menu() {
	add_options_page(
		__( 'Skomi Analytics', 'skomi-wordpress' ),
		__( 'Skomi Analytics', 'skomi-wordpress' ),
		'manage_options',
		'skomi-wordpress',
		'skomi_wordpress_settings_page'
	);
}
add_action( 'admin_menu', 'skomi_wordpress_settings_menu' );

function skomi_wordpress_register_settings() {
	register_setting(
		'skomi_wordpress',
		'skomi_wordpress_enabled',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'skomi_wordpress_sanitize_checkbox',
			'default'           => '1',
		)
	);

	register_setting(
		'skomi_wordpress',
		'skomi_wordpress_site_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'skomi_wordpress_sanitize_site_id',
			'default'           => '',
		)
	);

	register_setting(
		'skomi_wordpress',
		'skomi_wordpress_origin',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'skomi_wordpress_sanitize_origin',
			'default'           => SKOMI_WORDPRESS_DEFAULT_ORIGIN,
		)
	);

	register_setting(
		'skomi_wordpress',
		'skomi_wordpress_track_logged_in',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'skomi_wordpress_sanitize_checkbox',
			'default'           => '0',
		)
	);
}
add_action( 'admin_init', 'skomi_wordpress_register_settings' );

/**
 * ⚠ An unreadable id is REFUSED rather than stored, and the operator is told.
 * Storing it would print a broken URL on every page of the site; storing an
 * empty string instead would silently switch measurement off and look like it
 * had been saved.
 */
function skomi_wordpress_sanitize_site_id( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	if ( '' === $value || skomi_wordpress_is_site_id( $value ) ) {
		return strtolower( $value );
	}

	add_settings_error(
		'skomi_wordpress_site_id',
		'skomi_wordpress_site_id_invalid',
		__( 'That is not a Skomi site ID. Copy it from your site in Skomi — it looks like 3fa85f64-5717-4562-b3fc-2c963f66afa6.', 'skomi-wordpress' )
	);

	return skomi_wordpress_site_id();
}

function skomi_wordpress_sanitize_origin( $value ) {
	$value = is_string( $value ) ? untrailingslashit( trim( $value ) ) : '';

	if ( '' === $value ) {
		return SKOMI_WORDPRESS_DEFAULT_ORIGIN;
	}

	// https only: the snippet is loaded on every page, and a mixed-content
	// script is one the browser refuses without saying so on the page.
	if ( 0 !== strpos( $value, 'https://' ) ) {
		add_settings_error(
			'skomi_wordpress_origin',
			'skomi_wordpress_origin_insecure',
			__( 'The tracker address must start with https://.', 'skomi-wordpress' )
		);

		return skomi_wordpress_origin();
	}

	return esc_url_raw( $value );
}

function skomi_wordpress_sanitize_checkbox( $value ) {
	return '1' === $value ? '1' : '0';
}

function skomi_wordpress_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$id  = skomi_wordpress_site_id();
	$url = skomi_wordpress_script_url();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Skomi Analytics', 'skomi-wordpress' ); ?></h1>

		<?php if ( ! skomi_wordpress_enabled() ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'Switched off. Nothing is collected and no script is loaded, but your site ID is kept.', 'skomi-wordpress' ); ?>
				</p>
			</div>
		<?php elseif ( '' === $id ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'Nothing is being collected yet. Add your site ID below — it is on your site in Skomi.', 'skomi-wordpress' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: %s: the script URL being loaded. */
						esc_html__( 'Collecting. This page loads %s', 'skomi-wordpress' ),
						'<code>' . esc_html( $url ) . '</code>'
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'skomi_wordpress' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'skomi-wordpress' ); ?></th>
					<td>
						<label>
							<input name="skomi_wordpress_enabled" type="checkbox" value="1"
							       <?php checked( skomi_wordpress_enabled() ); ?> />
							<?php esc_html_e( 'Collect analytics on this site', 'skomi-wordpress' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Turn this off to stop collecting without losing your site ID. No script is loaded while it is off.', 'skomi-wordpress' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="skomi_wordpress_site_id"><?php esc_html_e( 'Site ID', 'skomi-wordpress' ); ?></label>
					</th>
					<td>
						<input name="skomi_wordpress_site_id" id="skomi_wordpress_site_id" type="text"
						       class="regular-text code" value="<?php echo esc_attr( $id ); ?>"
						       placeholder="3fa85f64-5717-4562-b3fc-2c963f66afa6" />
						<p class="description">
							<?php esc_html_e( 'From your site in Skomi. It is not a secret — it appears in the page source of every page it measures.', 'skomi-wordpress' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Measure signed-in users', 'skomi-wordpress' ); ?></th>
					<td>
						<label>
							<input name="skomi_wordpress_track_logged_in" type="checkbox" value="1"
							       <?php checked( skomi_wordpress_option( 'track_logged_in', '0' ), '1' ); ?> />
							<?php esc_html_e( 'Include visits by people signed in to WordPress', 'skomi-wordpress' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Off by default. Your own reloads outnumber a small site\'s real traffic, and nothing in the numbers says so.', 'skomi-wordpress' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="skomi_wordpress_origin"><?php esc_html_e( 'Tracker address', 'skomi-wordpress' ); ?></label>
					</th>
					<td>
						<input name="skomi_wordpress_origin" id="skomi_wordpress_origin" type="url"
						       class="regular-text code" value="<?php echo esc_attr( skomi_wordpress_origin() ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Leave this alone unless Skomi told you otherwise.', 'skomi-wordpress' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'If nothing arrives', 'skomi-wordpress' ); ?></h2>
		<p>
			<?php esc_html_e( 'On WordPress the answer is nearly always a page cache that has not been purged since you saved this, or an optimisation plugin that copies third-party scripts to your own server — exclude Skomi from that, or it freezes at the day it was copied.', 'skomi-wordpress' ); ?>
		</p>
		<p>
			<a href="https://skomi.com/docs/snippet-not-working" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'What to check when the snippet is not working', 'skomi-wordpress' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/** A settings link on the plugins list, where people look for it first. */
function skomi_wordpress_action_links( $links ) {
	$settings = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=skomi-wordpress' ) ),
		esc_html__( 'Settings', 'skomi-wordpress' )
	);

	array_unshift( $links, $settings );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'skomi_wordpress_action_links' );
