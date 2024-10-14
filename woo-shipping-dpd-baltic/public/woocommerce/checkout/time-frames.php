<?php
/**
 * Time frames template.
 *
 * @category Timeframes
 * @package  Dpd
 * @author   DPD
 */

?>

<tr class="wc_shipping_dpd_home_delivery">
	<th><?php esc_html_e( 'Timeframes', 'woo-shipping-dpd-baltic' ); ?></th>
	<td>
		<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" style="width: 100%;">
			<?php foreach ( $shifts as $shift ) : ?>
				<option <?php selected( $selected, $shift ); ?>>
					<?php echo esc_html( $shift ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
