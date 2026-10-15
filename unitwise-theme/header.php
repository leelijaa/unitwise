<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>(function(){try{var t=localStorage.getItem('uw-theme');if(t){document.documentElement.setAttribute('data-theme',t);}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content">
	<?php esc_html_e( 'Skip to content', 'unitwise-theme' ); ?>
</a>

<header class="site-header">
	<div class="container site-header__inner">

		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__logo" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Home">
			<span class="site-header__logo-icon animate-logo-pop" aria-hidden="true">UW</span>
			<span class="site-header__logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</a>

		<nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'unitwise-theme' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( [
					'theme_location' => 'primary',
					'menu_class'     => 'nav-menu',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => false,
				] );
			} else {
				unitwise_fallback_nav();
			}
			?>
		</nav>

		<div class="site-header__actions">
			<button class="theme-toggle-btn" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'unitwise-theme' ); ?>">🌙</button>
			<button class="hamburger-btn" aria-label="<?php esc_attr_e( 'Open menu', 'unitwise-theme' ); ?>" aria-expanded="false" aria-controls="mobile-nav">☰</button>
		</div>

	</div>

	<nav id="mobile-nav" class="mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'unitwise-theme' ); ?>" hidden>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'unitwise-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/weight/' ) ); ?>"><?php esc_html_e( 'Weight Converters', 'unitwise-theme' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'unitwise-theme' ); ?></a>
	</nav>
</header>

