<?php
/**
 * Fallback template — WordPress requires this file.
 * In normal usage, more specific templates (front-page.php, single.php, etc.) take precedence.
 */

get_header();
?>

<main id="main-content" class="site-main">
	<div class="container">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		} else {
			echo '<p>' . esc_html__( 'Nothing found.', 'unitwise-theme' ) . '</p>';
		}
		?>
	</div>
</main>

<?php get_footer(); ?>
