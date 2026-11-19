<?php
/**
 * Plugin Name:       WP Robots.txt Editor
 * Plugin URI:        https://github.com/Azanumer/wp-robots-txt-editor
 * Description:       Edit your site's virtual robots.txt from the WordPress admin — with validation, a "Disallow: /" safety warning, live preview and one-click restore to default.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Azan Umer
 * Author URI:        https://azanumer.cv
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-robots-txt-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WRTE_VERSION', '1.0.0' );
define( 'WRTE_OPTION', 'wrte_custom_robots' );

/**
 * The robots.txt WordPress generates when nothing is customised.
 *
 * Mirrors core's default: sitemap link (when the core sitemap is on)
 * plus the standard Disallow rules for wp-admin and search.
 *
 * @return string
 */
function wrte_default_robots() {
	$default = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";

	$sitemap_url = wp_sitemap_url( 'index' );
	if ( $sitemap_url ) {
		$default .= "\nSitemap: " . $sitemap_url . "\n";
	}

	return $default;
}

/**
 * The effective robots.txt: custom rules when saved, otherwise the default.
 *
 * @return string
 */
function wrte_effective_robots() {
	$custom = get_option( WRTE_OPTION, '' );
	if ( is_string( $custom ) && '' !== trim( $custom ) ) {
		return $custom;
	}
	return wrte_default_robots();
}

/**
 * Check custom rules for a site-wide block.
 *
 * Returns true when a "User-agent: *" group contains "Disallow: /",
 * which would de-index the whole site.
 *
 * @param string $robots Robots.txt content.
 * @return bool
 */
function wrte_has_sitewide_block( $robots ) {
	$lines         = preg_split( '/\r\n|\r|\n/', $robots );
	$current_agent = '';

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
			continue;
		}
		if ( preg_match( '/^User-agent:\s*(.+)$/i', $line, $m ) ) {
			$current_agent = trim( $m[1] );
			continue;
		}
		if ( '*' === $current_agent && preg_match( '/^Disallow:\s*\/\s*$/i', $line ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Replace the virtual robots.txt with the custom rules when set.
 *
 * @param string $output Core's generated robots.txt.
 * @return string
 */
function wrte_filter_robots_txt( $output ) {
	$custom = get_option( WRTE_OPTION, '' );
	if ( is_string( $custom ) && '' !== trim( $custom ) ) {
		return $custom;
	}
	return $output;
}
add_filter( 'robots_txt', 'wrte_filter_robots_txt', 10, 1 );

/**
 * Register the Settings page.
 */
function wrte_register_page() {
	add_options_page(
		__( 'Robots.txt', 'wp-robots-txt-editor' ),
		__( 'Robots.txt', 'wp-robots-txt-editor' ),
		'manage_options',
		'wp-robots-txt-editor',
		'wrte_render_page'
	);
}
add_action( 'admin_menu', 'wrte_register_page' );

/**
 * Render the Settings -> Robots.txt page.
 */
function wrte_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'wp-robots-txt-editor' ) );
	}

	$saved_notice = '';
	$block_notice = false;

	if ( isset( $_POST['wrte_save'] ) ) {
		check_admin_referer( 'wrte_save' );

		$robots = isset( $_POST['wrte_robots'] ) ? wp_unslash( $_POST['wrte_robots'] ) : '';
		// Normalise line endings and strip control characters (keep tabs/newlines).
		$robots = str_replace( "\r\n", "\n", $robots );
		$robots = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $robots );

		update_option( WRTE_OPTION, $robots );
		$saved_notice = __( 'Robots.txt saved.', 'wp-robots-txt-editor' );
		$block_notice = wrte_has_sitewide_block( $robots );
	}

	if ( isset( $_POST['wrte_restore'] ) ) {
		check_admin_referer( 'wrte_restore' );
		delete_option( WRTE_OPTION );
		$saved_notice = __( 'Restored the default robots.txt.', 'wp-robots-txt-editor' );
	}

	$current       = wrte_effective_robots();
	$is_custom     = '' !== trim( (string) get_option( WRTE_OPTION, '' ) );
	$physical_file = file_exists( ABSPATH . 'robots.txt' );
	$robots_url    = home_url( 'robots.txt' );

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Robots.txt', 'wp-robots-txt-editor' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: %s: robots.txt URL. */
				esc_html__( 'Edit the robots.txt search engines see at %s. Leave the box empty to use the WordPress default.', 'wp-robots-txt-editor' ),
				'<a href="' . esc_url( $robots_url ) . '" target="_blank" rel="noopener">' . esc_html( $robots_url ) . '</a>'
			);
			?>
		</p>

		<?php if ( $physical_file ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Warning: a physical robots.txt file exists in your site root. The web server serves that file instead of this virtual one — delete it (or edit it directly) for these settings to take effect.', 'wp-robots-txt-editor' ); ?></p></div>
		<?php endif; ?>

		<?php if ( '' !== $saved_notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $saved_notice ); ?></p></div>
		<?php endif; ?>

		<?php if ( $block_notice ) : ?>
			<div class="notice notice-error"><p><strong><?php esc_html_e( 'Warning: these rules block the entire site from search engines (Disallow: / for all user-agents).', 'wp-robots-txt-editor' ); ?></strong> <?php esc_html_e( 'Only save this if you really mean to de-index the site.', 'wp-robots-txt-editor' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'wrte_save' ); ?>
			<?php wp_nonce_field( 'wrte_restore' ); ?>
			<p>
				<label for="wrte_robots"><strong><?php esc_html_e( 'Robots.txt rules', 'wp-robots-txt-editor' ); ?></strong></label><br>
				<textarea id="wrte_robots" name="wrte_robots" rows="16" cols="80" class="large-text code" spellcheck="false"><?php echo esc_textarea( $current ); ?></textarea>
			</p>
			<p class="description">
				<?php esc_html_e( 'Rules: one directive per line. "User-agent: *" applies to all crawlers; "Disallow: /path/" blocks a path; "Sitemap: https://example.com/sitemap.xml" points crawlers at your sitemap.', 'wp-robots-txt-editor' ); ?>
			</p>
			<p>
				<?php submit_button( __( 'Save Robots.txt', 'wp-robots-txt-editor' ), 'primary', 'wrte_save', false ); ?>
				<?php if ( $is_custom ) : ?>
					<span style="margin-left:8px;">
						<button type="submit" name="wrte_restore" value="1" class="button" onclick="return confirm('<?php echo esc_js( __( 'Restore the default robots.txt? Your custom rules will be deleted.', 'wp-robots-txt-editor' ) ); ?>');"><?php esc_html_e( 'Restore default', 'wp-robots-txt-editor' ); ?></button>
					</span>
				<?php endif; ?>
			</p>
		</form>

		<h2><?php esc_html_e( 'Live preview', 'wp-robots-txt-editor' ); ?></h2>
		<p class="description"><?php esc_html_e( 'This is exactly what crawlers receive right now:', 'wp-robots-txt-editor' ); ?></p>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:800px;overflow:auto;"><?php echo esc_html( apply_filters( 'robots_txt', wrte_default_robots() ) ); ?></pre>
		<p class="description">
			<?php
			if ( $is_custom ) {
				esc_html_e( 'Custom rules are active.', 'wp-robots-txt-editor' );
			} else {
				esc_html_e( 'Showing the WordPress default — no custom rules saved yet.', 'wp-robots-txt-editor' );
			}
			?>
		</p>
	</div>
	<?php
}
