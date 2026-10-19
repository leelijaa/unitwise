<?php
/**
 * HUB homepage template.
 *
 * All sections are editable Gutenberg blocks: unitwise/hub-hero,
 * unitwise/hub-category-grid, unitwise/popular-converters,
 * unitwise/recent-posts, unitwise/hub-newsletter.
 * Edit the homepage in WP Admin → Pages → Homepage.
 */

get_header();

if ( have_posts() ) {
	the_post();
}
?>

<main id="main-content" class="site-main hub-page">
	<?php the_content(); ?>
</main>

<?php get_footer(); ?>
