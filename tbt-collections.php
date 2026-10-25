<?php
/**
 * Plugin Name:       TBT Collections
 * Description:       Groups lessons into collections (such as Learn from TED) without touching their single category. Each collection has its own archive at /collection/{slug}/.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            TBT
 * License:           GPL-2.0-or-later
 * Text Domain:       tbt-collections
 */

defined( 'ABSPATH' ) || exit;

define( 'TBTCOL_VERSION', '0.1.0' );

/**
 * Register the `tbt_collection` taxonomy on posts.
 *
 * Hierarchical only for the UI: it gives a checkbox box on the edit screen and
 * core bulk edit. Collections are not meant to be nested.
 *
 * Categories are deliberately left alone — Learning History and the lesson
 * cards rely on every lesson having exactly one.
 */
function tbtcol_register_taxonomy() {
	$labels = array(
		'name'                       => __( 'Collections', 'tbt-collections' ),
		'singular_name'              => __( 'Collection', 'tbt-collections' ),
		'menu_name'                  => __( 'Collections', 'tbt-collections' ),
		'all_items'                  => __( 'All collections', 'tbt-collections' ),
		'edit_item'                  => __( 'Edit collection', 'tbt-collections' ),
		'view_item'                  => __( 'View collection', 'tbt-collections' ),
		'update_item'                => __( 'Update collection', 'tbt-collections' ),
		'add_new_item'               => __( 'Add new collection', 'tbt-collections' ),
		'new_item_name'              => __( 'New collection name', 'tbt-collections' ),
		'parent_item'                => __( 'Parent collection', 'tbt-collections' ),
		'parent_item_colon'          => __( 'Parent collection:', 'tbt-collections' ),
		'search_items'               => __( 'Search collections', 'tbt-collections' ),
		'not_found'                  => __( 'No collections found.', 'tbt-collections' ),
		'no_terms'                   => __( 'No collections', 'tbt-collections' ),
		'filter_by_item'             => __( 'Filter by collection', 'tbt-collections' ),
		'items_list_navigation'      => __( 'Collections list navigation', 'tbt-collections' ),
		'items_list'                 => __( 'Collections list', 'tbt-collections' ),
		'back_to_items'              => __( '&larr; Go to collections', 'tbt-collections' ),
		'item_link'                  => __( 'Collection link', 'tbt-collections' ),
		'item_link_description'      => __( 'A link to a collection.', 'tbt-collections' ),
	);

	register_taxonomy(
		'tbt_collection',
		array( 'post' ),
		array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_in_quick_edit' => true,
			'rewrite'            => array(
				'slug'       => 'collection',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'tbtcol_register_taxonomy' );

/**
 * Add an "All collections" dropdown to the Posts list.
 *
 * The select is named after the taxonomy's query var and submits slugs, so
 * core turns `?tbt_collection={slug}` into the tax query on its own. The
 * "All collections" option submits 0, which WP_Query treats as empty and
 * ignores.
 *
 * @param string $post_type The post type of the list being shown.
 */
function tbtcol_admin_filter( $post_type ) {
	if ( 'post' !== $post_type ) {
		return;
	}

	$selected = isset( $_GET['tbt_collection'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		? sanitize_title( wp_unslash( $_GET['tbt_collection'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	wp_dropdown_categories(
		array(
			'taxonomy'        => 'tbt_collection',
			'name'            => 'tbt_collection',
			'value_field'     => 'slug',
			'show_option_all' => __( 'All collections', 'tbt-collections' ),
			'hide_empty'      => false,
			'selected'        => $selected,
		)
	);
}
add_action( 'restrict_manage_posts', 'tbtcol_admin_filter' );

/**
 * Activation: register the taxonomy, seed Learn from TED if it is missing,
 * then flush rewrite rules so /collection/{slug}/ resolves straight away.
 */
function tbtcol_activate() {
	tbtcol_register_taxonomy();

	if ( ! term_exists( 'learn-from-ted', 'tbt_collection' ) ) {
		wp_insert_term(
			'Learn from TED',
			'tbt_collection',
			array( 'slug' => 'learn-from-ted' )
		);
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tbtcol_activate' );

/**
 * Deactivation: flush rewrite rules only. Collections and their lesson
 * assignments are data and are kept — there is deliberately no uninstall.php.
 */
function tbtcol_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tbtcol_deactivate' );
