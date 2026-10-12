<?php
/**
 * Blog sidebar — only rendered when the sidebar has widgets.
 */

if ( ! is_active_sidebar( 'blog-sidebar' ) ) {
	return;
}
?>
<aside class="blog-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Sidebar', 'unitwise-theme' ); ?>">
	<?php dynamic_sidebar( 'blog-sidebar' ); ?>
</aside>
