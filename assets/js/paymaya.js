jQuery(document).ready(function ($) {
    const capturePanel = {
        init: function () {
            const captureInterface = `
                <div class="wc-order-data-row wc-order-capture-items wc-order-data-row-toggle" style="display: none;">
                    <table class="wc-order-totals">
                        <tbody>
                            <tr>
                                <td class="label">Order Total:</td>
                                <td width="1%"></td>
                                <td class="total">
                                    <span class="woocommerce-Price-amount amount">
                                        <bdi>
                                            <span clas="woocommerce-Price-currencySymbol">P</span>
                                            200.00
                                        </bdi>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Amount to Capture:</td>
                                <td width="1%"></td>
                                <td class="total">
                                    <input type="text" id="capture_amount" name="capture_amount" class="wc_input_price">
                                    <div class="clear"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="clear"></div>
                    <div class="refund-actions">
                        <button type="button" class="button button-primary do-capture-amount">
                            Capture
                            <span class="wc-order-capture-amount">
                                <span class="woocommerce-Price-amount amount">
                                    <span class="woocommerce-Price-currencySymbol">P</span>
                                    200.00
                                </span>
                            </span>
                            via Payments via Paymaya
                        </button>
                        <button type="button" class="button cancel-capture-action" style="float: left; margin-left: 0;">Cancel</button>
                    </div>
                </div>
            `;

            $('#woocommerce-order-items > .inside').append(captureInterface);
            $('button.capture-items').on('click', this.capture_payment);
            $('button.cancel-capture-action').on('click', this.cancel_capture);
        },
        capture_payment: function () {
            $( 'div.wc-order-capture-items' ).slideDown();
			$( 'div.wc-order-data-row-toggle' ).not( 'div.wc-order-capture-items' ).slideUp();
			$( 'div.wc-order-totals-items' ).slideUp();

            /** Disabled usage tracking */
			// window.wcTracks.recordEvent( 'order_edit_refund_button_click', {
			// 	order_id: woocommerce_admin_meta_boxes.post_id,
			// 	status: $( '#order_status' ).val()
			// } );

			return false;
        },
        cancel_capture: function () {
            $( 'div.wc-order-data-row-toggle' ).not( 'div.wc-order-bulk-actions' ).slideUp();
			$( 'div.wc-order-bulk-actions' ).slideDown();
			$( 'div.wc-order-totals-items' ).slideDown();

            /** Disabled usage tracking */
			// window.wcTracks.recordEvent( 'order_edit_add_items_cancelled', {
			// 	order_id: woocommerce_admin_meta_boxes.post_id,
			// 	status: $( '#order_status' ).val()
			// } );

			return false;
        }
    };

    capturePanel.init();
});
