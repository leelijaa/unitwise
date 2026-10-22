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
			<button class="theme-toggle-btn" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'unitwise-theme' ); ?>">
				<span class="icon-moon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
				<span class="icon-sun" aria-hidden="true" hidden><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></span>
			</button>
			<button class="hamburger-btn" aria-label="<?php esc_attr_e( 'Open menu', 'unitwise-theme' ); ?>" aria-expanded="false" aria-controls="mobile-nav">
				<span class="icon-menu" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></span>
				<span class="icon-close" aria-hidden="true" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
			</button>
		</div>

	</div>

	<nav id="mobile-nav" class="mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'unitwise-theme' ); ?>" hidden>
		<?php wp_nav_menu( [
			'theme_location' => 'primary',
			'menu_class'     => 'mobile-nav__menu',
			'container'      => false,
			'depth'          => 1,
			'fallback_cb'    => false,
		] ); ?>
	</nav>
</header>

