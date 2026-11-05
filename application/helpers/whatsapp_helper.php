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

function whatsapp_call($method, $path, $data = NULL)
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
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
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

function whatsapp_send($number, $text)
{
    return whatsapp_call('post', 'send', array('to' => $number, 'text' => $text));
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

function whatsapp_amount($invoice, $amount)
{
    $currency = trim((string)$invoice->amount_curre);
    if ($currency === '' || $currency === 'Rs') {
        $currency = 'PKR';
    }

    $amount = (float)$amount;

    return $currency . ' ' . number_format($amount, floor($amount) == $amount ? 0 : 2);
}

/** $contact_number is the connected WhatsApp number, or NULL when not connected. */
function whatsapp_reminder_text($invoice, $contact_number = NULL)
{
    $config = whatsapp_config();
    $business = !empty($config['whatsapp_business_name'])
        ? $config['whatsapp_business_name']
        : 'SuperSoft Technologies';

    if ($contact_number && substr($contact_number, 0, 2) === '92') {
        $contact_number = '0' . substr($contact_number, 2);
    }

    return 'Dear ' . trim($invoice->client_name) . ",\n\n"
        . 'This is a kind reminder from ' . $business . ' about your invoice #' . $invoice->invoice_number
        . ' dated ' . date('d M Y', strtotime($invoice->invoice_date_created)) . ".\n\n"
        . 'Total: ' . whatsapp_amount($invoice, $invoice->invoice_total) . "\n"
        . 'Paid: ' . whatsapp_amount($invoice, $invoice->invoice_paid) . "\n"
        . 'Balance due: ' . whatsapp_amount($invoice, $invoice->invoice_balance) . "\n\n"
        . 'Please clear the remaining balance at your earliest convenience.'
        . ($contact_number ? ' For any questions, call us at ' . $contact_number . '.' : '')
        . "\n\nThank you,\n" . $business;
}
