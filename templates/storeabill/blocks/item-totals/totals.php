<?php
/**
 * StoreaBill totals wrapper override to append payment method notice.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\StoreaBill\Document\Document $document
 */
global $document;

$payment_notice = GAED_StoreaBill_Integration::get_payment_method_display( $document );
?>
<table class="sab-item-totals-wrapper" autosize="1" style="page-break-inside: avoid">
	<tbody>
		<tr>
			<td class="sab-item-totals-wrapper-first"></td>
			<td class="sab-item-totals-wrapper-last">
				<table class="sab-item-totals <?php sab_print_html_classes( $classes ); ?>">
					<tbody>
						<?php echo sab_render_blocks( $totals['innerBlocks'] ); ?>
					</tbody>
				</table>
				<?php if ( ! empty( $payment_notice ) ) : ?>
					<div class="gaed-payment-method">
						<?php echo wp_kses_post( $payment_notice ); ?>
					</div>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>
