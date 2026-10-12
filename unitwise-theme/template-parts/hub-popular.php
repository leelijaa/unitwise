<?php
/**
 * Hub homepage — popular converters grid (uses the plugin block).
 */
?>
<section class="hub-popular" aria-label="<?php esc_attr_e( 'Popular converters', 'unitwise-theme' ); ?>">
	<h2 class="hub-section-title"><?php esc_html_e( 'Popular Converters', 'unitwise-theme' ); ?></h2>
	<?php echo do_blocks( '<!-- wp:unitwise/popular-converters {"category":"weight","limit":12,"title":""} /-->' ); ?>
</section>
