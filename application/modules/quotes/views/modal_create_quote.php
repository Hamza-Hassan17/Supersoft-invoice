<script type="text/javascript">
    $(function () {
        // Display the create quote modal
        $('#create-quote').modal('show');

        $('#create-quote').on('shown', function () {
            $("#client_name").focus();
        });

        $().ready(function () {
            $("[name='client_name']").select2();
            $("#client_name").focus();
        });

        // Creates the quote
        $('#quote_create_confirm').click(function () {
            console.log('clicked');
            // Posts the data to validate and create the quote;
            // will create the new client if necessary
            $.post("<?php echo site_url('quotes/ajax/create'); ?>", {
                    client_name: $('#client_name').val(),
                    quote_date_created: $('#quote_date_created').val(),
                    quote_password: $('#quote_password').val(),
                    user_id: '<?php echo $this->session->userdata('user_id'); ?>',
                    invoice_group_id: $('#invoice_group_id').val()
                },
                function (data) {
                    <?php echo (IP_DEBUG ? 'console.log(data);' : ''); ?>
                    var response = JSON.parse(data);
                    if (response.success == '1') {
                        // The validation was successful and quote was created
                        window.location = "<?php echo site_url('quotes/view'); ?>/" + response.quote_id;
                    }
                    else {
                        // The validation was not successful
                        $('.control-group').removeClass('has-error');
                        for (var key in response.validation_errors) {
                            $('#' + key).parent().parent().addClass('has-error');
                        }
                    }
                });
        });
    });

</script>

