<?php
/**
 * Static page template.
 */

get_header();
the_post();
?>

<main id="main-content" class="site-main single-page">
<div class="container container--narrow">
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
		<header class="entry-header">
			<h1 class="entry-title"><?php the_title(); ?></h1>
		</header>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>
</div>
</main>

<?php get_footer(); ?>
