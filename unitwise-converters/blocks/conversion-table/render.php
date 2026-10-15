<?php
/**
 * Server-side render for unitwise/conversion-table.
 *
 * Generates a real HTML <table> of common values — crawlable, no JS needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$from = ! empty( $attributes['from'] )
	? sanitize_key( $attributes['from'] )
	: ( $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_from_unit', true ) ) : '' );

$to = ! empty( $attributes['to'] )
	? sanitize_key( $attributes['to'] )
	: ( $post_id ? sanitize_key( get_post_meta( $post_id, 'unitwise_to_unit', true ) ) : '' );

if ( ! $from || ! $to || ! Unitwise_Registry::unit_exists( $from ) || ! Unitwise_Registry::unit_exists( $to ) ) {
	return;
}

$engine    = new Unitwise_Engine( Unitwise_Registry::get_unit_category( $from ) );
$values    = Unitwise_Engine::default_table_values( $from );
$rows      = $engine->get_table_rows( $from, $to, $values );
$from_lbl  = Unitwise_Registry::get_label( $from, true );
$to_lbl    = Unitwise_Registry::get_label( $to, true );
$from_sym  = Unitwise_Registry::get_symbol( $from );
$to_sym    = Unitwise_Registry::get_symbol( $to );
?>
<div class="unitwise-table-wrap">
	<table class="unitwise-conversion-table">
		<caption class="screen-reader-text">
			<?php printf( esc_html__( '%1$s to %2$s conversion table', 'unitwise-converters' ), esc_html( $from_lbl ), esc_html( $to_lbl ) ); ?>
		</caption>
		<thead>
			<tr>
				<th scope="col">
					<?php echo esc_html( $from_lbl ); ?>
					<span class="unitwise-th-sym">(<?php echo esc_html( $from_sym ); ?>)</span>
				</th>
				<th scope="col">
					<?php echo esc_html( $to_lbl ); ?>
					<span class="unitwise-th-sym">(<?php echo esc_html( $to_sym ); ?>)</span>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows as $row ) : ?>
			<tr>
				<td><?php echo esc_html( $row['from'] ); ?></td>
				<td><?php echo esc_html( $row['to'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