<div id="create-quote" class="modal col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2"
     role="dialog" aria-labelledby="modal_create_quote" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <a data-dismiss="modal" class="close"><i class="fa fa-close"></i></a>

            <h3><?php echo lang('create_quote'); ?></h3>
        </div>
        <div style="text-align:center">
            <a href="#" data-toggle="modal" data-target="#myModal2" class="btn btn-primary">Add New Client</a>
        </div>
        <div class="modal-body">

            <div class="form-group">
                <label for="client_name"><?php echo lang('client'); ?></label>
                <select name="client_name" id="client_name" class="form-control" autofocus="autofocus">
                    <?php
                    foreach ($clients as $client){
                        echo "<option value='".htmlentities($client->client_name)."' ";
                        if ($client_name == $client->client_name) echo 'selected';
                        echo ">".htmlentities($client->client_name)."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group has-feedback">
                <label for="quote_date_created">
                    <?php echo lang('quote_date'); ?>
                </label>

                <div class="input-group">
                    <input name="quote_date_created" id="quote_date_created"
                           class="form-control datepicker"
                           value="<?php echo date(date_format_setting()); ?>">
										<span class="input-group-addon">
												<i class="fa fa-calendar fa-fw"></i>
										</span>
                </div>
            </div>

            <div class="form-group">
                <label for="quote_password"><?php echo lang('quote_password'); ?></label>
                <input type="text" name="quote_password" id="quote_password" class="form-control"
                       value="<?php if ($this->mdl_settings->setting('quote_pre_password') == '') {
                           echo '';
                       } else {
                           echo $this->mdl_settings->setting('quote_pre_password');
                       } ?>" style="margin: 0 auto;" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="invoice_group_id"><?php echo lang('invoice_group'); ?>: </label>

                <div class="controls">
                    <select name="invoice_group_id" id="invoice_group_id"
                            class="form-control">
                        <option value=""></option>
                        <?php foreach ($invoice_groups as $invoice_group) { ?>
                            <option value="<?php echo $invoice_group->invoice_group_id; ?>"
                                    <?php if ($this->mdl_settings->setting('default_quote_group') == $invoice_group->invoice_group_id) { ?>selected="selected"<?php } ?>><?php echo $invoice_group->invoice_group_name; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?php echo lang('cancel'); ?>
                </button>
                <button class="btn btn-success ajax-loader" id="quote_create_confirm" type="button">
                    <i class="fa fa-check"></i> <?php echo lang('submit'); ?>
                </button>
            </div>
        </div>

    </form>

</div>
<div id="myModal2" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add Client</h4>
      </div>
      <div class="modal-body">
        <!-- my work start from here -->
        <div class="col-lg-6 ">
          <label><b>Company Name</b></label>
          <span style="color:#000">
            <?php
                $form_data = array('name'=>'company_name','value'=>'','class'=>'form-control', 'id'=>'company_name','placeholder'=>'','style'=>'text-align:center;margin-top:7px');
                echo form_input($form_data);
            ?>
          </span>
        </div>

         <div class="col-lg-6 ">
          <label><b>Name</b></label>
          <span style="color:#000">
            <?php
                $form_data = array('name'=>'name','value'=>'','class'=>'form-control', 'id'=>'cli_name','placeholder'=>'','style'=>'text-align:center;margin-top:7px');
                echo form_input($form_data);
            ?>
          </span>
        </div>

       
       
        <div class="col-lg-6 ">
          <label><b>Address</b></label>
          <span style="color:#000">
            <?php
                $form_data = array('name'=>'client_address_1','value'=>'','class'=>'form-control', 'id'=>'client_address_1','placeholder'=>'','style'=>'text-align:center;margin-top:7px');
                echo form_input($form_data);
            ?>
          </span>
        </div>
        <div class="col-lg-6 ">
          <label><b>Mobile Number</b></label>
          <span style="color:#000">
            <?php
                $form_data = array('name'=>'client_mobile','value'=>'','class'=>'form-control', 'id'=>'client_mobile','placeholder'=>'','style'=>'text-align:center;margin-top:7px');
                echo form_input($form_data);
            ?>
          </span>
        </div>
        <div class="col-lg-6 ">
          <label><b>Email Address</b></label>
          <span style="color:#000">
            <?php
                $form_data = array('name'=>'client_email','value'=>'','class'=>'form-control', 'id'=>'client_email','placeholder'=>'','style'=>'text-align:center;margin-top:7px');
                echo form_input($form_data);
            ?>
          </span>
        </div>
        <!-- <a href="#" id="reset_value" data-toggle="modal" data-target="#myModal2" class="btn btn-primary" style="margin-top:10px;margin-bottom:10px">Add product</a> -->
        <a href="#" id="submit_value" class="btn btn-primary" style="margin-top:30px;margin-bottom:10px;margin-left:20px">Submit</a>
      </div>
      <div class="alert-success col-lg-8 col-lg-offset-3" id="success_m"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" id="abc">Close</button>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
    $('#abc').click(function () {
        $('#modal-placeholder').load("<?php echo site_url('quotes/ajax/modal_create_invoice'); ?>");
    });
// $datetime = date('Y-m-d H:i:s');

</script>

<script type="text/javascript">
  
$(document).ready(function(){
$("#submit_value").click(function(){
// e.preventDefault();

company_name     = $("#company_name").val();
var cli_name         = $("#cli_name").val();
client_address_1 = $("#client_address_1").val();
client_mobile    = $("#client_mobile").val();
client_email     = $("#client_email").val();
// var client ;
// var company ;
// var client_addr_1 ;
// var client_mobil  ;
// var client_emai ;
// console.log(company_name);
// console.log(cli_name);
// console.log(client_address_1);
// console.log(client_mobile);
// console.log(client_email);

$.ajax({type:"post",url:"<?php echo site_url('invoices/ajax/insert_client'); ?>",data:{client:cli_name,company:company_name,client_addr_1:client_address_1,client_mobil:client_mobile,client_emai:client_email},success: function(ajaxresult){
// $.ajax({type:"post",url:"<?php echo site_url('invoices/ajax/insert_client'); ?>",data:{client:cli_name},success: function(ajaxresult){

// if(ajaxresult == '1')
if(ajaxresult)

{
  a = ajaxresult;
  console.log(a);
  // $("#ajaxrequest_register").html(categ);
 
  var message = "Client added successfully";
  $("#success_m").html(message);

  // $("#ajaxrequest_register").append('<p class="myclass">'+categ+'</p>');
    
 //  $('#modal_hide').fadeIn('slow');
 //  $('#newslet').fadeOut('slow');

 setTimeout(function () { $('#myModal2').modal('data-dismiss')}, 2000);
  setTimeout(function() { $('#modal-placeholder').load("<?php echo site_url('invoices/ajax/modal_create_invoice'); ?>")},2500);
// $.modal.close();
 // 

 // $('#myTable tbody').append('<tr class="child"><td>'+item_nam+'</td><td>'+quanti+'</td><td>'+rat+'</td><td>'+final_amount+'</td></tr>');

 // 
}
}
});
});
});  

</script>
<script type="text/javascript">