</table>
    <table class="wc-order-totals" style="border-top: 1px solid #999; margin-top:12px; padding-top:12px">
        <tr>
            <td class="label">Authorization Type:</td>
            <td width="1%"></td>
            <td class="total" style="font-weight: 700;"><?php echo strtoupper($authorizationType) ?></td>
        </tr>
        <tr>
            <td class="label">Amount already captured:</td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price($capturedAmount, $order->get_currency()) ?></td>
        </tr>
        <tr>
            <td class="label">Remaining order total:</td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price($balance, $order->get_currency()) ?></td>
        </tr>
    </table>
</div>
<div class="wc-order-data-row wc-order-capture-items wc-order-data-row-toggle" style="display: none;">
    <table class="wc-order-totals">
        <tbody id="capture-items-table-body">
            <tr>
                <td class="label">Authorized total:</td>
                <td width="1%"></td>
                <td class="total total-authorized">
                    <span class="woocommerce-Price-amount amount"><?php echo wc_price($authorizedAmount, $order->get_currency()) ?></span>
                </td>
            </tr>
            <tr>
                <td class="label">Amount already captured:</td>
                <td width="1%"></td>
                <td class="total total-captured">
                    <span class="woocommerce-Price-amount amount"><?php echo wc_price($capturedAmount, $order->get_currency()) ?></span>
                </td>
            </tr>
            <tr>
                <td class="label">Remaining order total:</td>
                <td width="1%"></td>
                <td class="total total-remaining">
                    <span class="woocommerce-Price-amount amount"><?php echo wc_price($balance, $order->get_currency()) ?></span>
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
<div class="wc-order-data-row" style="display: none;">
    <table>