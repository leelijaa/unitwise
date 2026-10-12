<?php
/**
 * Post meta registration for unitwise_converter CPT.
 *
 * All fields are REST-exposed so Gutenberg can read/write them.
 * PLUGIN LAYER.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Meta {

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {

		register_post_meta( 'unitwise_converter', 'unitwise_from_unit', [
			'type'              => 'string',
			'description'       => 'Source unit URL slug (e.g. grams).',
			'single'            => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			'show_in_rest'      => true,
		] );

		register_post_meta( 'unitwise_converter', 'unitwise_to_unit', [
			'type'              => 'string',
			'description'       => 'Target unit URL slug (e.g. kilograms).',
			'single'            => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			'show_in_rest'      => true,
		] );

		register_post_meta( 'unitwise_converter', 'unitwise_intro_override', [
			'type'              => 'string',
			'description'       => 'Optional custom intro paragraph (replaces the generated one).',
			'single'            => true,
			'sanitize_callback' => 'wp_kses_post',
			'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			'show_in_rest'      => true,
		] );

		register_post_meta( 'unitwise_converter', 'unitwise_faq_override', [
			'type'          => 'array',
			'description'   => 'Custom FAQ items: array of {q, a} objects.',
			'single'        => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
			'show_in_rest'  => [
				'schema' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'q' => [ 'type' => 'string' ],
							'a' => [ 'type' => 'string' ],
						],
					],
				],
			],
		] );
	}
}
