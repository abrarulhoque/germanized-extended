<?php
/**
 * StoreaBill item table row override with AED conversion per line item.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\StoreaBill\Document\Document $document
 * @var \Vendidero\StoreaBill\Document\Item     $item
 * @var integer                                     $count
 * @var integer                                     $item_size
 * @var array                                       $columns
 */

$column_index = 0;
\Vendidero\StoreaBill\Package::setup_document_item( $item );
?>
<?php do_action( "storeabill_{$document->get_type()}_item_table_before_row", $item, $document, $columns, $count, $item_size ); ?>

<tr class="sab-item-table-row sab-item-table-row-<?php echo esc_attr( $document->get_type() ); ?> <?php sab_print_html_classes( apply_filters( "storeabill_{$document->get_type()}_item_table_column_classes", sab_get_html_loop_classes( 'sab-item-table-row', $item_size, $count ), $item, $document ) ); ?>">
	<?php
	foreach ( $columns as $column ) :
		++$column_index;
		?>
		<td class="sab-item-table-column-body <?php sab_print_html_classes( $column['classes'] ); ?>" style="<?php sab_print_styles( array_merge( $column['styles'], array( 'width' => $column['width'] ) ) ); ?>">
			<?php echo sab_render_blocks( $column['innerBlocks'], array( 'fontSize' => $column['fontSize'] ) ); ?>
			<?php
			$line_item_aed = GAED_StoreaBill_Integration::get_line_item_aed_markup( $column, $item, $document );
			if ( ! empty( $line_item_aed ) ) {
				echo $line_item_aed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<?php do_action( "storeabill_{$document->get_type()}_item_table_after_column", $column, $item, $document, $column_index ); ?>
		</td>
	<?php endforeach; ?>
</tr>

<?php do_action( "storeabill_{$document->get_type()}_item_table_after_row", $item, $document, $columns, $count, $item_size ); ?>
