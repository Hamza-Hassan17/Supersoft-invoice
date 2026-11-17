<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Emails the client once per invoice, the first time it is saved with a total
 * above 0. Uses the same wording as the WhatsApp messages, with the PDF attached.
 *
 * Needs the ip_invoices.invoice_email_sent column (see sql/invoice_email_sent.sql);
 * without it, nothing is sent. Returns TRUE if sent, FALSE if sending failed,
 * NULL if there was nothing to send.
 */
function invoice_auto_email($invoice_id)
{
    $CI = &get_instance();

    if (!$CI->db->field_exists('invoice_email_sent', 'ip_invoices')) {
        return NULL;
    }

    $CI->load->helper('mailer');
    if (!mailer_configured()) {
        return NULL;
    }

    $CI->load->model('invoices/mdl_invoices');
    $invoice = $CI->mdl_invoices->get_by_id($invoice_id);

    if (!$invoice || $invoice->invoice_email_sent || !whatsapp_message_type($invoice)) {
        return NULL;
    }

    $to = trim((string)$invoice->client_email);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return NULL;
    }

    // Claim the invoice first so two quick saves can't both send.
    $CI->db->where('invoice_id', $invoice_id)->where('invoice_email_sent', 0)
        ->update('ip_invoices', array('invoice_email_sent' => 1));
    if ($CI->db->affected_rows() != 1) {
        return NULL;
    }

    list($subject, $html) = invoice_email_content(whatsapp_message_text($invoice));

    $pdf = whatsapp_invoice_pdf($invoice_id);
    $attachments = $pdf ? array(array(
        'path' => $pdf,
        'filename' => 'Invoice_' . preg_replace('/[^\w-]+/', '_', $invoice->invoice_number) . '.pdf',
    )) : NULL;

    $from = filter_var($invoice->user_email, FILTER_VALIDATE_EMAIL)
        ? array($invoice->user_email, $invoice->user_name)
        : $CI->mdl_settings->setting('smtp_username');

    $CI->load->helper('mailer/phpmailer');
    $sent = phpmail_send($from, $to, $subject, $html, NULL, NULL, NULL, $attachments);

    if (!$sent) {
        // Let the next save try again.
        $CI->db->where('invoice_id', $invoice_id)->update('ip_invoices', array('invoice_email_sent' => 0));
    }

    return $sent;
}

/**
 * Turns a WhatsApp-style message into an email: the first line becomes the
 * subject, *bold* becomes <b>bold</b>, and line breaks are kept.
 */
function invoice_email_content($text)
{
    $lines = explode("\n", $text);
    $subject = trim(str_replace('*', '', array_shift($lines)));

    $body = htmlspecialchars(trim(implode("\n", $lines)), ENT_QUOTES, 'UTF-8');
    $body = preg_replace('/\*([^*\n]+)\*/', '<b>$1</b>', $body);

    return array($subject, '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;">' . nl2br($body) . '</div>');
}
