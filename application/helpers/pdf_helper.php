<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*
 * InvoicePlane
 * 
 * A free and open source web based invoicing system
 *
 * @package		InvoicePlane
 * @author		Kovah (www.kovah.de)
 * @copyright	Copyright (c) 2012 - 2015 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 * 
 */

/** Amount with a thousand separator, e.g. "Rs 10,000.00". */
function pdf_money($currency, $amount)
{
    $currency = trim((string)$currency);
    if ($currency === '') {
        $CI = &get_instance();
        $currency = trim((string)$CI->mdl_settings->setting('currency_symbol'));
    }

    return ($currency !== '' ? $currency . ' ' : '') . number_format((float)$amount, 2);
}

/** Public link to an invoice's PDF; works for any status and needs no login. */
function invoice_public_pdf_url($invoice)
{
    return $invoice->invoice_url_key
        ? site_url('guest/view/invoice_pdf/' . $invoice->invoice_url_key)
        : NULL;
}

/** QR code with the invoice's public PDF link, for the PDF templates. */
function invoice_qr_html($invoice)
{
    $url = invoice_public_pdf_url($invoice);
    if (!$url) {
        return '';
    }

    $CI = &get_instance();
    $CI->load->helper('qr');

    return '<div style="margin-top:8px;">' . qr_code_html($url, 24) . '</div>';
}

/** Whole-invoice discount in money (fixed amount and/or percent), for the PDF. */
function invoice_discount_value($invoice)
{
    $before = (float)$invoice->invoice_item_subtotal + (float)$invoice->invoice_item_tax_total
        + (float)$invoice->invoice_tax_total;

    return round($before - (float)$invoice->invoice_total, 2);
}

function generate_invoice_pdf($invoice_id, $stream = TRUE, $invoice_template = NULL,$isGuest = NULL)
{
    $CI = &get_instance();

    $CI->load->model('invoices/mdl_invoices');
    $CI->load->model('invoices/mdl_items');
    $CI->load->model('invoices/mdl_invoice_tax_rates');
    $CI->load->model('payment_methods/mdl_payment_methods');
    $CI->load->helper('country');

    $invoice = $CI->mdl_invoices->get_by_id($invoice_id);
    if (!$invoice_template) {
        $CI->load->helper('template');
        $invoice_template = select_pdf_invoice_template($invoice);
    }

    $payment_method = $CI->mdl_payment_methods->where('payment_method_id', $invoice->payment_method)->get()->row();
    if ($invoice->payment_method == 0) $payment_method = false;

    // Determine if discounts should be displayed
    $items = $CI->mdl_items->where('invoice_id', $invoice_id)->get()->result();
    $show_discounts = false;
    foreach ($items as $item) {
        if ($item->item_discount != '0.00') {
            $show_discounts = true;
        }
    }

    $data = array(
        'invoice' => $invoice,
        'invoice_tax_rates' => $CI->mdl_invoice_tax_rates->where('invoice_id', $invoice_id)->get()->result(),
        'items' => $items,
        'payment_method' => $payment_method,
        'output_type' => 'pdf',
        'show_discounts' => $show_discounts,
    );

    $html = $CI->load->view('invoice_templates/pdf/' . $invoice_template, $data, TRUE);

    $CI->load->helper('mpdf');
    return pdf_create($html, lang('invoice') . '_' . str_replace(array('\\', '/'), '_', $invoice->invoice_number), $stream, $invoice->invoice_password,1,$isGuest);
}

function generate_quote_pdf($quote_id, $stream = TRUE, $quote_template = NULL)
{
    $CI = &get_instance();

    $CI->load->model('quotes/mdl_quotes');
    $CI->load->model('quotes/mdl_quote_items');
    $CI->load->model('quotes/mdl_quote_tax_rates');
    $CI->load->helper('country');

    $quote = $CI->mdl_quotes->get_by_id($quote_id);

    if (!$quote_template) {
        $quote_template = $CI->mdl_settings->setting('pdf_quote_template');
    }

    // Determine if discounts should be displayed
    $items = $CI->mdl_quote_items->where('quote_id', $quote_id)->get()->result();
    $show_discounts = false;
    foreach ($items as $item) {
        if ($item->item_discount != '0.00') {
            $show_discounts = true;
        }
    }

    $data = array(
        'quote' => $quote,
        'quote_tax_rates' => $CI->mdl_quote_tax_rates->where('quote_id', $quote_id)->get()->result(),
        'items' => $items,
        'output_type' => 'pdf',
        'show_discounts' => $show_discounts,
    );

    $html = $CI->load->view('quote_templates/pdf/' . $quote_template, $data, TRUE);

    $CI->load->helper('mpdf');

    return pdf_create($html, 'Quotation_' . str_replace(array('\\', '/'), '_', $quote->quote_number), $stream,$quote->quote_password);
}
