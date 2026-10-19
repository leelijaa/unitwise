<?php
/**
 * HUB homepage template.
 *
 * All sections are editable Gutenberg blocks seeded by Unitwise_Seeder::seed_homepage():
 * unitwise/hub-hero, unitwise/hub-category-grid, unitwise/popular-converters,
 * unitwise/recent-posts, unitwise/hub-newsletter.
 *
 * Edit the homepage in WP Admin → Pages → Homepage.
 * If content is ever cleared, it auto-re-seeds on the next admin page load.
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
