/**
 * Handle js for dpd admin.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */

(function( $ ) {
	'use strict';

	$(
		function() {

			// Repeater.
			$( '.warehouses-repeater' ).repeater(
				{
					// (Optional)
					// start with an empty list of repeaters. Set your first (and only)
					// "data-repeater-item" with style="display:none;" and pass the
					// following configuration flag
					initEmpty: false,
					// (Optional)
					// "defaultValues" sets the values of added items.  The keys of
					// defaultValues refer to the value of the input's name attribute.
					// If a default value is not specified for an input, then it will
					// have its value cleared.
					defaultValues: {
						'name': ''
					},
					// (Optional)
					// "show" is called just after an item is added.  The item is hidden
					// at this point.  If a show callback is not given the item will
					// have $(this).show() called on it.
					show: function () {
						$( this ).slideDown();
					},
					// (Optional)
					// "hide" is called when a user clicks on a data-repeater-delete
					// element.  The item is still visible.  "hide" is passed a function
					// as its first argument which will properly remove the item.
					// "hide" allows for a confirmation step, to send a delete request
					// to the server, etc.  If a hide callback is not given the item
					// will be deleted.
					hide: function (deleteElement) {
						let option_id = $( this ).find( 'button' ).data( 'optionkey' );

						if ( confirm( 'Are you sure you want to delete this warehouse?' ) ) {
							$( this ).slideUp(
								deleteElement,
								function(){
									this.remove();
								}
							);

							let data = {
								'action': 'delete_warehouse',
								'option_id': option_id,
								'admin_ajax_nonce': wc_dpd_baltic.admin_ajax_nonce
							};

							if (option_id) {
								$.post(
									ajaxurl,
									data,
									function() {
										// info?
									}
								);
							}

						}
					},
					// (Optional)
					// You can use this if you need to manually re-index the list
					// for example if you are using a drag and drop library to reorder
					// list items.
					ready: function (setIndexes) {
						// $dragAndDrop.on('drop', setIndexes);
					},
					// (Optional)
					// Removes the delete button from the first list item,
					// defaults to false.
					isFirstItemUndeletable: false
				}
			)

			// Request collect.
			const $request_collect = $( '#request_collect' );

			$request_collect.on(
				'click',
				function(e){
					if ($( '#mainform' )[0].checkValidity()) {
						e.preventDefault();

						const $this   = $( this );
						const $form   = $this.closest( 'form' );
						const $fields = $form.serializeArray();

						if ( confirm( 'Are you sure?' ) ) {
							$.post(
								ajaxurl,
								$fields,
								function(response) {
									window.location = response;
								}
							);
						}
					}
				}
			);

		}
	);

	$(
		function () {
			// Add buttons to product screen.
			var $order_screen = $( '.edit-php.post-type-shop_order' );

			if ($order_screen !== undefined && $order_screen.length === 0) {
				$order_screen = $( '.post-type-shop_order' );
			}

			var $title_action = $order_screen.find( '.page-title-action:first' );
			var $blankslate = $order_screen.find( '.woocommerce-BlankState' );

			if ( 0 === $blankslate.length ) {
				$title_action.after( '<a href="#TB_inline?width=600&height=200&inlineId=close-dpd-manifest" class="page-title-action thickbox">' + wc_dpd_baltic.i18n.close_manifest + '</a>' );
				$title_action.after( '<a href="#TB_inline?width=600&height=500&inlineId=request-dpd-courier" class="page-title-action thickbox">' + wc_dpd_baltic.i18n.request_courier + '</a>' );
			} else {
				$title_action.hide();
			}
		}
	);

})( jQuery );
