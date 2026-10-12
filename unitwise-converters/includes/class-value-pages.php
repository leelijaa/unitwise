<?php
/**
 * Value Pages — programmatic page generator and template router.
 *
 * Mirrors the Date-Time-Calculator-Theme pattern exactly:
 * - Native WP `page` posts flagged with _uw_value_page = '1' meta
 * - Admin UI: keyword textarea → save as drafts → publish table
 * - template_include filter routes to single-unitwise_value_page.php in theme
 * - SEO tags and JSON-LD output via wp_head
 *
 * URL: /{value}-{from-label}-to-{to-plural}/   (root-level, no /weight/ prefix)
 * e.g. /1-gram-to-milligrams/  /100-grams-to-kilograms/
 *
 * PLUGIN LAYER.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Value_Pages {

	const META_FLAG = '_uw_value_page';
	const META_VAL  = '_uw_vp_value';
	const META_FROM = '_uw_vp_from';
	const META_TO   = '_uw_vp_to';
	const META_CAT  = '_uw_vp_cat';
	const CACHE_KEY = 'uw_value_pages_data';

	/**
	 * Label / abbreviation → canonical plural slug.
	 * Accepts both singular and plural forms plus common symbols.
	 */
	private static array $label_map = [
		'gram'      => 'grams',      'grams'      => 'grams',
		'g'         => 'grams',
		'kilogram'  => 'kilograms',  'kilograms'  => 'kilograms',  'kg'  => 'kilograms',
		'milligram' => 'milligrams', 'milligrams' => 'milligrams', 'mg'  => 'milligrams',
		'microgram' => 'micrograms', 'micrograms' => 'micrograms', 'mcg' => 'micrograms',
		'ounce'     => 'ounces',     'ounces'     => 'ounces',     'oz'  => 'ounces',
		'pound'     => 'pounds',     'pounds'     => 'pounds',     'lb'  => 'pounds',  'lbs' => 'pounds',
		'stone'     => 'stones',     'stones'     => 'stones',     'st'  => 'stones',
		'ton'       => 'tons',       'tons'       => 'tons',       'tonne' => 'tons',  'tonnes' => 'tons',
		'carat'     => 'carats',     'carats'     => 'carats',     'ct'  => 'carats',
	];

	/** Singular URL form for each plural slug (used in slug when value = 1). */
	private static array $singular_url = [
		'grams'      => 'gram',      'kilograms'  => 'kilogram',
		'milligrams' => 'milligram', 'micrograms' => 'microgram',
		'ounces'     => 'ounce',     'pounds'     => 'pound',
		'stones'     => 'stone',     'tons'       => 'ton',
		'carats'     => 'carat',
	];

	// -------------------------------------------------------------------------
	// Bootstrap

	public static function init(): void {
		add_action( 'admin_menu',                   [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_post_uw_vp_generate',    [ __CLASS__, 'handle_generate' ] );
		add_action( 'admin_post_uw_vp_publish_all', [ __CLASS__, 'handle_publish_all' ] );
		add_action( 'admin_post_uw_vp_publish_one', [ __CLASS__, 'handle_publish_one' ] );
		add_action( 'admin_post_uw_vp_delete',      [ __CLASS__, 'handle_delete' ] );
		add_filter( 'template_include',             [ __CLASS__, 'route_template' ] );
		add_action( 'wp_enqueue_scripts',           [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_head',                      [ __CLASS__, 'output_seo' ], 1 );
		add_action( 'save_post_page',               [ __CLASS__, 'bust_cache' ] );
		add_action( 'trashed_post',                 [ __CLASS__, 'bust_cache' ] );
		add_action( 'deleted_post',                 [ __CLASS__, 'bust_cache' ] );
	}

	// -------------------------------------------------------------------------
	// Admin menu

	public static function register_menu(): void {
		add_menu_page(
			__( 'UnitWise Pages', 'unitwise-converters' ),
			__( 'UnitWise Pages', 'unitwise-converters' ),
			'manage_options',
			'uw-value-pages',
			[ __CLASS__, 'render_admin_page' ],
			'dashicons-list-view',
			26
		);
	}

	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$drafts       = self::get_pages_by_status( 'draft' );
		$published    = self::get_pages_by_status( 'publish' );
		$all_keywords = self::get_standard_keywords();
		$admin_url    = esc_url( admin_url( 'admin-post.php' ) );

		// Flash messages from redirect params
		$notice = '';
		if ( ! empty( $_GET['uw_created'] ) ) {
			$n      = (int) $_GET['uw_created'];
			$notice = "<div class='notice notice-success is-dismissible'><p>" . sprintf( esc_html__( 'Created %d draft page(s).', 'unitwise-converters' ), $n ) . '</p></div>';
		} elseif ( ! empty( $_GET['uw_published'] ) ) {
			$n      = (int) $_GET['uw_published'];
			$notice = "<div class='notice notice-success is-dismissible'><p>" . sprintf( esc_html__( 'Published %d page(s).', 'unitwise-converters' ), $n ) . '</p></div>';
		} elseif ( ! empty( $_GET['uw_deleted'] ) ) {
			$notice = "<div class='notice notice-success is-dismissible'><p>" . esc_html__( 'Page moved to trash.', 'unitwise-converters' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UnitWise Value Pages', 'unitwise-converters' ); ?></h1>
			<p style="color:#666;max-width:680px;">
				<?php esc_html_e( 'Generate SEO pages like "/1-gram-to-milligrams/" that directly answer specific conversion searches. Pages are stored as WordPress drafts until you publish them.', 'unitwise-converters' ); ?>
			</p>
			<?php echo wp_kses_post( $notice ); ?>

			<!-- SECTION 1: Generate -->
			<div class="postbox" style="margin-top:20px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Generate Pages', 'unitwise-converters' ); ?></h2>
				</div>
				<div class="inside">
					<p style="color:#666;"><?php esc_html_e( 'One keyword per line — e.g. "1 gram to milligrams", "100 grams to kilograms", "1 kg to pounds".', 'unitwise-converters' ); ?></p>
					<form method="post" action="<?php echo $admin_url; ?>">
						<?php wp_nonce_field( 'uw_vp_generate', 'uw_nonce' ); ?>
						<input type="hidden" name="action" value="uw_vp_generate">
						<textarea name="uw_keywords" id="uw-keywords" rows="12"
							style="width:100%;font-family:monospace;font-size:13px;"
							placeholder="1 gram to milligrams&#10;100 grams to kilograms&#10;1 pound to grams&#10;5 kg to pounds"></textarea>
						<p style="margin-top:8px;">
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Save as Drafts', 'unitwise-converters' ); ?>
							</button>
							&nbsp;
							<button type="button" id="uw-gen-standard" class="button button-secondary">
								<?php printf( esc_html__( 'Fill Standard Set (%d pages)', 'unitwise-converters' ), count( $all_keywords ) ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>

			<!-- SECTION 2: Drafts -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle">
						<?php printf( esc_html__( 'Drafts (%d)', 'unitwise-converters' ), count( $drafts ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<?php if ( $drafts ) : ?>
						<form method="post" action="<?php echo $admin_url; ?>">
							<?php wp_nonce_field( 'uw_vp_publish_all', 'uw_nonce' ); ?>
							<input type="hidden" name="action" value="uw_vp_publish_all">
							<p>
								<button type="submit" class="button button-primary">
									<?php printf( esc_html__( 'Publish All %d Drafts', 'unitwise-converters' ), count( $drafts ) ); ?>
								</button>
							</p>
						</form>
						<table class="wp-list-table widefat fixed striped" style="margin-top:8px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Slug', 'unitwise-converters' ); ?></th>
									<th><?php esc_html_e( 'Title', 'unitwise-converters' ); ?></th>
									<th style="width:170px;"><?php esc_html_e( 'Actions', 'unitwise-converters' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $drafts as $p ) : ?>
								<tr>
									<td><code style="font-size:12px;"><?php echo esc_html( $p->post_name ); ?></code></td>
									<td><?php echo esc_html( $p->post_title ); ?></td>
									<td>
										<form method="post" action="<?php echo $admin_url; ?>" style="display:inline;">
											<?php wp_nonce_field( 'uw_vp_publish_one', 'uw_nonce' ); ?>
											<input type="hidden" name="action" value="uw_vp_publish_one">
											<input type="hidden" name="post_id" value="<?php echo esc_attr( $p->ID ); ?>">
											<button type="submit" class="button button-small button-primary">
												<?php esc_html_e( 'Publish', 'unitwise-converters' ); ?>
											</button>
										</form>
										&nbsp;
										<form method="post" action="<?php echo $admin_url; ?>" style="display:inline;"
											onsubmit="return confirm('<?php echo esc_js( __( 'Delete this draft?', 'unitwise-converters' ) ); ?>')">
											<?php wp_nonce_field( 'uw_vp_delete', 'uw_nonce' ); ?>
											<input type="hidden" name="action" value="uw_vp_delete">
											<input type="hidden" name="post_id" value="<?php echo esc_attr( $p->ID ); ?>">
											<button type="submit" class="button button-small" style="color:#b32d2e;">
												<?php esc_html_e( 'Delete', 'unitwise-converters' ); ?>
											</button>
										</form>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="color:#888;"><?php esc_html_e( 'No drafts. Paste keywords above and click "Save as Drafts".', 'unitwise-converters' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- SECTION 3: Published -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle">
						<?php printf( esc_html__( 'Published (%d)', 'unitwise-converters' ), count( $published ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<?php if ( $published ) : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'URL', 'unitwise-converters' ); ?></th>
									<th><?php esc_html_e( 'Title', 'unitwise-converters' ); ?></th>
									<th style="width:120px;"><?php esc_html_e( 'Actions', 'unitwise-converters' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $published as $p ) :
									$url = get_permalink( $p->ID );
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( $url ); ?>" target="_blank" style="font-family:monospace;font-size:12px;">
											/<?php echo esc_html( $p->post_name ); ?>/
										</a>
									</td>
									<td><?php echo esc_html( $p->post_title ); ?></td>
									<td>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'unitwise-converters' ); ?>
										</a>
										&nbsp;
										<form method="post" action="<?php echo $admin_url; ?>" style="display:inline;"
											onsubmit="return confirm('<?php echo esc_js( __( 'Move this page to trash?', 'unitwise-converters' ) ); ?>')">
											<?php wp_nonce_field( 'uw_vp_delete', 'uw_nonce' ); ?>
											<input type="hidden" name="action" value="uw_vp_delete">
											<input type="hidden" name="post_id" value="<?php echo esc_attr( $p->ID ); ?>">
											<button type="submit" class="button button-small" style="color:#b32d2e;">
												<?php esc_html_e( 'Trash', 'unitwise-converters' ); ?>
											</button>
										</form>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="color:#888;"><?php esc_html_e( 'No published value pages yet.', 'unitwise-converters' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<script>
		document.getElementById('uw-gen-standard').addEventListener('click', function() {
			var keywords = <?php echo wp_json_encode( $all_keywords ); ?>;
			document.getElementById('uw-keywords').value = keywords.join('\n');
			document.getElementById('uw-keywords').focus();
		});
		</script>
		<?php
	}

	// -------------------------------------------------------------------------
	// Form handlers

	public static function handle_generate(): void {
		check_admin_referer( 'uw_vp_generate', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$raw   = sanitize_textarea_field( wp_unslash( $_POST['uw_keywords'] ?? '' ) );
		$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
		$count = 0;

		foreach ( $lines as $line ) {
			$parsed = self::parse_keyword( $line );
			if ( ! $parsed ) {
				continue;
			}

			$slug = self::build_slug( $parsed['value'], $parsed['from'], $parsed['to'] );

			// Idempotency — skip if a page with this slug already exists
			if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
				continue;
			}

			$id = wp_insert_post( [
				'post_title'   => self::build_title( $parsed['value'], $parsed['from'], $parsed['to'] ),
				'post_name'    => $slug,
				'post_status'  => 'draft',
				'post_type'    => 'page',
				'post_content' => '',
			] );

			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}

			update_post_meta( $id, self::META_FLAG, '1' );
			update_post_meta( $id, self::META_VAL,  (string) $parsed['value'] );
			update_post_meta( $id, self::META_FROM, $parsed['from'] );
			update_post_meta( $id, self::META_TO,   $parsed['to'] );
			update_post_meta( $id, self::META_CAT,  $parsed['cat'] );
			$count++;
		}

		self::bust_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_created=' . $count ) );
		exit;
	}

	public static function handle_publish_all(): void {
		check_admin_referer( 'uw_vp_publish_all', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$drafts = self::get_pages_by_status( 'draft' );
		$count  = 0;
		foreach ( $drafts as $p ) {
			wp_publish_post( $p->ID );
			$count++;
		}

		self::bust_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_published=' . $count ) );
		exit;
	}

	public static function handle_publish_one(): void {
		check_admin_referer( 'uw_vp_publish_one', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$id = (int) ( $_POST['post_id'] ?? 0 );
		if ( $id && '1' === get_post_meta( $id, self::META_FLAG, true ) ) {
			wp_publish_post( $id );
			self::bust_cache();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_published=1' ) );
		exit;
	}

	public static function handle_delete(): void {
		check_admin_referer( 'uw_vp_delete', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$id = (int) ( $_POST['post_id'] ?? 0 );
		if ( $id && '1' === get_post_meta( $id, self::META_FLAG, true ) ) {
			wp_trash_post( $id );
			self::bust_cache();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_deleted=1' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Template routing + assets

	/**
	 * Swap the template for value pages to the theme's dedicated file.
	 * Plugin only points; theme renders. Maintains plugin/theme separation.
	 */
	public static function route_template( string $template ): string {
		if ( ! is_singular( 'page' ) ) {
			return $template;
		}
		$post = get_queried_object();
		if ( ! $post || '1' !== get_post_meta( $post->ID, self::META_FLAG, true ) ) {
			return $template;
		}
		$t = locate_template( 'single-unitwise_value_page.php' );
		return $t ?: $template;
	}

	/**
	 * Enqueue converter CSS + JS on value pages.
	 * Localizes unitwiseData so the inline converter widget can compute results.
	 */
	public static function enqueue_assets(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}
		$id = get_the_ID();
		if ( ! $id || '1' !== get_post_meta( $id, self::META_FLAG, true ) ) {
			return;
		}

		wp_enqueue_style(
			'unitwise-converter',
			UNITWISE_URL . 'assets/css/converter.css',
			[],
			UNITWISE_VERSION
		);

		$cat = sanitize_key( get_post_meta( $id, self::META_CAT, true ) ?: 'weight' );
		wp_localize_script(
			'unitwise-theme',
			'unitwiseData',
			[
				'factors'  => Unitwise_Registry::get_factors( $cat ),
				'decimals' => 6,
			]
		);
	}

	// -------------------------------------------------------------------------
	// SEO meta tags

	/**
	 * Output title/description/OG/Twitter tags for value pages.
	 * Skips if Rank Math or Yoast is active (they handle it).
	 */
	public static function output_seo(): void {
		if ( defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) ) {
			return;
		}
		if ( ! is_singular( 'page' ) ) {
			return;
		}
		$id = get_the_ID();
		if ( ! $id || '1' !== get_post_meta( $id, self::META_FLAG, true ) ) {
			return;
		}

		$value = (float) get_post_meta( $id, self::META_VAL, true );
		$from  = sanitize_key( get_post_meta( $id, self::META_FROM, true ) );
		$to    = sanitize_key( get_post_meta( $id, self::META_TO, true ) );
		$cat   = sanitize_key( get_post_meta( $id, self::META_CAT, true ) ?: 'weight' );

		if ( ! $from || !$to || ! class_exists( 'Unitwise_Engine' ) ) {
			return;
		}

		$engine  = new Unitwise_Engine( $cat );
		$result  = $engine->format_result( $engine->convert( $value, $from, $to ) );
		$plural  = ( 1.0 !== $value );
		$from_l  = Unitwise_Registry::get_label( $from, $plural );
		$to_l    = Unitwise_Registry::get_label( $to, true );
		$site    = get_bloginfo( 'name' );
		$v_str   = ( floor( $value ) == $value ) ? (int) $value : $value;

		$title = esc_html( "{$v_str} {$from_l} to {$to_l} | {$site}" );
		$desc  = esc_attr( "{$v_str} {$from_l} equals {$result} {$to_l}. Convert {$from_l} to {$to_l} with our free online unit converter." );
		$url   = esc_url( (string) get_permalink( $id ) );
		$site  = esc_attr( $site );

		echo "<meta name=\"description\" content=\"{$desc}\">\n";
		echo "<link rel=\"canonical\" href=\"{$url}\">\n";
		echo "<meta property=\"og:title\" content=\"{$title}\">\n";
		echo "<meta property=\"og:description\" content=\"{$desc}\">\n";
		echo "<meta property=\"og:url\" content=\"{$url}\">\n";
		echo "<meta property=\"og:type\" content=\"article\">\n";
		echo "<meta property=\"og:site_name\" content=\"{$site}\">\n";
		echo "<meta name=\"twitter:card\" content=\"summary\">\n";
		echo "<meta name=\"twitter:title\" content=\"{$title}\">\n";
		echo "<meta name=\"twitter:description\" content=\"{$desc}\">\n";
	}

	// -------------------------------------------------------------------------
	// Keyword parser

	/**
	 * Parse a keyword line like "1 gram to milligrams" or "100 grams to kg".
	 *
	 * @return array{value:float,from:string,to:string,cat:string}|null
	 */
	public static function parse_keyword( string $line ): ?array {
		$line = strtolower( trim( $line ) );
		$line = preg_replace( '/\s+/', ' ', $line );

		// Pattern: {number} {from} to {to}
		if ( ! preg_match( '/^([0-9]+(?:\.[0-9]+)?)\s+([a-z]+)\s+to\s+([a-z]+)$/', $line, $m ) ) {
			return null;
		}

		$value    = (float) $m[1];
		$from_raw = $m[2];
		$to_raw   = $m[3];

		$from_slug = self::$label_map[ $from_raw ] ?? null;
		$to_slug   = self::$label_map[ $to_raw ]   ?? null;

		if ( ! $from_slug || ! $to_slug || $from_slug === $to_slug ) {
			return null;
		}
		if ( ! class_exists( 'Unitwise_Registry' ) ) {
			return null;
		}
		if ( ! Unitwise_Registry::unit_exists( $from_slug ) || ! Unitwise_Registry::unit_exists( $to_slug ) ) {
			return null;
		}

		return [ 'value' => $value, 'from' => $from_slug, 'to' => $to_slug, 'cat' => 'weight' ];
	}

	// -------------------------------------------------------------------------
	// Slug and title builders

	/**
	 * Build the URL slug. Mirrors dtcalc_page_slug().
	 * Singular form when value = 1 (e.g. "gram"), plural otherwise (e.g. "grams").
	 *
	 * e.g. build_slug(1, 'grams', 'milligrams') → "1-gram-to-milligrams"
	 *      build_slug(100, 'grams', 'kilograms') → "100-grams-to-kilograms"
	 */
	public static function build_slug( float $value, string $from_slug, string $to_slug ): string {
		$f = ( 1.0 === $value && isset( self::$singular_url[ $from_slug ] ) )
			? self::$singular_url[ $from_slug ]
			: $from_slug;
		$v = ( floor( $value ) == $value ) ? (int) $value : $value;
		return $v . '-' . $f . '-to-' . $to_slug;
	}

	/**
	 * Build the page title.
	 * e.g. "1 Gram to Milligrams", "100 Grams to Kilograms"
	 */
	public static function build_title( float $value, string $from_slug, string $to_slug ): string {
		$plural = ( 1.0 !== $value );
		$from_l = Unitwise_Registry::get_label( $from_slug, $plural );
		$to_l   = Unitwise_Registry::get_label( $to_slug, true );
		$v      = ( floor( $value ) == $value ) ? (int) $value : $value;
		return "{$v} {$from_l} to {$to_l}";
	}

	// -------------------------------------------------------------------------
	// Standard keyword set

	/**
	 * Returns all standard-set keywords for bulk generation.
	 * Uses Unitwise_Engine::default_table_values() per unit for scale-appropriate values.
	 * Roughly 460 keywords covering all 46 converter pairs × 10 values each.
	 */
	public static function get_standard_keywords(): array {
		if ( ! class_exists( 'Unitwise_Engine' ) || ! class_exists( 'Unitwise_Registry' ) ) {
			return [];
		}

		$pairs = [
			[ 'grams', 'kilograms' ],  [ 'kilograms', 'grams' ],
			[ 'grams', 'milligrams' ], [ 'milligrams', 'grams' ],
			[ 'grams', 'ounces' ],     [ 'grams', 'pounds' ],
			[ 'grams', 'micrograms' ], [ 'micrograms', 'grams' ],
			[ 'grams', 'stones' ],     [ 'stones', 'grams' ],
			[ 'grams', 'tons' ],       [ 'tons', 'grams' ],
			[ 'grams', 'carats' ],     [ 'carats', 'grams' ],
			[ 'kilograms', 'milligrams' ],
			[ 'kilograms', 'ounces' ],     [ 'ounces', 'kilograms' ],
			[ 'kilograms', 'pounds' ],     [ 'pounds', 'kilograms' ],
			[ 'kilograms', 'tons' ],       [ 'tons', 'kilograms' ],
			[ 'kilograms', 'stones' ],     [ 'stones', 'kilograms' ],
			[ 'milligrams', 'micrograms' ], [ 'micrograms', 'milligrams' ],
			[ 'milligrams', 'ounces' ],    [ 'ounces', 'milligrams' ],
			[ 'milligrams', 'pounds' ],    [ 'pounds', 'milligrams' ],
			[ 'milligrams', 'carats' ],    [ 'carats', 'milligrams' ],
			[ 'ounces', 'grams' ],
			[ 'ounces', 'pounds' ],        [ 'pounds', 'ounces' ],
			[ 'ounces', 'tons' ],          [ 'tons', 'ounces' ],
			[ 'pounds', 'grams' ],
			[ 'pounds', 'stones' ],        [ 'stones', 'pounds' ],
			[ 'pounds', 'tons' ],          [ 'tons', 'pounds' ],
		];

		$keywords = [];
		foreach ( $pairs as [ $from, $to ] ) {
			$values = Unitwise_Engine::default_table_values( $from );
			foreach ( $values as $v ) {
				$plural   = ( 1 != $v );
				$from_lbl = strtolower( Unitwise_Registry::get_label( $from, $plural ) );
				$keywords[] = $v . ' ' . $from_lbl . ' to ' . $to;
			}
		}
		return $keywords;
	}

	// -------------------------------------------------------------------------
	// Data helpers

	/**
	 * Get all UnitWise value pages by post status.
	 *
	 * @return WP_Post[]
	 */
	public static function get_pages_by_status( string $status ): array {
		return get_posts( [
			'post_type'      => 'page',
			'post_status'    => $status,
			'numberposts'    => -1,
			'orderby'        => 'post_name',
			'order'          => 'ASC',
			'meta_key'       => self::META_FLAG,
			'meta_value'     => '1',
			'no_found_rows'  => true,
		] );
	}

	/**
	 * Returns all published value pages as a transient-cached data array.
	 * Mirrors dtcalc_all_pages_data().
	 *
	 * @return array<int,array{slug:string,value:float,from:string,to:string,url:string,title:string}>
	 */
	public static function all_pages_data(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$posts = self::get_pages_by_status( 'publish' );
		$data  = [];
		foreach ( $posts as $p ) {
			$data[] = [
				'slug'  => $p->post_name,
				'value' => (float) get_post_meta( $p->ID, self::META_VAL, true ),
				'from'  => get_post_meta( $p->ID, self::META_FROM, true ),
				'to'    => get_post_meta( $p->ID, self::META_TO, true ),
				'url'   => get_permalink( $p->ID ),
				'title' => $p->post_title,
			];
		}

		set_transient( self::CACHE_KEY, $data, DAY_IN_SECONDS );
		return $data;
	}

	/**
	 * Look up the permalink for a specific value page, if it exists and is published.
	 * Used by the template to link rows in the nearby-values table.
	 */
	public static function value_page_url( float $value, string $from_slug, string $to_slug ): ?string {
		$slug = self::build_slug( $value, $from_slug, $to_slug );
		$p    = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $p || 'publish' !== $p->post_status ) {
			return null;
		}
		if ( '1' !== get_post_meta( $p->ID, self::META_FLAG, true ) ) {
			return null;
		}
		return (string) get_permalink( $p->ID );
	}

	public static function bust_cache(): void {
		delete_transient( self::CACHE_KEY );
	}
}
