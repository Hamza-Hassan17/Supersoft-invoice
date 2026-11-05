<div id="headerbar">
    <h1><i class="fa fa-whatsapp" style="color:#25d366"></i> WhatsApp</h1>
</div>

<div id="content">
    <div class="panel panel-default text-center" style="max-width:520px">
        <div class="panel-body">
            <div id="wa_message" class="alert" style="display:none"></div>

            <h4 id="wa_title">Checking WhatsApp...</h4>
            <p id="wa_detail" class="text-muted"></p>

            <img id="wa_qr" alt="WhatsApp QR code" width="320" height="320" style="display:none;max-width:100%">
            <p id="wa_qr_help" class="text-muted" style="display:none;margin-top:15px">
                On the phone: WhatsApp &rarr; Settings &rarr; Linked Devices &rarr; Link a Device, then scan this code.
            </p>

            <div id="wa_connected" style="display:none">
                <div class="form-inline" style="margin-bottom:15px">
                    <input type="text" id="wa_test_number" class="form-control" placeholder="03001234567">
                    <button type="button" id="wa_send_test" class="btn btn-primary">Send test</button>
                </div>
                <button type="button" id="wa_disconnect" class="btn btn-danger">Disconnect</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var message = function (text, ok) {
            $('#wa_message').attr('class', 'alert ' + (ok ? 'alert-success' : 'alert-danger')).text(text).show();
        };

        var post = function (action, data, success) {
            $.ajax({
                type: 'POST',
                url: "<?php echo site_url('whatsapp/ajax'); ?>/" + action,
                data: data,
                dataType: 'json'
            }).done(function (response) {
                if (response.error) {
                    message(response.error, false);
                } else {
                    success();
                }
            }).fail(function () {
                message('The request failed. Please check you are still logged in.', false);
            });
        };

        var show = function (status) {
            var connected = status.state === 'connected';
            var qr = !connected && status.qr;
            var title = 'Connecting...';
            var detail = status.error || 'The code will appear here shortly.';

            if (!status.configured) {
                title = "WhatsApp isn't set up on this site yet";
                detail = 'Add application/config/whatsapp.php.';
            } else if (connected) {
                title = 'Connected';
                detail = 'Sending from ' + status.number;
            } else if (qr) {
                title = 'Connect WhatsApp';
                detail = '';
            } else if (status.state === 'offline') {
                title = 'The WhatsApp service is offline';
            }

            $('#wa_title').text(title);
            $('#wa_detail').text(detail);
            $('#wa_connected').toggle(connected);
            $('#wa_qr_help').toggle(!!qr);
            $('#wa_qr').toggle(!!qr);
            if (qr && $('#wa_qr').attr('src') !== qr) {
                $('#wa_qr').attr('src', qr);
            }
        };

        var refresh = function () {
            $.ajax({url: "<?php echo site_url('whatsapp/ajax/status'); ?>", dataType: 'json'})
                .done(show)
                .fail(function () {
                    $('#wa_title').text('Could not check WhatsApp');
                    $('#wa_detail').text('Please reload the page and check you are still logged in.');
                })
                .always(function () {
                    setTimeout(refresh, 5000);
                });
        };

        $('#wa_send_test').click(function () {
            post('send_test', {number: $('#wa_test_number').val()}, function () {
                message('Test message sent.', true);
            });
        });

        $('#wa_disconnect').click(function () {
            if (confirm('Disconnect this WhatsApp number?')) {
                post('disconnect', {}, function () {
                    message('WhatsApp disconnected. Scan the new code to connect again.', true);
                });
            }
        });

        refresh();
    });
</script>
