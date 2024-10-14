<?php
/**
 * Manifest display template.
 *
 * @category Form
 * @package  Dpd
 * @author   DPD
 */

?>

<h2><?php esc_html_e( 'Manifests', 'woo-shipping-dpd-baltic' ); ?></h2>

<table class="form-table">
	<tbody>
	<tr valign="top">
		<td colspan="2">
			<table class="wc_gateways widefat" cellspacing="0">
				<thead>
				<tr>
					<th><?php esc_html_e( 'File name', 'woo-shipping-dpd-baltic' ); ?></th>
					<th><?php esc_html_e( 'Date', 'woo-shipping-dpd-baltic' ); ?></th>
					<th class="action"></th>
				</tr>
				</thead>
				<tbody class="ui-sortable">
				<?php foreach ( $results as $result ) :
                    $base64_decode_pdf = base64_decode( esc_textarea( $result->pdf ) );
                    $convert_to_object = json_decode( $base64_decode_pdf ) ?: null;
                ?>
				<tr>
					<td>manifest_<?php echo esc_html( str_replace( '-', '_', $result->date ) ); ?>.pdf</td>
					<td width="20%"><?php echo esc_html( $result->date ); ?></td>
					<td width="20%">
                        <?php if ( ! empty($convert_to_object) && $convert_to_object->status = 'err' ) : ?>
                            <p style="color:red"> <?php echo $convert_to_object->errlog ?> </p>
                        <?php else : ?>
						    <a class="button alignright" type="submit" href="<?php echo esc_url( get_admin_url() ); ?>admin.php?page=wc-settings&tab=dpd&section=manifests&admin_ajax_nonce=<?php echo esc_attr( wp_create_nonce( 'admin-nonce' ) ); ?>&download_manifest=<?php echo esc_attr( $result->id ); ?>"><?php esc_html_e( 'Download', 'woo-shipping-dpd-baltic' ); ?></a>
                        <?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>
