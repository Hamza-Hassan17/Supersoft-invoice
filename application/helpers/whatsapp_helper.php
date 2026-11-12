<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Talks to whatsapp-service/ and builds payment reminders.
 * Service failures come back as an 'error' string and never stop the page.
 */

function whatsapp_config()
{
    static $config = NULL;

    if ($config === NULL) {
        $config = array();
        $file = APPPATH . 'config/whatsapp.php';
        if (file_exists($file)) {
            include $file;
        }
    }

    return $config;
}

function whatsapp_configured()
{
    $config = whatsapp_config();

    return !empty($config['whatsapp_service_url'])
        && isset($config['whatsapp_token'])
        && strlen($config['whatsapp_token']) >= 32;
}

function whatsapp_call($method, $path, $data = NULL, $timeout = 45)
{
    if (!whatsapp_configured()) {
        return array('error' => 'WhatsApp is not set up on this site.');
    }
    if (!function_exists('curl_init')) {
        return array('error' => 'The PHP cURL extension is required for WhatsApp.');
    }

    $config = whatsapp_config();
    $curl = curl_init(rtrim($config['whatsapp_service_url'], '/') . '/' . $path);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    // Connecting can take over 10s on this host, and the Node app can take 20s to wake.
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'X-Token: ' . $config['whatsapp_token'],
        'Content-Type: application/json',
    ));

    if ($method === 'post') {
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data ? $data : new stdClass()));
    }

    $body = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($body === FALSE) {
        return array('error' => 'The WhatsApp service could not be reached.');
    }

    $response = json_decode($body, TRUE);

    return is_array($response)
        ? $response
        : array('error' => 'The WhatsApp service gave no answer (HTTP ' . $code . ').');
}

function whatsapp_status()
{
    return whatsapp_call('get', 'status') + array('state' => 'offline', 'number' => NULL, 'qr' => NULL);
}

/** $pdf_path is optional: that PDF is sent right after the text. */
function whatsapp_send($number, $text, $pdf_path = NULL, $pdf_name = 'Invoice.pdf')
{
    $data = array('to' => $number, 'text' => $text);

    if ($pdf_path) {
        $pdf = @file_get_contents($pdf_path);
        if ($pdf === FALSE || substr($pdf, 0, 5) !== '%PDF-') {
            return array('error' => 'The invoice PDF could not be created, so nothing was sent.');
        }
        $data['document'] = array('data' => base64_encode($pdf), 'fileName' => $pdf_name);
    }

    // Uploading a PDF to WhatsApp can take a while.
    return whatsapp_call('post', 'send', $data, $pdf_path ? 120 : 45);
}

/** Builds the invoice PDF the same way emailing an invoice does. Returns its path or NULL. */
function whatsapp_invoice_pdf($invoice_id)
{
    $CI = &get_instance();
    $CI->load->helper('pdf');

    try {
        ob_start();
        $path = generate_invoice_pdf($invoice_id, FALSE);
        ob_end_clean();
    } catch (Exception $e) {
        ob_end_clean();
        return NULL;
    } catch (Error $e) {
        ob_end_clean();
        return NULL;
    }

    return ($path && is_file($path)) ? $path : NULL;
}

function whatsapp_logout()
{
    return whatsapp_call('post', 'logout');
}

/**
 * Pakistani numbers become 923001234567. Numbers written with another country
 * code (+44..., 0044...) are kept. Returns NULL when the number is unusable.
 */
function whatsapp_number($phone)
{
    $raw = trim((string)$phone);
    $digits = preg_replace('/\D+/', '', $raw);

    if (substr($digits, 0, 2) === '00') {
        $digits = substr($digits, 2);
        $international = TRUE;
    } else {
        $international = substr($raw, 0, 1) === '+';
    }

    if ($international && substr($digits, 0, 2) !== '92') {
        return preg_match('/^[1-9]\d{9,14}$/', $digits) ? $digits : NULL;
    }

    if (substr($digits, 0, 2) === '03' && strlen($digits) === 11) {
        $digits = '92' . substr($digits, 1);
    } elseif (substr($digits, 0, 1) === '3' && strlen($digits) === 10) {
        $digits = '92' . $digits;
    }

    return preg_match('/^92\d{10}$/', $digits) ? $digits : NULL;
}

function whatsapp_invoice_number($invoice)
{
    $number = whatsapp_number($invoice->client_mobile);

    return $number ? $number : whatsapp_number($invoice->client_phone);
}

