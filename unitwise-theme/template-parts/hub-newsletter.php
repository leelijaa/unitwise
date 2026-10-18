<?php
/**
 * Newsletter CTA — displayed on the hub homepage.
 * No back-end yet; theme.js shows a "coming soon" notice on submit.
 */
?>
<aside class="hub-newsletter" aria-label="<?php esc_attr_e( 'Newsletter sign-up', 'unitwise-theme' ); ?>">
	<h2 class="hub-newsletter__title"><?php esc_html_e( 'Stay in the Loop', 'unitwise-theme' ); ?></h2>
	<p class="hub-newsletter__sub"><?php esc_html_e( 'Get notified when we add new converters, guides, and tools. No spam, unsubscribe any time.', 'unitwise-theme' ); ?></p>
	<form class="hub-newsletter__form" method="post" aria-label="<?php esc_attr_e( 'Email sign-up form', 'unitwise-theme' ); ?>">
		<label for="newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Email address', 'unitwise-theme' ); ?></label>
		<input type="email" id="newsletter-email" name="email" class="hub-newsletter__input" placeholder="<?php esc_attr_e( 'you@example.com', 'unitwise-theme' ); ?>" autocomplete="email" required>
		<button type="submit" class="hub-newsletter__btn"><?php esc_html_e( 'Subscribe', 'unitwise-theme' ); ?></button>
	</form>
</aside>
