<?php
/**
 * Form shipping dpd terminals template.
 *
 * @category Form shipping
 * @package  Dpd
 * @author   DPD
 */

?>

<tr class="wc_shipping_dpd_terminals">
    <th><?php esc_html_e( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ); ?> <abbr class="required" title="required">*</abbr></th>
    <td>
        <div class="custom-dropdown">
            <?php if ( $selected != '' && $selected_terminal_name != '') : ?>
                <div class="selected-option"><?php echo esc_attr( $selected_terminal_name ?? "" ); ?></div>
            <?php else: ?>
                <div class="selected-option"><?= esc_html( __( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ); ?></div>
            <?php endif; ?>
            <ul class="dropdown-list">
                <input type="text" class="js--pudo-search" style="width: 100%; padding: 1rem;" placeholder="<?php echo esc_html( __( 'Search', 'woo-shipping-dpd-baltic' ) ); ?>">
                <?php if( !count( $terminals ) ) : ?>
                    <li class="pudo" data-value=""><?= esc_html( __( 'The Pickup Point is empty', 'woo-shipping-dpd-baltic' ) ); ?></li>
                <?php else : ?>
                    <?php foreach ( $terminals as $group_name => $locations ) : ?>
                        <li class="group-pudo"><?php echo esc_html( $group_name ); ?></li>
                        <?php foreach ( $locations as $location ) : ?>
                            <?php if ( $location->status == '1' ) : ?>
                                <li class="pudo" data-cod="<?php echo esc_attr( $location->cod ); ?>" data-value="<?php echo esc_html( $location->parcelshop_id ); ?>"><?php echo esc_html( $location->name ); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <div id="load-more-btn" class="<?= $load_more_page != -1 ? '' : 'hidden'; ?>" load-more-page="<?php echo $load_more_page; ?>"><span class="load-more button">Load More<span></div>
                <?php endif; ?>
            </ul>
        </div>

        <input type="hidden" name="wc_shipping_dpd_parcels_terminal" id="wc_shipping_dpd_parcels_terminal" value="<?php echo esc_attr( $selected ?? "" ); ?>" />
    </td>
</tr>
