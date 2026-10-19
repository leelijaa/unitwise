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
	<p class="hub-newsletter__coming-soon">
		<?php esc_html_e( 'Newsletter coming soon — check back shortly.', 'unitwise-converters' ); ?>
	</p>
</aside>
