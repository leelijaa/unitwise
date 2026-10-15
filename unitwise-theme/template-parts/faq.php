<?php
/**
 * FAQ accordion.
 *
 * Receives $args['faqs'] — array of { q, a } objects.
 * Works without JS (details/summary).
 */

$faqs = $args['faqs'] ?? [];
if ( empty( $faqs ) ) {
	return;
}
?>
<div class="unitwise-faq">
	<?php foreach ( $faqs as $item ) :
		$q = isset( $item['q'] ) ? wp_kses_post( $item['q'] ) : '';
		$a = isset( $item['a'] ) ? wp_kses_post( $item['a'] ) : '';
		if ( ! $q || ! $a ) continue;
	?>
	<details class="unitwise-faq__item">
		<summary class="unitwise-faq__question"><?php echo esc_html( wp_strip_all_tags( $q ) ); ?></summary>
		<div class="unitwise-faq__answer"><?php echo wp_kses_post( $a ); ?></div>
	</details>
	<?php endforeach; ?>
</div>
