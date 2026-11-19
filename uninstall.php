<?php
/**
 * Uninstall WP Robots.txt Editor.
 *
 * Removes the saved custom robots.txt rules.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wrte_custom_robots' );
