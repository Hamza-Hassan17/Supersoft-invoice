<div class="table-responsive">
    <table id="item_table" class="items table table-condensed table-bordered">
        <thead style="display: none">
        <tr>
            <th></th>
            <th><?php echo lang('description'); ?></th>
            <!-- <th><?php echo lang('description'); ?></th> -->
            <th><?php echo lang('quantity'); ?></th>
            <th><?php echo 'Invoice Value'; ?></th>
            <!-- <th><?php echo lang('tax_rate'); ?></th> -->
            <!-- <th><?php echo lang('subtotal'); ?></th> -->
            <!-- <th><?php echo lang('tax'); ?></th> -->
            <th><?php echo lang('total'); ?></th>
            <th></th>
        </tr>
        </thead>

        <tbody id="new_row" style="display: none;">
        <tr>
            <td rowspan="2" class="td-icon"><i class="fa fa-arrows cursor-move"></i></td>
            <td class="td-text">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                <input type="hidden" name="item_id" value="">

                <div class="input-group">
                    <span class="input-group-addon"><?php echo lang('description'); ?></span>
                    <input type="text" name="item_name" id="fetch_rec" class="input-sm form-control" value="">
                </div>
            </td>
            <td class="td-amount td-quantity">
                <div class="input-group">
                    <span class="input-group-addon"><?php echo lang('quantity'); ?></span>
                    <input type="text" name="item_quantity" class="input-sm form-control amount quan_val" value="">
                </div>
            </td>
            <td class="td-amount">
                <div class="input-group">
                    <span class="input-group-addon"><?php echo 'Invoice Value'; ?></span>
                    <input type="text" name="item_price" class="input-sm form-control amount price_val" value="">
                </div>
            </td>

            <!-- <td class="td-amount ">
                <div class="input-group">
                    <span class="input-group-addon"><?php echo lang('item_discount'); ?></span>
                    <input type="text" name="item_discount_amount" class="input-sm form-control amount item_dis_val"
                           value="" data-toggle="tooltip" data-placement="bottom"
                           title="<?php echo $this->mdl_settings->setting('currency_symbol') . ' ' . lang('per_item'); ?>">
                </div>
            </td> -->
            <!-- <td class="td-amount">
                <div class="input-group">
                    <span class="input-group-addon"><?php echo lang('tax_rate'); ?></span>
                    <select name="item_tax_rate_id" name="item_tax_rate_id"
                            class="form-control input-sm">
                        <option value="0"><?php echo lang('none'); ?></option>
                        <?php foreach ($tax_rates as $tax_rate) { ?>
                            <option value="<?php echo $tax_rate->tax_rate_id; ?>">
                                <?php echo $tax_rate->tax_rate_percent . '% - ' . $tax_rate->tax_rate_name; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </td> -->
            <!-- <td class="td-icon text-right td-vert-middle"></td> -->
            <td class="td-icon text-right td-vert-middle">
                <?php if ($invoice->is_read_only != 1): ?>
                    <a href="<?php echo site_url('invoices/delete_itm/' . $invoice->invoice_id); ?>" title="<?php echo lang('delete'); ?>">
                        <i class="fa fa-trash-o text-danger"></i>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <!-- <td class="td-textarea">
                <div class="input-group">
                    <span class="input-group-addon"><?php echo lang('description'); ?></span>
                    <textarea name="item_description" class="input-sm form-control"></textarea>
                </div>
            </td> -->
            <!-- <td colspan="2" class="td-amount td-vert-middle">
                <span><?php echo lang('subtotal'); ?></span><br/>
                <span name="subtotal" class="amount"></span>
            </td> -->
            <!-- <td class="td-amount td-vert-middle">
                <span><?php echo lang('discount'); ?></span><br/>
                <span name="item_discount_total" class="amount"></span>
            </td> -->
            <!-- <td class="td-amount td-vert-middle">
                <span><?php echo lang('tax'); ?></span><br/>
                <span name="item_tax_total" class="amount"></span>
            </td> -->
            <!-- <td class="td-amount td-vert-middle">
                <span><?php echo lang('total'); ?></span><br/>
                <span name="item_total" class="amount"></span>
            </td> -->
        </tr>
        </tbody>

        <?php foreach ($items as $item) { ?>
            <tbody class="item">
            <tr>
                <td rowspan="2" class="td-icon"><i class="fa fa-arrows cursor-move"></i></td>
                <td class="td-text">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                    <input type="hidden" name="item_id" value="<?php echo $item->item_id; ?>"
                        <?php if ($invoice->is_read_only == 1) {
                            echo 'disabled="disabled"';
                        } ?>>

                    <div class="input-group">
                        <span class="input-group-addon"><?php echo lang('description'); ?></span>
                        <input type="text" name="item_name" class="input-sm form-control"
                               value="<?php echo html_escape($item->item_name); ?>"
                            <?php if ($invoice->is_read_only == 1) {
                                echo 'disabled="disabled"';
                            } ?>>
                    </div>
                </td>
                <td class="td-amount td-quantity">
                     <div class="input-group">
                        <span class="input-group-addon"><?php echo lang('quantity'); ?></span>
                        <input type="text" name="item_quantity" class="input-sm form-control amount"
                               value="<?php echo format_amount($item->item_quantity); ?>"
                            <?php if ($invoice->is_read_only == 1) {
                                echo 'disabled="disabled"';
                            } ?>>
                    </div> 
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-addon"><?php echo 'Invoice Value'; ?></span>
                        <input type="text" name="item_price" class="input-sm form-control amount"
                               value="<?php echo format_amount($item->item_price); ?>"
                            <?php if ($invoice->is_read_only == 1) {
                                echo 'disabled="disabled"';
                            } ?>>
                    </div>
                </td>
                <!--<td class="td-amount ">
                     <div class="input-group">
                        <span class="input-group-addon"><?php echo lang('item_discount'); ?></span>
                        <input type="text" name="item_discount_amount" class="input-sm form-control amount"
                               value="<?php echo format_amount($item->item_discount_amount); ?>"
                               data-toggle="tooltip" data-placement="bottom"
                               title="<?php echo $this->mdl_settings->setting('currency_symbol') . ' ' . lang('per_item'); ?>"
                            <?php if ($invoice->is_read_only == 1) {
                                echo 'disabled="disabled"';
                            } ?>>
                    </div> 
                </td>-->
               <!--  <td class="td-amount">
                   <div class="input-group">
                       <span class="input-group-addon"><?php echo lang('tax_rate'); ?></span>
                       <select name="item_tax_rate_id" name="item_tax_rate_id"
                               class="form-control input-sm"
                           <?php if ($invoice->is_read_only == 1) {
                               echo 'disabled="disabled"';
                           } ?>>
                           <option value="0"><?php echo lang('none'); ?></option>
                           <?php foreach ($tax_rates as $tax_rate) { ?>
                               <option value="<?php echo $tax_rate->tax_rate_id; ?>"
                                       <?php if ($item->item_tax_rate_id == $tax_rate->tax_rate_id) { ?>selected="selected"<?php } ?>>
                                   <?php echo $tax_rate->tax_rate_percent . '% - ' . $tax_rate->tax_rate_name; ?>
                               </option>
                           <?php } ?>
                       </select>
                   </div>
               </td> -->
                <td class="td-icon text-right td-vert-middle">
                    <?php if ($invoice->is_read_only != 1): ?>
                        <a href="<?php echo site_url('invoices/delete_item/' . $invoice->invoice_id . '/' . $item->item_id); ?>" title="<?php echo lang('delete'); ?>">
                            <i class="fa fa-trash-o text-danger"></i>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <!-- <td class="td-textarea">
                    <div class="input-group">
                        <span class="input-group-addon"><?php echo lang('description'); ?></span>
                        <textarea name="item_description"
                                  class="input-sm form-control"
                            <?php if ($invoice->is_read_only == 1) {
                                echo 'disabled="disabled"';
                            } ?>><?php echo $item->item_description; ?></textarea>
                    </div>
                </td> -->

                <!-- <td colspan="2" class="td-amount td-vert-middle">
                    <span><?php echo lang('subtotal'); ?></span><br/>
                    <span name="subtotal" class="amount">
                        <?php echo format_currency($item->item_subtotal); ?>
                    </span>
                </td> -->
                <!-- <td class="td-amount td-vert-middle">
                    <span><?php echo lang('discount'); ?></span><br/>
                    <span name="item_discount_total" class="amount">
                        <?php echo format_currency($item->item_discount); ?>
                    </span>
                </td> -->
                <!-- <td class="td-amount td-vert-middle">
                    <span><?php echo lang('tax'); ?></span><br/>
                    <span name="item_tax_total" class="amount">
                        <?php echo format_currency($item->item_tax_total); ?>
                    </span>
                </td> -->
                <!-- <td class="td-amount td-vert-middle">
                    <span><?php echo lang('total'); ?></span><br/>
                    <span name="item_total" class="amount">
                        <?php echo format_currency($item->item_total); ?>
                    </span>
                </td> -->
            </tr>
            </tbody>
        <?php } ?>

    </table>
</div>

<div class="row">
    <div class="col-xs-12 col-md-4">
        <div class="btn-group">
            <?php if ($invoice->is_read_only != 1) { ?>
                <a href="#" class="btn_add_row btn btn-sm btn-default">
                    <i class="fa fa-plus"></i> <?php echo lang('add_new_row'); ?>
                </a>
                <a href="#" class="btn_add_product btn btn-sm btn-default">
                    <i class="fa fa-database"></i>
                    <?php echo lang('add_product'); ?>
                </a>
            <?php } ?>
        </div>
        <br/><br/>
    </div>

    <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
        <table class="table table-condensed text-right">
           <!--  <tr>
               <td style="width: 40%;"><?php echo lang('subtotal'); ?></td>
               <td style="width: 60%;"
                   class="amount val_total"><?php echo format_currency($invoice->invoice_item_subtotal); ?></td>
           </tr>
           <tr>
               <td><?php echo lang('item_tax'); ?></td>
               <td class="amount"><?php echo format_currency($invoice->invoice_item_tax_total); ?></td>
           </tr>
           <tr>
               <td><?php echo lang('invoice_tax'); ?></td>
               <td>
                   <?php if ($invoice_tax_rates) {
                       foreach ($invoice_tax_rates as $invoice_tax_rate) { ?>
                           <span class="text-muted">
                           <?php echo anchor('invoices/delete_invoice_tax/' . $invoice->invoice_id . '/' . $invoice_tax_rate->invoice_tax_rate_id, '<i class="fa fa-trash-o"></i>');
                           echo ' ' . $invoice_tax_rate->invoice_tax_rate_name . ' ' . $invoice_tax_rate->invoice_tax_rate_percent; ?>
                               %</span>&nbsp;
                           <span class="amount">
                               <?php echo format_currency($invoice_tax_rate->invoice_tax_rate_amount); ?>
                           </span>
                       <?php }
                   } else {
                       echo format_currency('0');
                   } ?>
               </td>
           </tr>
           <tr>
               <td class="td-vert-middle"><?php echo lang('discount'); ?></td>
               <td class="clearfix">
                   <div class="discount-field">
                       <div class="input-group input-group-sm">
                           <input id="invoice_discount_amount" name="invoice_discount_amount"
                                  class="discount-option form-control input-sm amount show_dis_val"
                                  value="<?php echo($invoice->invoice_discount_amount != 0 ? $invoice->invoice_discount_amount : ''); ?>"
                               <?php if ($invoice->is_read_only == 1) {
                               echo 'disabled="disabled"';
                           } ?>>
           
                           <div
                               class="input-group-addon"><?php echo $this->mdl_settings->setting('currency_symbol'); ?></div>
                       </div>
                   </div>
                   <div class="discount-field">
                       <div class="input-group input-group-sm">
                           <input id="invoice_discount_percent" name="invoice_discount_percent"
                                  value="<?php echo($invoice->invoice_discount_percent != 0 ? $invoice->invoice_discount_percent : ''); ?>"
                                  class="discount-option form-control input-sm amount per_dis_val"
                               <?php if ($invoice->is_read_only == 1) {
                                   echo 'disabled="disabled"';
                               } ?>>
                           <div class="input-group-addon">&percnt;</div>
                       </div>
                   </div>
               </td>
           </tr> -->
            <tr>
                <td><?php echo lang('total'); ?></td>
                <td class="amount val_total add_curr_symbol"><b class="symbol"><?php echo"$invoice->amount_curre"; ?></b> <b><?php echo $invoice->invoice_total; ?></b></td>
            </tr>
            <tr>
                <td><?php echo lang('paid'); ?></td>
                <!-- <td class="amount"><b><?php echo format_currency($invoice->invoice_paid); ?></b></td> -->
                <!-- <td class="amount"><b><input class="val_total1" type="text" value="<?php echo format_currency($invoice->invoice_balance); ?>"></b></td> -->
                <td class="amount"><b><input class="val_total1" type="text" value="<?php echo $invoice->invoice_paid; ?>"></b></td>
                <input type="hidden" name="" class="hide_val_total1" value="<?php echo $item->item_id; ?>">
            </tr>
            <tr>
                <td><b><?php echo lang('balance'); ?></b></td>
                <!-- <td class="amount"><b><?php echo format_currency($invoice->invoice_balance); ?></b></td> -->
                <?php if ($invoice->is_read_only == 1) { ?>
                <td class="amount"><b><input class="balan_val" type="text" value="<?php echo $invoice->invoice_balance; ?>"></b></td>
                <?php } else { ?>
                  <td class="amount add_curr_symbol val_total4"><b class="symbol"><?php echo"$invoice->amount_curre";  ?></b> <b><?php echo $invoice->invoice_balance; ?></b></td>
                <?php } ?>
            </tr>
        </table>
        <?php if ($invoice->is_read_only == 1) { ?>
        <button style="float:right" type="button" class="btn update_amount">Update</button> 
        <?php } ?>
    </div>
</div>
<input class="val_total2" type="hidden" name="abc" value="<?php echo "$invoice->temp_amount";?>">
<!--<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js" type="text/javascript"></script>-->
<!-- <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js" type="text/javascript"></script>  -->







        <script type="text/javascript">
 $("#add_value").click(function () 
 {
   var a =  $('#id').val();
   $("#after_div_insert").append('<p class="myclass">'+a+'</p>');
    // $('#show').text(a);
    console.log(a);
    // $("#hide").fadeIn("slow");
   
} ); 
</script>

<script type="text/javascript">
  $(document).ready(function(){
   
    // var a ;
    // var b  ;
    /*$(".quan_val").change(function(){
      a = this.value;
      console.log(a);
    });*/
    // $("input[name=item_price]").val();

    /*$(document).on('click', "a.tabclick", function() {
        var liId = $(this).parent("li").attr("id");
        alert(liId);        
    });*/
    $("#amount_curr").change(function () {
           var end = this.value;
           dis_amo = $('.val_total b:first').html('');
           dis_amo = $('.val_total4 b:first').html('');
           dis_amo = $('.val_total b:first').text();
         
          $(".val_total b:first").text(end + ' ' + dis_amo);
          $(".val_total4 b:first").text(end + ' ' + dis_amo);

           console.log(dis_amo);
       });


    $(document).on('change',"input[name=item_price]",function(){
      b = this.value;
      a = $(this).closest('td').prev('td').find('.quan_val').val();
      c = a*b;
      if($('.hide_val_total1').val() == '')
      {
      dis_amo = $('.val_total b:last').text();
      // console.log(dis_amo);
     /* item_box_amo1 = dis_amo.indexOf("£");
      item_box_amo2 = (parseInt(item_box_amo1) + 1);
      item_box_amo3 = (parseInt(item_box_amo2) + 3);
      final_box_amo = dis_amo.substring(item_box_amo2, item_box_amo3);*/
     
        count_number1 = parseInt(c,10) + parseInt(dis_amo,10);
       
        // console.log(count_number1);
         // $(".val_total").html('&pound'+count_number1+'.00');
         $(".val_total b:last").html(count_number1+'.00');
         
         $(".val_total4 b:last").text('');
         $(".val_total4 b:last").html(count_number1+'.00');
      }
      else
      {
            dis_amo = $('.val_total b:last').text();
            count_number1 = parseInt(c,10) + parseInt(dis_amo,10);
            $(".val_total b:last").html(count_number1+'.00');

            dis_amo = $(".val_total4 b:last").text();
            $(".val_total4 b:last").text('');
            count_number1 = parseInt(c,10) + parseInt(dis_amo,10);

            $(".val_total4 b:last").html(count_number1+'.00');
      }
       // $(".val_total1").val(count_number1+'.00');
       
       // $(".val_total4 b:first").html(count_number1+'.00');
    });

    incr = 1;
    $(".val_total1").change(function()
    {
      paid_amoun = $(".val_total1").val();
      if($(".hide_val_total1").val() == "")
      {
        bala_amoun = $('.val_total b:last').text();
      }
      else
      {
        if(incr == 1)
        {
           bala_amoun = $(".val_total4 b:last").text();
           new_bala_amoun = bala_amoun;
        }
        else
        {
           bala_amoun = new_bala_amoun;
        }
      }
      ++incr;
      count_number1 = parseInt(bala_amoun,10) - parseInt(paid_amoun,10) ;
      $(".val_total4 b:last").text('');
      $(".val_total4 b:last").html(count_number1+'.00');
    });
    
    // $('.item_dis_val').keyup(function() {
    //  x = $(this).val();
    //  total_value = c-x;
    //   $('.val_total').html(total_value);
    //   dis_amo = $('.val_total').text();
    // });


    //  $('.per_dis_val').keyup(function() {
    // console.log( dis_val = $(this).val());
    // console.log( dis_val1 = (dis_amo/100)*dis_val);
    // console.log( dis_val2 = dis_amo - dis_val1 );
    //   $('.show_dis_val').val(dis_val2);
    //    $('.val_total').html(dis_val2);

    // });
  });
</script>
<script type="text/javascript">
  
$(document).ready(function(){
$(".update_amount").click(function(){

paid_amoun     = $(".val_total1").val();
bala_amoun     = $(".balan_val").val();
invoice_id     = <?php echo $invoice_id; ?>;

$.ajax({type:"post",url:"<?php echo site_url('invoices/ajax/update_amo'); ?>",data:{pai_amoun:paid_amoun,bal_amoun:bala_amoun,invoic_id:invoice_id},success: function(ajaxresult){

if(ajaxresult)

{
  window.location = "<?php echo site_url('invoices/view'); ?>/" + <?php echo $invoice_id; ?>;
 /* a = ajaxresult;
  console.log(a);
 
  var message = "Client added successfully";
  $("#success_m").html(message);

  setTimeout(function () { $('#myModal').modal('data-dismiss')}, 2000);
  setTimeout(function() { $('#modal-placeholder').load("<?php echo site_url('invoices/ajax/modal_create_invoice'); ?>")},2500);
*/ 
}
}
});
});
});  

</script>

