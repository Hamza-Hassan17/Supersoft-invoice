<div id="headerbar">

    <h1><?php echo lang('invoices'); ?></h1>
     <div class="pull-left">
        <ul class="nav nav-pills index-options">
            <li><a href="<?php echo site_url('invoices/status/filter2'); ?>"><?php echo "Sort By Invoice No DESC" ?></a></li>
            <li><a href="<?php echo site_url('invoices/status/filter3'); ?>"><?php echo "Sort By Invoice No ASC" ?></a></li>
            <li><a href="<?php echo site_url('invoices/status/filter'); ?>"><?php echo "Sort By Name" ?></a></li>
            <li><a href="<?php echo site_url('invoices/status/filter1'); ?>"><?php echo "Sort By Date" ?></a></li>
        </ul>
    </div>

    <div class="pull-right">
        <button type="button" class="btn btn-default btn-sm submenu-toggle hidden-lg"
                data-toggle="collapse" data-target="#ip-submenu-collapse">
            <i class="fa fa-bars"></i> <?php echo lang('submenu'); ?>
        </button>
        <a class="create-invoice btn btn-sm btn-primary" href="#">
            <i class="fa fa-plus"></i> <?php echo lang('new'); ?>
        </a>
    </div>

    <div class="pull-right visible-lg">
        <?php echo pager(site_url('invoices/status/' . $this->uri->segment(3)), 'mdl_invoices'); ?>
    </div>

    <div class="pull-right visible-lg">
        <ul class="nav nav-pills index-options">
            <li <?php if ($status == 'all') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/all'); ?>"><?php echo lang('all'); ?></a></li>
            <li <?php if ($status == 'draft') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/draft'); ?>"><?php echo lang('draft'); ?></a></li>
            <li <?php if ($status == 'sent') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/sent'); ?>"><?php echo lang('sent'); ?></a></li>
            <li <?php if ($status == 'viewed') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/viewed'); ?>"><?php echo lang('viewed'); ?></a></li>
            <li <?php if ($status == 'paid') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/paid'); ?>"><?php echo lang('paid'); ?></a></li>
            <li <?php if ($status == 'overdue') { ?>class="active"<?php } ?>><a
                    href="<?php echo site_url('invoices/status/overdue'); ?>"><?php echo lang('overdue'); ?></a></li>
        </ul>
    </div>

</div>

<div id="submenu">
    <div class="collapse clearfix" id="ip-submenu-collapse">

        <div class="submenu-row">
            <?php echo pager(site_url('invoices/status/' . $this->uri->segment(3)), 'mdl_invoices'); ?>
        </div>

        <div class="submenu-row">
            <ul class="nav nav-pills index-options">
                <li <?php if ($status == 'all') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/all'); ?>"><?php echo lang('all'); ?></a></li>
                <li <?php if ($status == 'draft') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/draft'); ?>"><?php echo lang('draft'); ?></a></li>
                <li <?php if ($status == 'sent') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/sent'); ?>"><?php echo lang('sent'); ?></a></li>
                <li <?php if ($status == 'viewed') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/viewed'); ?>"><?php echo lang('viewed'); ?></a></li>
                <li <?php if ($status == 'paid') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/paid'); ?>"><?php echo lang('paid'); ?></a></li>
                <li <?php if ($status == 'overdue') { ?>class="active"<?php } ?>><a
                        href="<?php echo site_url('invoices/status/overdue'); ?>"><?php echo lang('overdue'); ?></a>
                </li>
            </ul>
        </div>

    </div>
</div>

<div id="content" class="table-content">

    <div id="filter_results">
        <?php $this->layout->load_view('invoices/partial_invoice_table', array('invoices' => $invoices)); ?>
    </div>

</div>