function whatsapp_currency($invoice)
{
    $currency = trim((string)$invoice->amount_curre);

    return ($currency === '' || $currency === 'Rs') ? 'PKR' : $currency;
}

function whatsapp_amount($invoice, $amount)
{
    $amount = (float)$amount;

    return whatsapp_currency($invoice) . ' ' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

/** Business name and phone for messages; the phone is shown as 0308-2595128. */
function whatsapp_business()
{
    $config = whatsapp_config();
    $name = !empty($config['whatsapp_business_name']) ? $config['whatsapp_business_name'] : 'Supersoft Technologies';
    $phone = !empty($config['whatsapp_business_phone']) ? $config['whatsapp_business_phone'] : '03082595128';

    if (preg_match('/^(03\d{2})(\d{7})$/', preg_replace('/\D+/', '', $phone), $m)) {
        $phone = $m[1] . '-' . $m[2];
    }

    return array($name, $phone);
}

/** 'reminder' when a balance is due, 'paid' when cleared, NULL for empty or credit invoices. */
function whatsapp_message_type($invoice)
{
    if ((float)$invoice->invoice_balance > 0) {
        return 'reminder';
    }

    return (float)$invoice->invoice_total > 0 ? 'paid' : NULL;
}

function whatsapp_message_text($invoice)
{
    return whatsapp_message_type($invoice) === 'paid'
        ? whatsapp_paid_text($invoice)
        : whatsapp_reminder_text($invoice);
}

// WhatsApp formatting: *bold*. Emoji are written as escapes to keep this file plain ASCII.

function whatsapp_signature($business, $phone)
{
    return "Best regards,\n*Accounts Department*\n*" . $business . "*\n\u{1F4DE} " . $phone;
}

function whatsapp_paid_text($invoice)
{
    list($business, $phone) = whatsapp_business();
    $number = $invoice->invoice_number;
    $date = date('d F Y', strtotime($invoice->invoice_date_created));

    return "*Payment Confirmation \u{2013} Invoice #" . $number . "*\n\n"
        . 'Dear ' . trim($invoice->client_name) . ",\n\n"
        . 'Greetings from *' . $business . "!*\n\n"
        . 'We are pleased to confirm that the payment for *Invoice #' . $number . '*, dated ' . $date
        . ", has been received in full.\n\n"
        . "*Invoice Details:*\n"
        . '- *Total Invoice Amount:* ' . whatsapp_amount($invoice, $invoice->invoice_total) . "\n"
        . '- *Total Payment Received:* ' . whatsapp_amount($invoice, $invoice->invoice_paid) . "\n"
        . '- *Outstanding Balance:* ' . whatsapp_currency($invoice) . " 0.00\n"
        . "- *Payment Status:* PAID IN FULL \u{2705}\n\n"
        . 'Thank you for your payment and continued trust in ' . $business . ".\n\n"
        . "We truly appreciate your business and look forward to serving you again.\n\n"
        . whatsapp_signature($business, $phone);
}

function whatsapp_reminder_text($invoice)
{
    list($business, $phone) = whatsapp_business();
    $number = $invoice->invoice_number;
    $date = date('d F Y', strtotime($invoice->invoice_date_created));
    $balance = whatsapp_amount($invoice, $invoice->invoice_balance);

    return "*Payment Reminder \u{2013} Outstanding Balance | Invoice #" . $number . "*\n\n"
        . 'Dear ' . trim($invoice->client_name) . ",\n\n"
        . 'Greetings from *' . $business . "!*\n\n"
        . 'This is a gentle reminder regarding the outstanding payment against Invoice #' . $number
        . ', dated ' . $date . ".\n\n"
        . "*Invoice Details:*\n"
        . '- *Total Invoice Amount:* ' . whatsapp_amount($invoice, $invoice->invoice_total) . "\n"
        . '- *Payment Received:* ' . whatsapp_amount($invoice, $invoice->invoice_paid) . "\n"
        . '- *Outstanding Balance:* ' . $balance . "\n\n"
        . 'We kindly request you to arrange payment of the remaining *' . $balance . "* at your earliest convenience.\n\n"
        . 'If the payment has already been processed, please disregard this reminder and share the payment confirmation for our records.' . "\n\n"
        . 'For any queries or assistance, please feel free to contact us at *' . $phone . "*.\n\n"
        . "We appreciate your cooperation and look forward to your prompt response.\n\n"
        . whatsapp_signature($business, $phone);
}
