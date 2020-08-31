jQuery(document).ready(function ($) {
    const {
        order_id: orderId,
        amount_authorized: amountAuthorized,
        amount_captured: amountCaptured,
    } = cynder_paymaya_order;

    let amountRemaining = 0;
    const _amountAuthorized = Number(amountAuthorized);
    const _amountCaptured = Number(amountCaptured);

    if (_amountAuthorized > _amountCaptured) {
        amountRemaining = _amountAuthorized - _amountCaptured;
    }

    const capturePanel = {
        init: function () {
            const captureInterface = `
                <div class="wc-order-data-row wc-order-capture-items wc-order-data-row-toggle" style="display: none;">
                    <table class="wc-order-totals">
                        <tbody id="capture-items-table-body">
                            <tr>
                                <td class="label">Authorized total:</td>
                                <td width="1%"></td>
                                <td class="total total-authorized">
                                    <span class="woocommerce-Price-amount amount"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Amount already captured:</td>
                                <td width="1%"></td>
                                <td class="total total-captured">
                                    <span class="woocommerce-Price-amount amount"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Remaining order total:</td>
                                <td width="1%"></td>
                                <td class="total total-remaining">
                                    <span class="woocommerce-Price-amount amount"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Amount to Capture:</td>
                                <td width="1%"></td>
                                <td class="total">
                                    <input type="text" id="capture_amount" name="capture_amount" class="wc_input_price" min="1" required>
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
                                <span class="woocommerce-Price-amount amount"></span>
                            </span>
                        </button>
                        <button type="button" class="button cancel-capture-action" style="float: left; margin-left: 0;">Cancel</button>
                    </div>
                </div>
            `;

            $('#woocommerce-order-items > .inside').append(captureInterface);
            $('button.capture-items').on('click', this.capture_payment);
            $('button.cancel-capture-action').on('click', this.cancel_capture);
            $('input#capture_amount').on('keyup change', this.update_capture_value);
            $('button.do-capture-amount').on('click', this.do_capture_amount);
            $('td.total-authorized > .amount').text(
                accounting.formatMoney(
                    amountAuthorized,
                    {
                        symbol: woocommerce_admin_meta_boxes.currency_format_symbol,
                        decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
                        thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
                        precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
                        format: woocommerce_admin_meta_boxes.currency_format
                    }
                )
            );

            $('td.total-captured > .amount').text(
                accounting.formatMoney(
                    amountCaptured,
                    {
                        symbol: woocommerce_admin_meta_boxes.currency_format_symbol,
                        decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
                        thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
                        precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
                        format: woocommerce_admin_meta_boxes.currency_format
                    }
                )
            );

            $('td.total-remaining > .amount').text(
                accounting.formatMoney(
                    amountRemaining,
                    {
                        symbol: woocommerce_admin_meta_boxes.currency_format_symbol,
                        decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
                        thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
                        precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
                        format: woocommerce_admin_meta_boxes.currency_format
                    }
                )
            );


            $('#capture_amount').attr('max', amountRemaining);
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
        },
        update_capture_value: function () {
            let value = accounting.unformat($(this).val() || '0', woocommerce_admin.mon_decimal_point);

            $('span.wc-order-capture-amount > .amount').text(
                accounting.formatMoney(
                    value,
                    {
                        symbol: woocommerce_admin_meta_boxes.currency_format_symbol,
                        decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
                        thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
                        precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
                        format: woocommerce_admin_meta_boxes.currency_format
                    }
                )
            );
        },
        do_capture_amount: function () {
            const captureAmount = Number($('#capture_amount').val() || 0);

            capturePanel.remove_capture_input_error();

            if (captureAmount === 0) {
                const minimumAmount = accounting.formatMoney(
                    1,
                    {
                        symbol: woocommerce_admin_meta_boxes.currency_format_symbol,
                        decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
                        thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
                        precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
                        format: woocommerce_admin_meta_boxes.currency_format
                    }
                );
                capturePanel.add_capture_input_error(`Amount to capture must at least be ${minimumAmount}`);
                return;
            }

            capturePanel.block();

            if (window.confirm('Are you sure you want to capture this payment?')) {
                const data = {
                    action: 'capture_payment',
                    order_id: orderId,
                    capture_amount: Number(captureAmount),
                };

                $.ajax({
                    url: woocommerce_admin_meta_boxes.ajax_url,
                    data,
                    type: 'POST',
                })
                .done(response => {
                    if (response.success) {
                        window.location.reload();
                    }
                })
                .fail((jqXhr, textStatus, err) => console.log(err))
                .always(() => {
                    capturePanel.unblock();
                });
            } else {
                capturePanel.unblock();
            }
        },
        block: function () {
            $( '#woocommerce-order-items' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
        },
        unblock: function () {
            $( '#woocommerce-order-items' ).unblock();
        },
        add_capture_input_error: function (errorMessage) {
            $('#capture_amount').css('border-color', 'red');
            $('#capture-items-table-body').append(`
                <tr id="capture-input-error">
                    <td colspan="3">
                        <span style="color: red;">
                            ${errorMessage}
                        </span>
                    </td>
                </tr>
            `);
        },
        remove_capture_input_error: function() {
            $('#capture_amount').css('border-color', 'black');
            $('#capture-input-error').remove();
        }
    };

    capturePanel.init();
});
