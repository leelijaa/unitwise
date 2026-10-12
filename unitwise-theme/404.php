<?php
/**
 * 404 template.
 */

get_header();
?>

<main id="main-content" class="site-main error-404">
	<div class="container">
		<section>
			<h1><?php esc_html_e( 'Page Not Found', 'unitwise-theme' ); ?></h1>
			<p><?php esc_html_e( 'The page you were looking for does not exist. Try using the search or browse our converters below.', 'unitwise-theme' ); ?></p>
			<?php get_search_form(); ?>
			<p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
					<?php esc_html_e( '← Back to Home', 'unitwise-theme' ); ?>
				</a>
			</p>
		</section>
	</div>
</main>

<?php get_footer(); ?>
