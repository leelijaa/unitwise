<?php
/**
 * Render: unitwise/hub-newsletter — newsletter sign-up CTA.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$heading = esc_html( $attributes['heading'] ?? 'Stay in the Loop' );
$subtext = esc_html( $attributes['subtext'] ?? '' );
?>
<aside <?php echo get_block_wrapper_attributes( [ 'class' => 'hub-newsletter' ] ); ?> aria-label="<?php esc_attr_e( 'Newsletter sign-up', 'unitwise-converters' ); ?>">
	<h2 class="hub-newsletter__title"><?php echo $heading; ?></h2>
	<?php if ( $subtext ) : ?>
		<p class="hub-newsletter__sub"><?php echo $subtext; ?></p>
	<?php endif; ?>
	<form class="hub-newsletter__form" method="post" aria-label="<?php esc_attr_e( 'Email sign-up form', 'unitwise-converters' ); ?>">
		<label for="newsletter-email-<?php echo esc_attr( $block->context['postId'] ?? 'hub' ); ?>" class="screen-reader-text">
			<?php esc_html_e( 'Email address', 'unitwise-converters' ); ?>
		</label>
		<input type="email"
			id="newsletter-email-<?php echo esc_attr( $block->context['postId'] ?? 'hub' ); ?>"
			name="email"
			class="hub-newsletter__input"
			placeholder="<?php esc_attr_e( 'you@example.com', 'unitwise-converters' ); ?>"
			autocomplete="email"
			required>
		<button type="submit" class="hub-newsletter__btn">
			<?php esc_html_e( 'Subscribe', 'unitwise-converters' ); ?>
		</button>
	</form>
</aside>
