<?php
/**
 * Value Pages — programmatic page generator, template router, and section manager.
 *
 * Admin UI has four sections:
 *  1. Generate  — keyword textarea → save as drafts
 *  2. Drafts    — table of draft pages (publish / delete per row, publish-all button)
 *  3. Published — table of live pages (edit / trash per row)
 *  4. Template Sections — add/edit/toggle custom content blocks that appear on
 *                          every value page at a configurable position
 *
 * URL pattern: /{value}-{from-label}-to-{to-plural}/  (root-level, no /weight/ prefix)
 * e.g.  /1-gram-to-milligrams/  /100-grams-to-kilograms/
 *
 * PLUGIN LAYER.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unitwise_Value_Pages {

	const META_FLAG       = '_uw_value_page';
	const META_VAL        = '_uw_vp_value';
	const META_FROM       = '_uw_vp_from';
	const META_TO         = '_uw_vp_to';
	const META_CAT        = '_uw_vp_cat';
	const CACHE_KEY       = 'uw_value_pages_data';
	const SECTIONS_OPTION = 'uw_vp_sections';

	/** Insertion positions available inside the value-page template. */
	private static array $positions = [
		'after_hero'      => 'After Answer Block',
		'after_converter' => 'After Converter Widget',
		'after_formula'   => 'After Formula',
		'after_table'     => 'After Conversion Table',
		'after_related'   => 'After Related Converters',
		'before_faq'      => 'Before FAQ',
	];

	/**
	 * Label / abbreviation → canonical plural slug.
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
		add_action( 'admin_menu',                      [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_post_uw_vp_generate',       [ __CLASS__, 'handle_generate' ] );
		add_action( 'admin_post_uw_vp_publish_all',    [ __CLASS__, 'handle_publish_all' ] );
		add_action( 'admin_post_uw_vp_publish_one',    [ __CLASS__, 'handle_publish_one' ] );
		add_action( 'admin_post_uw_vp_delete',         [ __CLASS__, 'handle_delete' ] );
		add_action( 'admin_post_uw_vp_save_section',   [ __CLASS__, 'handle_save_section' ] );
		add_action( 'admin_post_uw_vp_delete_section', [ __CLASS__, 'handle_delete_section' ] );
		add_action( 'admin_post_uw_vp_toggle_section', [ __CLASS__, 'handle_toggle_section' ] );
		add_filter( 'template_include',                [ __CLASS__, 'route_template' ] );
		add_action( 'wp_enqueue_scripts',              [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_head',                         [ __CLASS__, 'output_seo' ], 1 );
		add_action( 'save_post_page',                  [ __CLASS__, 'bust_cache' ] );
		add_action( 'trashed_post',                    [ __CLASS__, 'bust_cache' ] );
		add_action( 'deleted_post',                    [ __CLASS__, 'bust_cache' ] );
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

	// =========================================================================
	// Admin page renderer
	// =========================================================================

	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$drafts       = self::get_pages_by_status( 'draft' );
		$published    = self::get_pages_by_status( 'publish' );
		$all_keywords = self::get_standard_keywords();
		$sections     = self::get_sections();
		$admin_url    = esc_url( admin_url( 'admin-post.php' ) );

		// Which section are we editing right now?
		$editing_id = sanitize_key( $_GET['edit_section'] ?? '' );
		$edit_data  = null;
		if ( $editing_id ) {
			foreach ( $sections as $s ) {
				if ( $s['id'] === $editing_id ) {
					$edit_data = $s;
					break;
				}
			}
		}

		// Flash notices from redirect params
		$notice = '';
		if ( ! empty( $_GET['uw_created'] ) ) {
			$n      = (int) $_GET['uw_created'];
			$notice = "<div class='notice notice-success is-dismissible'><p>"
				. sprintf( esc_html__( 'Created %d draft page(s).', 'unitwise-converters' ), $n )
				. '</p></div>';
		} elseif ( ! empty( $_GET['uw_published'] ) ) {
			$n      = (int) $_GET['uw_published'];
			$notice = "<div class='notice notice-success is-dismissible'><p>"
				. sprintf( esc_html__( 'Published %d page(s).', 'unitwise-converters' ), $n )
				. '</p></div>';
		} elseif ( ! empty( $_GET['uw_deleted'] ) ) {
			$notice = "<div class='notice notice-success is-dismissible'><p>"
				. esc_html__( 'Page moved to trash.', 'unitwise-converters' )
				. '</p></div>';
		} elseif ( ! empty( $_GET['uw_section_saved'] ) ) {
			$notice = "<div class='notice notice-success is-dismissible'><p>"
				. esc_html__( 'Section saved.', 'unitwise-converters' )
				. '</p></div>';
		} elseif ( ! empty( $_GET['uw_section_deleted'] ) ) {
			$notice = "<div class='notice notice-success is-dismissible'><p>"
				. esc_html__( 'Section deleted.', 'unitwise-converters' )
				. '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UnitWise Value Pages', 'unitwise-converters' ); ?></h1>
			<p style="color:#666;max-width:680px;">
				<?php esc_html_e( 'Generate SEO pages like "/1-gram-to-milligrams/" that directly answer specific conversion searches. Use Template Sections to add custom content blocks that appear on every value page.', 'unitwise-converters' ); ?>
			</p>
			<?php echo wp_kses_post( $notice ); ?>

			<!-- ================================================================
			     SECTION 1: Generate pages
			     ================================================================ -->
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

			<!-- ================================================================
			     SECTION 2: Drafts
			     ================================================================ -->
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

			<!-- ================================================================
			     SECTION 3: Published pages
			     ================================================================ -->
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

			<!-- ================================================================
			     SECTION 4: Template Sections manager
			     Add/edit/delete custom content blocks that appear on every
			     value page at a configurable position.
			     ================================================================ -->
			<div class="postbox" id="uw-template-sections-box">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Template Sections', 'unitwise-converters' ); ?></h2>
				</div>
				<div class="inside">
					<p style="color:#666;max-width:640px;">
						<?php esc_html_e( 'Custom content blocks that appear on every value page. Choose where each section is injected and toggle sections active/inactive without deleting them.', 'unitwise-converters' ); ?>
					</p>

					<?php if ( $sections ) : ?>
					<table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
						<thead>
							<tr>
								<th style="width:30%;"><?php esc_html_e( 'Section Title', 'unitwise-converters' ); ?></th>
								<th><?php esc_html_e( 'Position', 'unitwise-converters' ); ?></th>
								<th style="width:80px;"><?php esc_html_e( 'Status', 'unitwise-converters' ); ?></th>
								<th style="width:180px;"><?php esc_html_e( 'Actions', 'unitwise-converters' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $sections as $sec ) :
								$pos_label = self::$positions[ $sec['position'] ] ?? $sec['position'];
								$edit_link = admin_url( 'admin.php?page=uw-value-pages&edit_section=' . rawurlencode( $sec['id'] ) . '#uw-section-form' );
							?>
							<tr id="uw-sec-row-<?php echo esc_attr( $sec['id'] ); ?>">
								<td><strong><?php echo esc_html( $sec['title'] ); ?></strong></td>
								<td><?php echo esc_html( $pos_label ); ?></td>
								<td>
									<?php if ( $sec['enabled'] ) : ?>
										<span style="color:#00a32a;font-weight:600;"><?php esc_html_e( 'Active', 'unitwise-converters' ); ?></span>
									<?php else : ?>
										<span style="color:#888;"><?php esc_html_e( 'Inactive', 'unitwise-converters' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<!-- Edit button: fills form below via JS -->
									<a href="<?php echo esc_url( $edit_link ); ?>"
									   class="button button-small uw-edit-section"
									   data-id="<?php echo esc_attr( $sec['id'] ); ?>"
									   data-title="<?php echo esc_attr( $sec['title'] ); ?>"
									   data-position="<?php echo esc_attr( $sec['position'] ); ?>"
									   data-content="<?php echo esc_attr( $sec['content'] ); ?>"
									   data-enabled="<?php echo $sec['enabled'] ? '1' : '0'; ?>">
										<?php esc_html_e( 'Edit', 'unitwise-converters' ); ?>
									</a>
									&nbsp;
									<!-- Toggle active/inactive -->
									<form method="post" action="<?php echo $admin_url; ?>" style="display:inline;">
										<?php wp_nonce_field( 'uw_vp_toggle_section', 'uw_nonce' ); ?>
										<input type="hidden" name="action" value="uw_vp_toggle_section">
										<input type="hidden" name="section_id" value="<?php echo esc_attr( $sec['id'] ); ?>">
										<button type="submit" class="button button-small">
											<?php echo $sec['enabled']
												? esc_html__( 'Disable', 'unitwise-converters' )
												: esc_html__( 'Enable',  'unitwise-converters' ); ?>
										</button>
									</form>
									&nbsp;
									<!-- Delete -->
									<form method="post" action="<?php echo $admin_url; ?>" style="display:inline;"
										onsubmit="return confirm('<?php echo esc_js( __( 'Delete this section?', 'unitwise-converters' ) ); ?>')">
										<?php wp_nonce_field( 'uw_vp_delete_section', 'uw_nonce' ); ?>
										<input type="hidden" name="action" value="uw_vp_delete_section">
										<input type="hidden" name="section_id" value="<?php echo esc_attr( $sec['id'] ); ?>">
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
						<p style="color:#888;margin-bottom:16px;"><?php esc_html_e( 'No template sections yet. Add one below.', 'unitwise-converters' ); ?></p>
					<?php endif; ?>

					<!-- Add / Edit section form -->
					<div id="uw-section-form"
					     style="background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:20px;">
						<h3 style="margin-top:0;" id="uw-section-form-heading">
							<?php echo $edit_data
								? esc_html__( 'Edit Section', 'unitwise-converters' )
								: esc_html__( 'Add New Section', 'unitwise-converters' ); ?>
						</h3>
						<form method="post" action="<?php echo $admin_url; ?>">
							<?php wp_nonce_field( 'uw_vp_save_section', 'uw_nonce' ); ?>
							<input type="hidden" name="action" value="uw_vp_save_section">
							<!-- Populated by JS when editing an existing section -->
							<input type="hidden" name="section_id" id="uw-sec-id"
							       value="<?php echo esc_attr( $edit_data['id'] ?? '' ); ?>">

							<table class="form-table" role="presentation" style="margin-top:0;">
								<tr>
									<th scope="row" style="width:160px;">
										<label for="uw-sec-title"><?php esc_html_e( 'Section Title', 'unitwise-converters' ); ?></label>
									</th>
									<td>
										<input type="text" id="uw-sec-title" name="section_title"
										       class="regular-text"
										       value="<?php echo esc_attr( $edit_data['title'] ?? '' ); ?>"
										       placeholder="<?php esc_attr_e( 'e.g. Pro Tips, Did You Know?', 'unitwise-converters' ); ?>">
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="uw-sec-position"><?php esc_html_e( 'Position', 'unitwise-converters' ); ?></label>
									</th>
									<td>
										<select id="uw-sec-position" name="section_position">
											<?php foreach ( self::$positions as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"
												<?php selected( $edit_data['position'] ?? '', $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Where this section appears on every value page.', 'unitwise-converters' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="uw-sec-content"><?php esc_html_e( 'Content', 'unitwise-converters' ); ?></label>
									</th>
									<td>
										<textarea id="uw-sec-content" name="section_content"
										          rows="10"
										          style="width:100%;font-size:13px;line-height:1.6;"
										          placeholder="<?php esc_attr_e( 'Enter HTML or plain text. <p>, <ul>, <strong>, <a> and similar tags are supported.', 'unitwise-converters' ); ?>"><?php echo esc_textarea( $edit_data['content'] ?? '' ); ?></textarea>
										<p class="description"><?php esc_html_e( 'Supports HTML. Use {from}, {to}, {value}, {result} as placeholders — they are replaced at render time.', 'unitwise-converters' ); ?></p>
									</td>
								</tr>
							</table>

							<p style="margin-top:16px;">
								<button type="submit" class="button button-primary">
									<?php echo $edit_data
										? esc_html__( 'Update Section', 'unitwise-converters' )
										: esc_html__( 'Save Section', 'unitwise-converters' ); ?>
								</button>
								<?php if ( $edit_data ) : ?>
								&nbsp;
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=uw-value-pages' ) ); ?>"
								   class="button button-secondary">
									<?php esc_html_e( 'Cancel', 'unitwise-converters' ); ?>
								</a>
								<?php endif; ?>
							</p>
						</form>
					</div><!-- #uw-section-form -->
				</div>
			</div><!-- #uw-template-sections-box -->

		</div><!-- .wrap -->

		<script>
		/* Standard-set fill */
		document.getElementById('uw-gen-standard').addEventListener('click', function() {
			var keywords = <?php echo wp_json_encode( $all_keywords ); ?>;
			document.getElementById('uw-keywords').value = keywords.join('\n');
			document.getElementById('uw-keywords').focus();
		});

		/* Edit section — populate form without a page reload */
		document.querySelectorAll('.uw-edit-section').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				var d = this.dataset;
				document.getElementById('uw-sec-id').value        = d.id;
				document.getElementById('uw-sec-title').value     = d.title;
				document.getElementById('uw-sec-position').value  = d.position;
				document.getElementById('uw-sec-content').value   = d.content;
				document.getElementById('uw-section-form-heading').textContent =
					<?php echo wp_json_encode( __( 'Edit Section', 'unitwise-converters' ) ); ?>;
				document.getElementById('uw-section-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});
		</script>
		<?php
	}

	// =========================================================================
	// Form handlers — pages
	// =========================================================================

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
		$count = 0;
		foreach ( self::get_pages_by_status( 'draft' ) as $p ) {
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

	// =========================================================================
	// Form handlers — template sections
	// =========================================================================

	public static function handle_save_section(): void {
		check_admin_referer( 'uw_vp_save_section', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}

		$section_id = sanitize_key( wp_unslash( $_POST['section_id'] ?? '' ) );
		$title      = sanitize_text_field( wp_unslash( $_POST['section_title'] ?? '' ) );
		$position   = sanitize_key( wp_unslash( $_POST['section_position'] ?? '' ) );
		$content    = wp_kses_post( wp_unslash( $_POST['section_content'] ?? '' ) );

		if ( ! $title || ! isset( self::$positions[ $position ] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages' ) );
			exit;
		}

		$sections = self::get_sections();

		if ( $section_id ) {
			// Update existing
			$found = false;
			foreach ( $sections as &$s ) {
				if ( $s['id'] === $section_id ) {
					$s['title']    = $title;
					$s['position'] = $position;
					$s['content']  = $content;
					$found = true;
					break;
				}
			}
			unset( $s );
			if ( ! $found ) {
				wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages' ) );
				exit;
			}
		} else {
			// Add new
			$sections[] = [
				'id'       => 'sec_' . substr( md5( uniqid( '', true ) ), 0, 8 ),
				'title'    => $title,
				'position' => $position,
				'content'  => $content,
				'enabled'  => true,
			];
		}

		self::save_sections( $sections );
		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_section_saved=1' ) );
		exit;
	}

	public static function handle_delete_section(): void {
		check_admin_referer( 'uw_vp_delete_section', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}
		$section_id = sanitize_key( wp_unslash( $_POST['section_id'] ?? '' ) );
		$sections   = self::get_sections();
		$sections   = array_values( array_filter( $sections, fn( $s ) => $s['id'] !== $section_id ) );
		self::save_sections( $sections );
		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_section_deleted=1' ) );
		exit;
	}

	public static function handle_toggle_section(): void {
		check_admin_referer( 'uw_vp_toggle_section', 'uw_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'unitwise-converters' ) );
		}
		$section_id = sanitize_key( wp_unslash( $_POST['section_id'] ?? '' ) );
		$sections   = self::get_sections();
		foreach ( $sections as &$s ) {
			if ( $s['id'] === $section_id ) {
				$s['enabled'] = ! $s['enabled'];
				break;
			}
		}
		unset( $s );
		self::save_sections( $sections );
		wp_safe_redirect( admin_url( 'admin.php?page=uw-value-pages&uw_section_saved=1' ) );
		exit;
	}

	// =========================================================================
	// Template routing + assets
	// =========================================================================

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

	// =========================================================================
	// SEO meta tags
	// =========================================================================

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

		if ( ! $from || ! $to || ! class_exists( 'Unitwise_Engine' ) ) {
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

	// =========================================================================
	// Template section rendering (called from single-unitwise_value_page.php)
	// =========================================================================

	/**
	 * Get all template sections (stored in wp_options).
	 *
	 * @return array<int, array{id:string,title:string,content:string,position:string,enabled:bool}>
	 */
	public static function get_sections(): array {
		$raw = get_option( self::SECTIONS_OPTION, [] );
		return is_array( $raw ) ? $raw : [];
	}

	/** Persist the sections array. */
	private static function save_sections( array $sections ): void {
		update_option( self::SECTIONS_OPTION, $sections, false );
	}

	/**
	 * Output HTML for all enabled sections at a given position.
	 * Supports {from}, {to}, {value}, {result} placeholders in content.
	 *
	 * Call from the theme template at each injection point:
	 *   Unitwise_Value_Pages::render_sections( 'after_formula', $ctx );
	 *
	 * @param string $position  One of the keys in self::$positions.
	 * @param array  $ctx       Optional context: ['from'=>string,'to'=>string,'value'=>string,'result'=>string]
	 */
	public static function render_sections( string $position, array $ctx = [] ): void {
		$sections = self::get_sections();
		foreach ( $sections as $s ) {
			if ( ! $s['enabled'] || $s['position'] !== $position ) {
				continue;
			}
			$content = $s['content'];
			if ( $ctx ) {
				$content = str_replace(
					[ '{from}', '{to}', '{value}', '{result}' ],
					[
						esc_html( $ctx['from']   ?? '' ),
						esc_html( $ctx['to']     ?? '' ),
						esc_html( $ctx['value']  ?? '' ),
						esc_html( $ctx['result'] ?? '' ),
					],
					$content
				);
			}
			?>
			<section class="vp-section vp-custom-section">
				<h2 class="vp-section__title"><?php echo esc_html( $s['title'] ); ?></h2>
				<div class="vp-custom-section__body">
					<?php echo wp_kses_post( $content ); ?>
				</div>
			</section>
			<?php
		}
	}

	// =========================================================================
	// Keyword parser
	// =========================================================================

	/**
	 * @return array{value:float,from:string,to:string,cat:string}|null
	 */
	public static function parse_keyword( string $line ): ?array {
		$line = strtolower( trim( $line ) );
		$line = preg_replace( '/\s+/', ' ', $line );
		if ( ! preg_match( '/^([0-9]+(?:\.[0-9]+)?)\s+([a-z]+)\s+to\s+([a-z]+)$/', $line, $m ) ) {
			return null;
		}
		$value     = (float) $m[1];
		$from_slug = self::$label_map[ $m[2] ] ?? null;
		$to_slug   = self::$label_map[ $m[3] ] ?? null;
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

	// =========================================================================
	// Slug / title builders
	// =========================================================================

	public static function build_slug( float $value, string $from_slug, string $to_slug ): string {
		$f = ( 1.0 === $value && isset( self::$singular_url[ $from_slug ] ) )
			? self::$singular_url[ $from_slug ]
			: $from_slug;
		$v = ( floor( $value ) == $value ) ? (int) $value : $value;
		return $v . '-' . $f . '-to-' . $to_slug;
	}

	public static function build_title( float $value, string $from_slug, string $to_slug ): string {
		$from_l = Unitwise_Registry::get_label( $from_slug, ( 1.0 !== $value ) );
		$to_l   = Unitwise_Registry::get_label( $to_slug, true );
		$v      = ( floor( $value ) == $value ) ? (int) $value : $value;
		return "{$v} {$from_l} to {$to_l}";
	}

	// =========================================================================
	// Standard keyword set
	// =========================================================================

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
				$lbl        = strtolower( Unitwise_Registry::get_label( $from, ( 1 != $v ) ) );
				$keywords[] = $v . ' ' . $lbl . ' to ' . $to;
			}
		}
		return $keywords;
	}

	// =========================================================================
	// Data helpers
	// =========================================================================

	/** @return WP_Post[] */
	public static function get_pages_by_status( string $status ): array {
		return get_posts( [
			'post_type'     => 'page',
			'post_status'   => $status,
			'numberposts'   => -1,
			'orderby'       => 'post_name',
			'order'         => 'ASC',
			'meta_key'      => self::META_FLAG,
			'meta_value'    => '1',
			'no_found_rows' => true,
		] );
	}

	/** @return array<int, array{slug:string,value:float,from:string,to:string,url:string,title:string}> */
	public static function all_pages_data(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$data = [];
		foreach ( self::get_pages_by_status( 'publish' ) as $p ) {
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
