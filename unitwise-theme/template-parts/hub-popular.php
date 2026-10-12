<?php
/**
 * Hub homepage — popular converters grid (uses the plugin block).
 */
?>
<div class="hub-popular">
	<?php echo do_blocks( '<!-- wp:unitwise/popular-converters {"category":"weight","limit":12,"title":""} /-->' ); ?>
</div>
