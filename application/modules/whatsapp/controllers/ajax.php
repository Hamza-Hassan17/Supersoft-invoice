<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// $ajax_controller rejects cross-site form posts, which can't set the AJAX header.
class Ajax extends Admin_Controller
{
    public $ajax_controller = TRUE;

    public function status()
    {
        $status = whatsapp_status();

        $this->reply(array(
            'configured' => whatsapp_configured(),
            'state' => $status['state'],
            'number' => $status['number'],
            'qr' => strpos((string)$status['qr'], 'data:image/png;base64,') === 0 ? $status['qr'] : NULL,
            'error' => isset($status['error']) ? $status['error'] : NULL,
        ));
    }

    public function disconnect()
    {
        $this->require_post();
        $this->reply(whatsapp_logout());
    }

    public function send_test()
    {
        $this->require_post();

        $number = whatsapp_number($this->input->post('number'));
        if (!$number) {
            $this->reply(array('error' => 'Enter a valid mobile number, e.g. 03001234567.'));
            return;
        }

        $this->reply(whatsapp_send($number, 'Test message from your invoice system. WhatsApp is connected.'));
    }

    public function modal_reminder()
    {
        $this->require_post();

        $invoice = $this->reminder_invoice($this->input->post('invoice_id'));
        $data = array('invoice' => $invoice, 'number' => NULL, 'connected' => FALSE, 'text' => '');

        if ($invoice) {
            $status = whatsapp_status();
            $data['number'] = whatsapp_invoice_number($invoice);
            $data['connected'] = $status['state'] === 'connected';
            $data['text'] = whatsapp_message_text($invoice);
        }

        $this->layout->load_view('whatsapp/modal_reminder', $data);
    }

    /** The recipient always comes from the invoice, never from the browser. */
    public function send_reminder()
    {
        $this->require_post();

        $invoice = $this->reminder_invoice($this->input->post('invoice_id'));
        $number = $invoice ? whatsapp_invoice_number($invoice) : NULL;
        $text = trim((string)$this->input->post('text'));

        if (!$number) {
            $result = array('error' => 'This invoice has no amount or the client has no valid mobile number.');
        } elseif ($text === '' || strlen($text) > 16000) {
            $result = array('error' => 'Please enter a message of a reasonable length.');
        } else {
            $pdf = whatsapp_invoice_pdf($invoice->invoice_id);
            $name = 'Invoice_' . preg_replace('/[^\w-]+/', '_', $invoice->invoice_number) . '.pdf';
            $result = $pdf
                ? whatsapp_send($number, $text, $pdf, $name)
                : array('error' => 'The invoice PDF could not be created, so nothing was sent.');
        }

        $this->reply($result);
    }

    private function reminder_invoice($invoice_id)
    {
        $this->load->model('invoices/mdl_invoices');

        $invoice = $this->mdl_invoices->get_by_id((int)$invoice_id);

        return ($invoice && whatsapp_message_type($invoice)) ? $invoice : NULL;
    }

    private function require_post()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_404();
        }
    }

    private function reply($data)
    {
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($data));
    }
}
