<?php
/**
 * Silo landing page — one per converter category (e.g. /weight/).
 */

get_header();

global $wp_query;
$term  = $wp_query->queried_object instanceof WP_Term ? $wp_query->queried_object : null;
$posts = $wp_query->unitwise_converter_posts ?? [];
$count = count( $posts );
?>

<main id="main-content" class="site-main silo-page">

	<?php
	if ( $term ) {
		$GLOBALS['uw_crumbs'] = [
			[ 'label' => $term->name . ' ' . __( 'Converters', 'unitwise-theme' ), 'url' => '' ],
		];
	}
	get_template_part( 'template-parts/breadcrumbs' );
	?>

	<!-- Hero -->
	<section class="silo-hero">
		<div class="silo-hero__bg-grid" aria-hidden="true"></div>
		<div class="silo-hero__bg-glow" aria-hidden="true"></div>
		<div class="container silo-hero__inner">
			<div class="silo-hero__badge">
				<span class="silo-hero__badge-dot" aria-hidden="true"></span>
				<?php esc_html_e( 'Unit Converters', 'unitwise-theme' ); ?>
			</div>
			<h1 class="silo-hero__title">
				<?php
				if ( $term ) {
					echo esc_html( $term->name ) . ' <span class="accent">' . esc_html__( 'Converters', 'unitwise-theme' ) . '</span>';
				} else {
					esc_html_e( 'Converters', 'unitwise-theme' );
				}
				?>
			</h1>
			<?php if ( $term && $term->description ) : ?>
				<p class="silo-hero__desc"><?php echo esc_html( $term->description ); ?></p>
			<?php else : ?>
				<p class="silo-hero__desc">
					<?php
					printf(
						/* translators: %s: category name */
						esc_html__( 'Fast, accurate %s conversion tools — enter any value and get the result instantly.', 'unitwise-theme' ),
						$term ? esc_html( strtolower( $term->name ) ) : ''
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( $count ) : ?>
				<div class="silo-hero__stats">
					<span class="silo-hero__stat">
						<strong><?php echo esc_html( $count ); ?></strong>
						<?php esc_html_e( 'converters', 'unitwise-theme' ); ?>
					</span>
					<span class="silo-hero__stat-sep" aria-hidden="true">·</span>
					<span class="silo-hero__stat"><?php esc_html_e( '100% free', 'unitwise-theme' ); ?></span>
					<span class="silo-hero__stat-sep" aria-hidden="true">·</span>
					<span class="silo-hero__stat"><?php esc_html_e( 'Instant results', 'unitwise-theme' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Converter grid -->
	<section class="silo-grid-section" aria-label="<?php echo $term ? esc_attr( sprintf( __( '%s converters', 'unitwise-theme' ), $term->name ) ) : esc_attr__( 'Converters', 'unitwise-theme' ); ?>">
		<div class="container">
			<?php if ( ! empty( $posts ) ) : ?>
				<ul class="silo-converter-grid" role="list">
					<?php foreach ( $posts as $converter_post ) :
						$from     = get_post_meta( $converter_post->ID, 'unitwise_from_unit', true );
						$to       = get_post_meta( $converter_post->ID, 'unitwise_to_unit',   true );
						$from_sym = $from ? Unitwise_Registry::get_symbol( $from ) : '';
						$to_sym   = $to   ? Unitwise_Registry::get_symbol( $to )   : '';
						$from_lbl = $from ? Unitwise_Registry::get_label( $from, true ) : '';
						$to_lbl   = $to   ? Unitwise_Registry::get_label( $to, true )   : '';
						$url      = get_permalink( $converter_post );
					?>
					<li class="silo-card">
						<a href="<?php echo esc_url( $url ); ?>" class="silo-card__link">
							<span class="silo-card__sym">
								<span class="silo-card__sym-from"><?php echo esc_html( $from_sym ); ?></span>
								<span class="silo-card__sym-arrow" aria-hidden="true">→</span>
								<span class="silo-card__sym-to"><?php echo esc_html( $to_sym ); ?></span>
							</span>
							<span class="silo-card__title"><?php echo esc_html( $converter_post->post_title ); ?></span>
							<span class="silo-card__units"><?php echo esc_html( $from_lbl ); ?> → <?php echo esc_html( $to_lbl ); ?></span>
							<span class="silo-card__arrow" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
							</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No converters found. Run the seeder from Tools → UnitWise Seeder.', 'unitwise-theme' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
