<?php
/**
 * Seeder — creates all converter posts from Section 4 of the build spec.
 *
 * Idempotent: re-running never creates duplicate posts.
 * Access: Tools → UnitWise Seeder admin page, or `wp unitwise seed`.
 *
 * Adding a new converter pair = one row in get_pairs().
 * Adding a new category = one entry in Unitwise_Registry + rows here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Seeder {

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_seed_action' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_blog_seed_action' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_homepage_seed_action' ] );
		add_action( 'admin_init', [ __CLASS__, 'auto_seed_homepage' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'unitwise seed',      [ __CLASS__, 'cli_seed' ] );
			WP_CLI::add_command( 'unitwise seed-blog', [ __CLASS__, 'cli_seed_blog' ] );
		}
	}

	// -------------------------------------------------------------------------
	// Admin page

	public static function add_admin_page(): void {
		add_management_page(
			__( 'UnitWise Seeder', 'unitwise-converters' ),
			__( 'UnitWise Seeder', 'unitwise-converters' ),
			'manage_options',
			'unitwise-seeder',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page(): void {
		$pairs     = self::get_pairs();
		$blog_posts = self::get_blog_posts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UnitWise Seeder', 'unitwise-converters' ); ?></h1>

			<h2><?php esc_html_e( 'Converter Posts', 'unitwise-converters' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: number of converter pairs */
					esc_html__( 'This will create %d converter posts. Running it again is safe — existing posts are skipped.', 'unitwise-converters' ),
					count( $pairs )
				);
				?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'unitwise_seed', 'unitwise_seed_nonce' ); ?>
				<input type="hidden" name="action" value="unitwise_seed">
				<?php submit_button( __( 'Seed Converters', 'unitwise-converters' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Homepage Content', 'unitwise-converters' ); ?></h2>
			<p>
				<?php esc_html_e( 'Populates the static front page with the UnitWise hub blocks (hero, category grid, popular converters, recent posts, newsletter). Also runs automatically whenever the front page has no content.', 'unitwise-converters' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'unitwise_seed_homepage', 'unitwise_seed_homepage_nonce' ); ?>
				<input type="hidden" name="action" value="unitwise_seed_homepage">
				<?php submit_button( __( 'Seed Homepage Blocks', 'unitwise-converters' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Demo Blog Posts', 'unitwise-converters' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: number of blog posts */
					esc_html__( 'This will create %d demo blog posts about unit conversion. Idempotent — skips posts that already exist.', 'unitwise-converters' ),
					count( $blog_posts )
				);
				?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'unitwise_seed_blog', 'unitwise_seed_blog_nonce' ); ?>
				<input type="hidden" name="action" value="unitwise_seed_blog">
				<?php submit_button( __( 'Seed Blog Posts', 'unitwise-converters' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_seed_action(): void {
		if (
			empty( $_POST['action'] ) ||
			'unitwise_seed' !== $_POST['action'] ||
			! isset( $_POST['unitwise_seed_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unitwise_seed_nonce'] ) ), 'unitwise_seed' ) ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		$result = self::seed();
		add_action( 'admin_notices', static function () use ( $result ): void {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: created, 2: skipped */
					esc_html__( 'Seeder complete — %1$d created, %2$d skipped (already existed).', 'unitwise-converters' ),
					(int) $result['created'],
					(int) $result['skipped']
				)
			);
		} );
	}

	public static function handle_blog_seed_action(): void {
		if (
			empty( $_POST['action'] ) ||
			'unitwise_seed_blog' !== $_POST['action'] ||
			! isset( $_POST['unitwise_seed_blog_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unitwise_seed_blog_nonce'] ) ), 'unitwise_seed_blog' ) ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		$result = self::seed_blog();
		add_action( 'admin_notices', static function () use ( $result ): void {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: created, 2: skipped */
					esc_html__( 'Blog seeder complete — %1$d posts created, %2$d skipped.', 'unitwise-converters' ),
					(int) $result['created'],
					(int) $result['skipped']
				)
			);
		} );
	}

	// -------------------------------------------------------------------------
	// Homepage seeder

	public static function handle_homepage_seed_action(): void {
		if (
			empty( $_POST['action'] ) ||
			'unitwise_seed_homepage' !== $_POST['action'] ||
			! isset( $_POST['unitwise_seed_homepage_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unitwise_seed_homepage_nonce'] ) ), 'unitwise_seed_homepage' ) ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		$seeded = self::seed_homepage();
		add_action( 'admin_notices', static function () use ( $seeded ): void {
			$msg = $seeded
				? __( 'Homepage blocks seeded successfully.', 'unitwise-converters' )
				: __( 'Homepage could not be seeded — no static front page found.', 'unitwise-converters' );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $seeded ? 'success' : 'error', esc_html( $msg ) );
		} );
	}

	/**
	 * Auto-seeds the front page with hub blocks when its post_content is empty
	 * AND it has never been seeded before. Once seeded (option set), clearing the
	 * page in Gutenberg no longer triggers a re-seed automatically — use the
	 * "Seed Homepage Blocks" button in Tools → UnitWise Seeder instead.
	 */
	public static function auto_seed_homepage(): void {
		if ( get_option( 'unitwise_homepage_seeded' ) ) {
			return;
		}
		$front_id = (int) get_option( 'page_on_front' );
		if ( ! $front_id ) {
			return;
		}
		$content = get_post_field( 'post_content', $front_id );
		if ( '' === trim( (string) $content ) ) {
			self::seed_homepage();
		}
	}

	/**
	 * Ensures a WP page exists as the static front page, then seeds it with hub blocks.
	 * Creates the page if none is configured. Always targets a page CPT, never a post.
	 *
	 * @return bool True on success.
	 */
	public static function seed_homepage(): bool {
		$front_id = (int) get_option( 'page_on_front' );

		// No static front page configured — create one.
		if ( ! $front_id ) {
			$front_id = (int) wp_insert_post( [
				'post_type'   => 'page',
				'post_title'  => 'Homepage',
				'post_name'   => 'homepage',
				'post_status' => 'publish',
				'post_content' => '',
			] );

			if ( ! $front_id ) {
				return false;
			}

			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front_id );
		}

		// Safety guard: only seed a page CPT, never a post.
		$post = get_post( $front_id );
		if ( ! $post || 'page' !== $post->post_type ) {
			return false;
		}

		$blocks = '<!-- wp:unitwise/hub-hero {"badge":"Free & No Sign-up Required","headline":"Free Online","accentText":"Unit Converters","lead":"Instant, accurate conversions for weight, mass, and more. Beautifully simple, completely free."} /-->'
			. "\n\n"
			. '<!-- wp:unitwise/hub-category-grid {"heading":"Browse by Category","subheading":"Pick a measurement type to see all available converters."} /-->'
			. "\n\n"
			. '<!-- wp:unitwise/popular-converters {"category":"weight","limit":12} /-->'
			. "\n\n"
			. '<!-- wp:unitwise/recent-posts {"heading":"From the Blog","subheading":"Tips, guides, and insights about unit conversion.","count":3} /-->'
			. "\n\n"
			. '<!-- wp:unitwise/hub-newsletter {"heading":"Stay in the Loop","subtext":"Get notified when we add new converters, guides, and tools. No spam, unsubscribe any time."} /-->';

		$result = wp_update_post( [
			'ID'           => $front_id,
			'post_content' => $blocks,
		], true );

		if ( is_wp_error( $result ) || 0 === $result ) {
			return false;
		}

		update_option( 'unitwise_homepage_seeded', '1' );
		return true;
	}

	// -------------------------------------------------------------------------
	// WP-CLI

	public static function cli_seed( array $args, array $assoc_args ): void {
		$result = self::seed();
		WP_CLI::success(
			sprintf(
				'%d converter posts created, %d skipped.',
				$result['created'],
				$result['skipped']
			)
		);
	}

	public static function cli_seed_blog( array $args, array $assoc_args ): void {
		$result = self::seed_blog();
		WP_CLI::success(
			sprintf(
				'%d blog posts created, %d skipped.',
				$result['created'],
				$result['skipped']
			)
		);
	}

	// -------------------------------------------------------------------------
	// Core seed logic

	/**
	 * @return array{ created: int, skipped: int }
	 */
	public static function seed(): array {
		$created = 0;
		$skipped = 0;

		// Ensure category terms exist first.
		self::ensure_categories();

		foreach ( self::get_pairs() as $pair ) {
			$from     = $pair['from'];
			$to       = $pair['to'];
			$category = $pair['category'];
			$slug     = "{$from}-to-{$to}";

			// Idempotency check: look for existing post with the same meta combo.
			$existing = get_posts( [
				'post_type'     => 'unitwise_converter',
				'post_status'   => 'any',
				'numberposts'   => 1,
				'fields'        => 'ids',
				'no_found_rows' => true,
				'meta_query'    => [
					'relation' => 'AND',
					[ 'key' => 'unitwise_from_unit', 'value' => $from ],
					[ 'key' => 'unitwise_to_unit',   'value' => $to   ],
				],
			] );

			if ( ! empty( $existing ) ) {
				++$skipped;
				continue;
			}

			$from_label = Unitwise_Registry::get_label( $from, true );
			$to_label   = Unitwise_Registry::get_label( $to,   true );
			$title      = "{$from_label} to {$to_label} Converter";

			$post_id = wp_insert_post( [
				'post_type'   => 'unitwise_converter',
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_content' => '',
			] );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			// Meta
			update_post_meta( $post_id, 'unitwise_from_unit', $from );
			update_post_meta( $post_id, 'unitwise_to_unit',   $to   );

			// Taxonomy
			$term = get_term_by( 'slug', $category, 'unitwise_category' );
			if ( $term instanceof WP_Term ) {
				wp_set_post_terms( $post_id, [ $term->term_id ], 'unitwise_category' );
			}

			++$created;
		}

		return [ 'created' => $created, 'skipped' => $skipped ];
	}

	// -------------------------------------------------------------------------
	// Categories

	private static function ensure_categories(): void {
		foreach ( Unitwise_Registry::get_categories() as $slug => $cat ) {
			if ( ! term_exists( $slug, 'unitwise_category' ) ) {
				wp_insert_term( $cat['name'], 'unitwise_category', [
					'slug'        => $slug,
					'description' => $cat['description'],
				] );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Pair definitions (Section 4 + added pairs)
	// Each row = one converter page. Add a row; no other code changes needed.

	public static function get_pairs(): array {
		return [
			// --- Grams ↔ Kilograms ---
			[ 'from' => 'grams',      'to' => 'kilograms',  'category' => 'weight' ],
			[ 'from' => 'kilograms',  'to' => 'grams',      'category' => 'weight' ],

			// --- Grams ↔ Milligrams ---
			[ 'from' => 'grams',      'to' => 'milligrams', 'category' => 'weight' ],
			[ 'from' => 'milligrams', 'to' => 'grams',      'category' => 'weight' ],

			// --- Grams → Ounces / Pounds ---
			[ 'from' => 'grams',      'to' => 'ounces',     'category' => 'weight' ],
			[ 'from' => 'grams',      'to' => 'pounds',     'category' => 'weight' ],

			// --- Grams ↔ Micrograms ---
			[ 'from' => 'grams',      'to' => 'micrograms', 'category' => 'weight' ],
			[ 'from' => 'micrograms', 'to' => 'grams',      'category' => 'weight' ],

			// --- Grams ↔ Stones ---
			[ 'from' => 'grams',      'to' => 'stones',     'category' => 'weight' ],
			[ 'from' => 'stones',     'to' => 'grams',      'category' => 'weight' ],

			// --- Grams ↔ Tons ---
			[ 'from' => 'grams',      'to' => 'tons',       'category' => 'weight' ],
			[ 'from' => 'tons',       'to' => 'grams',      'category' => 'weight' ],

			// --- Grams ↔ Carats ---
			[ 'from' => 'grams',      'to' => 'carats',     'category' => 'weight' ],
			[ 'from' => 'carats',     'to' => 'grams',      'category' => 'weight' ],

			// --- Kilograms → Milligrams ---
			[ 'from' => 'kilograms',  'to' => 'milligrams', 'category' => 'weight' ],

			// --- Kilograms ↔ Ounces ---
			[ 'from' => 'kilograms',  'to' => 'ounces',     'category' => 'weight' ],
			[ 'from' => 'ounces',     'to' => 'kilograms',  'category' => 'weight' ],

			// --- Kilograms ↔ Pounds ---
			[ 'from' => 'kilograms',  'to' => 'pounds',     'category' => 'weight' ],
			[ 'from' => 'pounds',     'to' => 'kilograms',  'category' => 'weight' ],

			// --- Kilograms ↔ Tons ---
			[ 'from' => 'kilograms',  'to' => 'tons',       'category' => 'weight' ],
			[ 'from' => 'tons',       'to' => 'kilograms',  'category' => 'weight' ],

			// --- Kilograms ↔ Stones ---
			[ 'from' => 'kilograms',  'to' => 'stones',     'category' => 'weight' ],
			[ 'from' => 'stones',     'to' => 'kilograms',  'category' => 'weight' ],

			// --- Milligrams ↔ Micrograms ---
			[ 'from' => 'milligrams', 'to' => 'micrograms', 'category' => 'weight' ],
			[ 'from' => 'micrograms', 'to' => 'milligrams', 'category' => 'weight' ],

			// --- Milligrams ↔ Ounces ---
			[ 'from' => 'milligrams', 'to' => 'ounces',     'category' => 'weight' ],
			[ 'from' => 'ounces',     'to' => 'milligrams', 'category' => 'weight' ],

			// --- Milligrams ↔ Pounds ---
			[ 'from' => 'milligrams', 'to' => 'pounds',     'category' => 'weight' ],
			[ 'from' => 'pounds',     'to' => 'milligrams', 'category' => 'weight' ],

			// --- Milligrams ↔ Carats ---
			[ 'from' => 'milligrams', 'to' => 'carats',     'category' => 'weight' ],
			[ 'from' => 'carats',     'to' => 'milligrams', 'category' => 'weight' ],

			// --- Ounces ↔ Grams ---
			[ 'from' => 'ounces',     'to' => 'grams',      'category' => 'weight' ],

			// --- Ounces ↔ Pounds ---
			[ 'from' => 'ounces',     'to' => 'pounds',     'category' => 'weight' ],
			[ 'from' => 'pounds',     'to' => 'ounces',     'category' => 'weight' ],

			// --- Ounces ↔ Tons ---
			[ 'from' => 'ounces',     'to' => 'tons',       'category' => 'weight' ],
			[ 'from' => 'tons',       'to' => 'ounces',     'category' => 'weight' ],

			// --- Pounds ↔ Grams ---
			[ 'from' => 'pounds',     'to' => 'grams',      'category' => 'weight' ],

			// --- Pounds ↔ Stones ---
			[ 'from' => 'pounds',     'to' => 'stones',     'category' => 'weight' ],
			[ 'from' => 'stones',     'to' => 'pounds',     'category' => 'weight' ],

			// --- Pounds ↔ Tons ---
			[ 'from' => 'pounds',     'to' => 'tons',       'category' => 'weight' ],
			[ 'from' => 'tons',       'to' => 'pounds',     'category' => 'weight' ],
		];
	}

	// -------------------------------------------------------------------------
	// Blog seeder

	/**
	 * @return array{ created: int, skipped: int }
	 */
	public static function seed_blog(): array {
		$created = 0;
		$skipped = 0;

		foreach ( self::get_blog_posts() as $post_data ) {
			$existing_q = new WP_Query( [
				'post_type'      => 'post',
				'name'           => $post_data['slug'],
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );
			$existing = (bool) $existing_q->have_posts();
			if ( $existing ) {
				++$skipped;
				continue;
			}

			$post_id = wp_insert_post( [
				'post_type'    => 'post',
				'post_title'   => $post_data['title'],
				'post_name'    => $post_data['slug'],
				'post_content' => $post_data['content'],
				'post_excerpt' => $post_data['excerpt'],
				'post_status'  => 'publish',
				'post_date'    => $post_data['date'],
			] );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			if ( ! empty( $post_data['category'] ) ) {
				$cat_id = get_cat_ID( $post_data['category'] );
				if ( ! $cat_id ) {
					$new_cat = wp_create_category( $post_data['category'] );
					$cat_id  = is_wp_error( $new_cat ) ? 0 : $new_cat;
				}
				if ( $cat_id ) {
					wp_set_post_categories( $post_id, [ $cat_id ] );
				}
			}

			++$created;
		}

		return [ 'created' => $created, 'skipped' => $skipped ];
	}

	public static function get_blog_posts(): array {
		return [
			[
				'title'    => 'Grams vs Kilograms: What\'s the Difference and When to Use Each',
				'slug'     => 'grams-vs-kilograms-difference',
				'date'     => '2024-09-01 09:00:00',
				'category' => 'Unit Conversion',
				'excerpt'  => 'Learn the key differences between grams and kilograms, and when each unit is the right choice for cooking, science, and everyday life.',
				'content'  => '<p>When it comes to measuring mass, grams and kilograms are the two most common metric units you will encounter. Understanding the difference between them — and when to use each — can save time and prevent costly mistakes.</p>

<h2>What Is a Gram?</h2>
<p>A gram (g) is the base unit of mass in the metric system. It is equal to one thousandth of a kilogram. Grams are ideal for measuring small quantities such as spices in a recipe, medication dosages, or gemstone weights. A standard paperclip weighs about one gram.</p>

<h2>What Is a Kilogram?</h2>
<p>A kilogram (kg) equals 1,000 grams and is the SI base unit of mass. It is used for everyday objects: body weight, grocery items like flour and sugar, and product packaging. A litre of water weighs almost exactly one kilogram.</p>

<h2>When to Use Grams</h2>
<ul>
<li>Cooking and baking — measuring spices, yeast, or salt</li>
<li>Pharmacy — medication doses are listed in milligrams (mg) or grams</li>
<li>Jewellery — precious stones are weighed in carats (0.2 g each)</li>
<li>Science experiments — lab balances measure in grams</li>
</ul>

<h2>When to Use Kilograms</h2>
<ul>
<li>Body weight and fitness tracking</li>
<li>Grocery shopping — a bag of rice is 1 kg or 5 kg</li>
<li>Shipping and logistics — parcel weight limits</li>
<li>Construction — materials like cement or sand</li>
</ul>

<h2>Quick Conversion Reference</h2>
<p>To convert grams to kilograms, divide by 1,000. To convert kilograms to grams, multiply by 1,000.</p>
<ul>
<li>500 g = 0.5 kg</li>
<li>2 kg = 2,000 g</li>
<li>250 g = 0.25 kg</li>
</ul>

<p>Use our <a href="' . esc_url( home_url( '/weight/grams-to-kilograms/' ) ) . '">Grams to Kilograms converter</a> for instant, accurate results.</p>',
			],
			[
				'title'    => 'How to Convert Pounds to Kilograms: The Complete Guide',
				'slug'     => 'convert-pounds-to-kilograms-guide',
				'date'     => '2024-09-08 09:00:00',
				'category' => 'Unit Conversion',
				'excerpt'  => 'A complete guide to converting pounds to kilograms, including the formula, worked examples, and common conversion values.',
				'content'  => '<p>Whether you are reading a recipe from the United States, checking your weight after travelling, or comparing product specifications, knowing how to convert pounds (lb) to kilograms (kg) is an essential skill.</p>

<h2>The Conversion Formula</h2>
<p>The exact relationship is: <strong>1 pound = 0.45359237 kilograms</strong>. For everyday use, 0.4536 is accurate enough.</p>
<p>Formula: <strong>kg = lb × 0.45359237</strong></p>

<h2>Worked Examples</h2>
<ul>
<li>150 lb = 150 × 0.4536 = <strong>68.04 kg</strong></li>
<li>200 lb = 200 × 0.4536 = <strong>90.72 kg</strong></li>
<li>10 lb = 10 × 0.4536 = <strong>4.536 kg</strong></li>
</ul>

<h2>Common Conversions Table</h2>
<table>
<thead><tr><th>Pounds (lb)</th><th>Kilograms (kg)</th></tr></thead>
<tbody>
<tr><td>1</td><td>0.454</td></tr>
<tr><td>5</td><td>2.268</td></tr>
<tr><td>10</td><td>4.536</td></tr>
<tr><td>50</td><td>22.680</td></tr>
<tr><td>100</td><td>45.359</td></tr>
</tbody>
</table>

<h2>Why the Difference Matters</h2>
<p>The United States, Liberia, and Myanmar are the only countries that primarily use the imperial pound. The rest of the world uses kilograms. This makes conversion essential for international communication, travel, and trade.</p>

<p>For quick conversions, use our <a href="' . esc_url( home_url( '/weight/pounds-to-kilograms/' ) ) . '">Pounds to Kilograms converter</a>.</p>',
			],
			[
				'title'    => 'What Is an Ounce? Understanding Troy, Fluid, and Avoirdupois Ounces',
				'slug'     => 'what-is-an-ounce-types-explained',
				'date'     => '2024-09-15 09:00:00',
				'category' => 'Unit Guides',
				'excerpt'  => 'Not all ounces are equal. Discover the difference between avoirdupois, troy, and fluid ounces, and when each one is used.',
				'content'  => '<p>The word "ounce" can refer to three different units depending on the context. Confusing them leads to errors in cooking, precious metal trading, and chemistry. Here is a clear breakdown.</p>

<h2>Avoirdupois Ounce (oz)</h2>
<p>This is the everyday ounce used in the United States and the UK for food, body weight, and general merchandise. <strong>1 avoirdupois ounce = 28.3495 grams</strong>. There are 16 ounces in one pound.</p>

<h2>Troy Ounce (ozt)</h2>
<p>Used exclusively for precious metals — gold, silver, platinum. <strong>1 troy ounce = 31.1035 grams</strong>, which is heavier than the standard ounce. Gold prices quoted on commodities markets always use troy ounces.</p>

<h2>Fluid Ounce (fl oz)</h2>
<p>A unit of volume, not mass. In the US, <strong>1 fluid ounce = 29.5735 mL</strong>. In the UK it is 28.4131 mL. Fluid ounces measure liquids like milk, juice, and spirits.</p>

<h2>Key Takeaways</h2>
<ul>
<li>When a recipe says "2 oz of butter," it means avoirdupois ounces (weight).</li>
<li>When a price chart says "gold at $2,000/oz," it means troy ounces.</li>
<li>When a drink label says "12 fl oz," it means fluid ounces (volume).</li>
</ul>

<p>Convert between ounces and grams using our <a href="' . esc_url( home_url( '/weight/ounces-to-grams/' ) ) . '">Ounces to Grams converter</a>.</p>',
			],
			[
				'title'    => 'Metric vs Imperial: Which Unit System Should You Use?',
				'slug'     => 'metric-vs-imperial-unit-system',
				'date'     => '2024-09-22 09:00:00',
				'category' => 'Unit Guides',
				'excerpt'  => 'A practical comparison of metric and imperial measurement systems to help you decide which to use for cooking, fitness, science, and international work.',
				'content'  => '<p>Two measurement systems co-exist in our world: the metric system (used by most countries) and the imperial system (used primarily in the United States). Knowing the strengths of each helps you work confidently with either.</p>

<h2>The Metric System</h2>
<p>Also called the International System of Units (SI), the metric system is based on powers of 10. Moving between units is simple: multiply or divide by 10, 100, or 1,000. Key units include grams, kilograms, metres, litres, and degrees Celsius.</p>
<p><strong>Best for:</strong> science, engineering, cooking in Europe, international commerce.</p>

<h2>The Imperial System</h2>
<p>Imperial units (pounds, ounces, feet, miles, Fahrenheit) have historical roots in British and American standards. They are less systematic — 16 ounces in a pound, 12 inches in a foot — but are deeply embedded in everyday life in the US.</p>
<p><strong>Best for:</strong> everyday life in the United States, US recipes, body weight discussions in the UK.</p>

<h2>Which Should You Use?</h2>
<p>Use metric for anything scientific or international. Use imperial when the context demands it — for example, following a US recipe or communicating with American clients. When in doubt, convert.</p>

<h2>Common Conversions Between Systems</h2>
<ul>
<li>1 kg = 2.20462 lb</li>
<li>1 lb = 453.592 g</li>
<li>1 oz = 28.3495 g</li>
<li>1 stone = 6.35029 kg</li>
</ul>',
			],
			[
				'title'    => 'How to Read Nutrition Labels: Understanding Grams and Milligrams',
				'slug'     => 'reading-nutrition-labels-grams-milligrams',
				'date'     => '2024-09-29 09:00:00',
				'category' => 'Practical Guides',
				'excerpt'  => 'Nutrition labels use grams and milligrams for different nutrients. This guide explains what those numbers mean and how to use them.',
				'content'  => '<p>Nutrition facts panels list serving sizes and nutrient amounts in grams (g) and milligrams (mg). Understanding these units helps you make informed food choices.</p>

<h2>Grams on a Nutrition Label</h2>
<p>Macronutrients — carbohydrates, fat, and protein — are listed in grams because they make up a significant portion of food weight. A typical 100 g serving of chicken breast contains about 31 g of protein.</p>

<h2>Milligrams on a Nutrition Label</h2>
<p>Sodium and some vitamins are listed in milligrams (mg) because you need much smaller amounts. The daily recommended sodium intake is 2,300 mg (2.3 g). One teaspoon of table salt contains about 2,300 mg of sodium.</p>

<h2>Micrograms (μg or mcg)</h2>
<p>Vitamins like B12 and D are listed in micrograms — one millionth of a gram. A typical B12 supplement provides 500–1,000 mcg.</p>

<h2>Quick Reference</h2>
<ul>
<li>1 gram (g) = 1,000 milligrams (mg)</li>
<li>1 milligram (mg) = 1,000 micrograms (mcg/μg)</li>
<li>Carbs, fat, protein → grams</li>
<li>Sodium, potassium, calcium → milligrams</li>
<li>Vitamins B12, D, folate → micrograms</li>
</ul>

<p>Use our <a href="' . esc_url( home_url( '/weight/grams-to-milligrams/' ) ) . '">Grams to Milligrams converter</a> to double-check any label value instantly.</p>',
			],
			[
				'title'    => 'Stone to Kilograms: The UK Body Weight Conversion Explained',
				'slug'     => 'stone-to-kilograms-body-weight-uk',
				'date'     => '2024-10-06 09:00:00',
				'category' => 'Unit Conversion',
				'excerpt'  => 'The stone is still widely used in the UK for body weight. Here is how to convert stone to kilograms and why the unit persists.',
				'content'  => '<p>Walk into any UK gym or doctor\'s surgery and you will hear body weight discussed in stones. Despite the metric system being official, stones remain the preferred unit for personal weight in Britain and Ireland.</p>

<h2>What Is a Stone?</h2>
<p>One stone equals <strong>14 pounds</strong> or approximately <strong>6.35029 kilograms</strong>. The unit has been used in Britain since medieval times, originally to weigh commodities like wool and cheese.</p>

<h2>The Conversion Formula</h2>
<p><strong>kg = stones × 6.35029</strong></p>
<p>Example: 11 stone = 11 × 6.35029 = <strong>69.85 kg</strong></p>

<h2>Common Body Weight Conversions</h2>
<table>
<thead><tr><th>Stone</th><th>Kilograms</th><th>Pounds</th></tr></thead>
<tbody>
<tr><td>8</td><td>50.8</td><td>112</td></tr>
<tr><td>9</td><td>57.2</td><td>126</td></tr>
<tr><td>10</td><td>63.5</td><td>140</td></tr>
<tr><td>11</td><td>69.9</td><td>154</td></tr>
<tr><td>12</td><td>76.2</td><td>168</td></tr>
<tr><td>13</td><td>82.6</td><td>182</td></tr>
<tr><td>14</td><td>88.9</td><td>196</td></tr>
</tbody>
</table>

<h2>Why Does the Stone Persist?</h2>
<p>Habit and familiarity keep the stone in everyday British use despite the UK officially adopting SI units in 1965. Doctors sometimes use kilograms for clinical purposes while patients think in stones — so conversion is a regular need.</p>

<p>Convert any stone value to kilograms with our <a href="' . esc_url( home_url( '/weight/stones-to-kilograms/' ) ) . '">Stones to Kilograms converter</a>.</p>',
			],
			[
				'title'    => '5 Common Unit Conversion Mistakes and How to Avoid Them',
				'slug'     => 'common-unit-conversion-mistakes',
				'date'     => '2024-10-13 09:00:00',
				'category' => 'Practical Guides',
				'excerpt'  => 'From mixing up mass and weight to forgetting prefixes, these are the most frequent errors people make when converting units — and how to fix them.',
				'content'  => '<p>Unit conversion errors have caused real-world disasters — the Mars Climate Orbiter was lost in 1999 partly due to a metric/imperial mix-up. At a smaller scale, they ruin recipes, medical doses, and engineering calculations. Here are the five most common mistakes.</p>

<h2>1. Confusing Mass and Weight</h2>
<p>Mass (in kilograms) and weight (in Newtons) are different things. On Earth\'s surface they are proportional, but on the Moon your mass stays the same while your weight drops to one-sixth. In everyday life this rarely matters, but in physics it always does.</p>

<h2>2. Forgetting Prefixes</h2>
<p>Milli- means one thousandth; kilo- means one thousand. Forgetting this is a classic error. 500 milligrams is 0.5 grams, not 500 grams. Always write the full unit symbol (mg, not just m or g) to avoid ambiguity.</p>

<h2>3. Using the Wrong Ounce</h2>
<p>As covered in our ounce guide, avoirdupois and troy ounces differ by almost 10%. Using the wrong one when buying precious metals or following specialist recipes leads to significant errors.</p>

<h2>4. Rounding Too Early</h2>
<p>Rounding intermediate results introduces cumulative error. Carry full precision through the calculation and only round the final answer.</p>

<h2>5. Assuming US and UK Measurements Are Identical</h2>
<p>A US fluid ounce (29.57 mL) is not the same as a UK fluid ounce (28.41 mL). US cups are 240 mL; UK cups are sometimes 250 mL. When following recipes from a different country, check which standard applies.</p>

<h2>How to Avoid All Five</h2>
<p>Use a reliable online converter that shows the full formula and precision — like UnitWise. Always label your units throughout a calculation, and double-check your answer against a rough mental estimate.</p>',
			],
			[
				'title'    => 'A Brief History of the Kilogram: From the Platinum Cylinder to a Fundamental Constant',
				'slug'     => 'history-of-the-kilogram',
				'date'     => '2024-10-20 09:00:00',
				'category' => 'Interesting Facts',
				'excerpt'  => 'The kilogram was once defined by a lump of metal in a Paris vault. Now it is defined by the Planck constant. Here is the fascinating history of the world\'s base unit of mass.',
				'content'  => '<p>The kilogram has one of the most unusual histories of any measurement unit — its definition changed on 20 May 2019 for the first time in 130 years.</p>

<h2>The Original Kilogram (1795)</h2>
<p>France introduced the metric system during the Revolution. The kilogram was defined as the mass of one cubic decimetre of water at 4°C — the temperature at which water is densest.</p>

<h2>The Platinum Cylinder (1889)</h2>
<p>In 1889 the International Bureau of Weights and Measures (BIPM) created the International Prototype of the Kilogram (IPK): a cylinder of 90% platinum and 10% iridium, stored in a vault near Paris. Every kilogram measurement in the world was traceable to this single object. Copies (called national prototypes) were distributed to member countries.</p>

<h2>The Problem: It Was Drifting</h2>
<p>When scientists compared the IPK to its copies in 1989, they found discrepancies of up to 50 micrograms. Even a perfect platinum alloy changes mass slightly over time through surface contamination. A kilogram defined by a physical object is fundamentally unstable.</p>

<h2>The Planck Constant Definition (2019)</h2>
<p>Since 20 May 2019, the kilogram has been defined by fixing the numerical value of the Planck constant (h) to exactly 6.62607015 × 10⁻³⁴ kg·m²·s⁻¹. This ties the kilogram to a true constant of nature — meaning it will never drift and can be reproduced anywhere in the universe with the right equipment.</p>

<h2>What This Means for You</h2>
<p>For everyday use, nothing changed. A kilogram of apples is still a kilogram of apples. But for precision science and industry, the new definition provides a rock-solid foundation that the platinum cylinder could not.</p>',
			],
			[
				'title'    => 'Weight vs Mass: The Difference Every Student Needs to Know',
				'slug'     => 'weight-vs-mass-difference-explained',
				'date'     => '2024-10-27 09:00:00',
				'category' => 'Unit Guides',
				'excerpt'  => 'Weight and mass are often used interchangeably in everyday speech, but in science they are very different. Here is a clear explanation with examples.',
				'content'  => '<p>One of the most common confusions in physics is treating "weight" and "mass" as synonyms. In casual speech that is fine; in science, engineering, or space travel, the difference is critical.</p>

<h2>What Is Mass?</h2>
<p>Mass is the amount of matter in an object. It is measured in kilograms (kg) and does not change based on location. Your mass on Earth is the same as your mass on the Moon or in deep space.</p>

<h2>What Is Weight?</h2>
<p>Weight is the force gravity exerts on a mass. It is measured in Newtons (N) using Newton\'s second law: <strong>W = m × g</strong>, where g is gravitational acceleration (9.81 m/s² on Earth\'s surface).</p>

<blockquote>A person with a mass of 70 kg has a weight of 70 × 9.81 = 686.7 N on Earth, but only 70 × 1.62 = 113.4 N on the Moon.</blockquote>

<h2>Why the Confusion Exists</h2>
<p>In everyday language, "weight" is used to mean mass because we only ever experience one gravitational field (Earth). Bathroom scales are calibrated to show kilograms, even though they technically measure force — because g is constant for all practical purposes on Earth\'s surface.</p>

<h2>When the Distinction Matters</h2>
<ul>
<li>Space exploration — calculating fuel for rockets requires true mass, not Earth weight</li>
<li>Fluid mechanics — buoyancy depends on mass and density</li>
<li>Physics exams — using weight where mass is required (or vice versa) loses marks</li>
</ul>

<h2>Summary</h2>
<ul>
<li><strong>Mass</strong>: measured in kg, constant everywhere</li>
<li><strong>Weight</strong>: measured in Newtons, varies with gravity</li>
<li>A kitchen scale measures mass, not weight in the scientific sense</li>
</ul>',
			],
			[
				'title'    => 'Carat Weight for Diamonds and Gemstones: Everything You Need to Know',
				'slug'     => 'carat-weight-diamonds-gemstones-guide',
				'date'     => '2024-11-03 09:00:00',
				'category' => 'Interesting Facts',
				'excerpt'  => 'A carat is exactly 0.2 grams — but understanding how diamond carats affect value, size, and price requires a deeper look.',
				'content'  => '<p>When shopping for diamonds or gemstones, you will encounter carat weight constantly. But carat is a precise unit with a specific meaning — and carat weight alone does not determine a stone\'s size or value.</p>

<h2>What Is a Carat?</h2>
<p>One metric carat (ct) = <strong>exactly 0.2 grams</strong> = 200 milligrams. This has been the international standard since 1907. The word "carat" comes from the carob seed, which traders once used as counterweights because of their remarkably consistent mass.</p>

<h2>How Carat Relates to Size</h2>
<p>Carat measures mass, not diameter. A 1-carat round diamond is typically about 6.5 mm in diameter, but this varies with cut depth. Two diamonds of identical carat weight can appear different sizes depending on their proportions.</p>

<h2>Common Carat Weights and Gram Equivalents</h2>
<table>
<thead><tr><th>Carats (ct)</th><th>Grams (g)</th><th>Milligrams (mg)</th></tr></thead>
<tbody>
<tr><td>0.25</td><td>0.05</td><td>50</td></tr>
<tr><td>0.5</td><td>0.1</td><td>100</td></tr>
<tr><td>1.0</td><td>0.2</td><td>200</td></tr>
<tr><td>2.0</td><td>0.4</td><td>400</td></tr>
<tr><td>5.0</td><td>1.0</td><td>1,000</td></tr>
</tbody>
</table>

<h2>Carat vs Karat</h2>
<p>These two words sound identical but mean different things. <strong>Carat</strong> is a unit of mass for gemstones. <strong>Karat</strong> (abbreviated K or kt) is a measure of gold purity: 24K gold is pure gold; 18K is 75% gold.</p>

<h2>Price and Carat Weight</h2>
<p>Diamond prices scale non-linearly with carat weight. A 2-carat diamond is not twice the price of a 1-carat diamond — it is typically 4× more expensive, because large gem-quality stones are exponentially rarer.</p>

<p>Use our <a href="' . esc_url( home_url( '/weight/carats-to-grams/' ) ) . '">Carats to Grams converter</a> for accurate carat conversions.</p>',
			],
		];
	}
}
