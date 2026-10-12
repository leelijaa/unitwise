<?php
/**
 * Plugin Name:       UnitWise Converters
 * Plugin URI:        https://unitwise.com
 * Description:       Unit conversion engine, CPT, taxonomy, rewrite rules, schema, and blocks.
 *                    DATA LAYER — survives any theme switch. Do not add presentation logic here.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            UnitWise
 * Author URI:        https://unitwise.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unitwise-converters
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UNITWISE_VERSION', '1.0.0' );
define( 'UNITWISE_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNITWISE_URL', plugin_dir_url( __FILE__ ) );
define( 'UNITWISE_SLUG', 'unitwise-converters' );

$unitwise_includes = [
	'class-registry',
	'class-engine',
	'class-post-type',
	'class-taxonomy',
	'class-rewrites',
	'class-meta',
	'class-assets',
	'class-blocks',
	'class-shortcodes',
	'class-schema',
	'class-seeder',
];

foreach ( $unitwise_includes as $file ) {
	require_once UNITWISE_DIR . 'includes/' . $file . '.php';
}

register_activation_hook( __FILE__, 'unitwise_activate' );
register_deactivation_hook( __FILE__, 'unitwise_deactivate' );

function unitwise_activate(): void {
	Unitwise_Post_Type::register();
	Unitwise_Taxonomy::register();
	Unitwise_Rewrites::register();
	flush_rewrite_rules();
}

function unitwise_deactivate(): void {
	flush_rewrite_rules();
}

add_action( 'plugins_loaded', 'unitwise_boot' );

function unitwise_boot(): void {
	load_plugin_textdomain( 'unitwise-converters', false, UNITWISE_SLUG . '/languages' );

	Unitwise_Post_Type::init();
	Unitwise_Taxonomy::init();
	Unitwise_Rewrites::init();
	Unitwise_Meta::init();
	Unitwise_Assets::init();
	Unitwise_Blocks::init();
	Unitwise_Shortcodes::init();
	Unitwise_Schema::init();
	Unitwise_Seeder::init();
}
