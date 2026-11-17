-- Run once in phpMyAdmin on the invoice database.
-- Adds the flag that stops the new-invoice email from being sent twice.
-- Existing invoices are marked as already emailed, so editing an old invoice
-- never emails the client.

ALTER TABLE ip_invoices ADD COLUMN invoice_email_sent TINYINT(1) NOT NULL DEFAULT 0;
UPDATE ip_invoices SET invoice_email_sent = 1;
