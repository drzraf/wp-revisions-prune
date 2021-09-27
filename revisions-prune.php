<?php
/**
 * Plugin Name: Revisions CLI
 * Plugin URI: https://github.com/drzraf/wp-revisions-prune
 * Description: WP CLI command filtering/pruning a list of WP posts revisions.
 * Version: 0.0.1
 * Author: Raphaël Droz
 * Author URI: https://github.com/drzraf
 * License: MIT
 * TextDomain:
 * DomainPath:
 * Network:
 *
 * @package drzraf/wp-revisions-prune
 */

if ( ! class_exists( '\WP_CLI' ) ) {
	return;
}

$wpcli_revisions_pruner_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_revisions_pruner_autoloader ) ) {
	require_once $wpcli_revisions_pruner_autoloader;
}

\WP_CLI::add_command( 'revisions prune', [ \WP_CLI\WpRevisionsPrune\RevisionsPruner::class, 'prune' ] );
