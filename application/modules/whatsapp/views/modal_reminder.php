<?php $esc = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>

<div id="modal_whatsapp_reminder" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title">
                    Payment reminder<?php if ($invoice) { echo ' to ' . $esc($invoice->client_name); } ?>
                </h3>
            </div>

            <?php if ($invoice && $number) { ?>
                <div class="modal-body">
                    <p class="text-muted">
                        <?php echo $connected
                            ? 'Edit the message if needed, then send it from the connected WhatsApp number.'
                            : "WhatsApp isn't connected, so this opens on your own WhatsApp instead."; ?>
                    </p>
                    <div id="whatsapp_reminder_error" class="alert alert-danger" style="display:none"></div>
                    <textarea id="whatsapp_reminder_text" class="form-control" rows="14"><?php echo $esc($text); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" id="whatsapp_reminder_submit" class="btn btn-success">
                        <i class="fa fa-whatsapp"></i>
                        <?php echo $connected ? 'Send now' : 'Open in WhatsApp'; ?>
                    </button>
                </div>
            <?php } else { ?>
                <div class="modal-body">
                    <p class="mb-0">
                        <?php echo $invoice
                            ? 'This client has no valid mobile number.'
                            : 'This invoice has no balance due.'; ?>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $('#modal_whatsapp_reminder').modal('show');

        <?php if ($invoice && $number) { ?>
        var showError = function (message) {
            $('#whatsapp_reminder_error').text(message).show();
        };

        $('#whatsapp_reminder_submit').click(function () {
            var button = $(this);
            var text = $('#whatsapp_reminder_text').val();

            <?php if (!$connected) { ?>
            window.open('https://wa.me/<?php echo $esc($number); ?>?text=' + encodeURIComponent(text), '_blank', 'noopener');
            $('#modal_whatsapp_reminder').modal('hide');
            <?php } else { ?>
            button.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: "<?php echo site_url('whatsapp/ajax/send_reminder'); ?>",
                data: {invoice_id: <?php echo (int)$invoice->invoice_id; ?>, text: text},
                dataType: 'json'
            }).done(function (response) {
                if (response.error) {
                    showError(response.error);
                    button.prop('disabled', false);
                } else {
                    $('#modal_whatsapp_reminder').modal('hide');
                    alert('Payment reminder sent.');
                }
            }).fail(function () {
                showError('The message could not be sent. Please check you are still logged in.');
                button.prop('disabled', false);
            });
            <?php } ?>
        });
        <?php } ?>
    });
</script>
