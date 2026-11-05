# WhatsApp service

Keeps the WhatsApp login open and sends reminders for the invoice site.

## Set up on cPanel

Keep this folder **outside `public_html`**. Its `auth/` folder gives access to the linked WhatsApp account.

1. Upload `app.js`, `package.json` and `package-lock.json` to a folder such as `~/invoice-whatsapp`.
2. Create a random token of at least 32 characters and save it in a `.token` file in that folder.
3. In **Setup Node.js App**, click **Create Application**:
   - Node.js version: 22
   - Application mode: Production
   - Application root: `invoice-whatsapp`
   - Application URL: the `invoice.supersoft-tech.com` domain, path `wa-service`
   - Application startup file: `app.js`
4. Click **Run NPM Install**, then start the app.
5. Upload `application/config/whatsapp.php`, copied from `whatsapp.example.php`, with:
   - `whatsapp_service_url`: `https://invoice.supersoft-tech.com/wa-service`
   - `whatsapp_token`: the same token
6. In **Cron Jobs**, add a job every 5 minutes that restarts the app if it has stopped:

   ```
   curl -s https://invoice.supersoft-tech.com/wa-service/ping > /dev/null
   ```

7. Open the WhatsApp page on the invoice site and scan the QR code.
