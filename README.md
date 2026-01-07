# Technical Support Ticketing - UISP Plugin

A modern, public-facing support ticket submission system for UISP/UCRM that allows customers to submit support tickets using their Account Number (Custom ID).

## Features

- **Public Ticket Form**: Customers can submit tickets without logging in
- **Account Verification**: Validates account number against UISP Custom ID (userIdent)
- **Modern UI**: Clean, responsive design with gradient styling
- **Character Limit**: Concern field limited to 1000 characters with live counter
- **Auto-Integration**: Tickets automatically appear in UISP Ticketing system
- **Admin Dashboard**: View ticket statistics and recent tickets
- **Error Handling**: Clear error messages when account number is not found

## Requirements

- **UCRM Version**: 2.14.0 or higher
- **UISP Version**: 1.0.0 or higher
- **PHP Version**: 7.4 or higher

## Installation

### Method 1: Direct Upload (Recommended)

1. **Download the Plugin**
   - Download the plugin ZIP file from GitHub repository

2. **Upload to UISP**
   - Log in to your UISP admin panel
   - Navigate to **System > Plugins**
   - Click **"Add Plugin"** or **"Upload Plugin"**
   - Select the downloaded ZIP file
   - Click **"Upload"**

3. **Enable the Plugin**
   - Find "Technical Support Ticketing" in the plugins list
   - Click the toggle to enable it

### Method 2: Manual Installation

1. **Access Server**
   ```bash
   ssh your-server
   cd /var/www/ucrm/data/plugins/
   ```

2. **Extract Plugin**
   ```bash
   unzip technical-support-ticketing.zip
   ```

3. **Set Permissions**
   ```bash
   chown -R www-data:www-data technical-support-ticketing/
   chmod -R 755 technical-support-ticketing/
   ```

## Usage

### Public Ticket Submission

1. **Share the Public URL**
   - After installation, a public URL will be available
   - Format: `https://your-uisp-domain/crm/plugin/technical-support-ticketing/public.php`
   - Share this link with customers for ticket submission

2. **Customer Submits Ticket**
   - Customer enters their **Account Number** (Custom ID from UISP)
   - Customer describes their concern (up to 1000 characters)
   - System validates account number against UISP database
   - If valid, ticket is created in UISP Ticketing system

### Admin View

1. Navigate to **Reports > Support Ticketing** in UISP
2. View ticket statistics:
   - Total tickets
   - Open tickets
   - Pending tickets
   - Solved tickets
3. See list of recent tickets with status

## Form Fields

### Account Number (Required)
- Maps to UISP **Custom ID** (userIdent) field
- Must exactly match a client's Custom ID in UISP
- If not found: displays "Account number cannot be found."

### Concern (Required)
- Text area for describing the issue
- Maximum 1000 characters
- Live character counter shows remaining space
- Color-coded warnings when approaching limit

## How It Works

1. Customer enters Account Number and Concern
2. Plugin searches all clients for matching Custom ID (userIdent)
3. If match found:
   - Creates new ticket in UISP Ticketing system
   - Associates ticket with found client
   - Sets ticket status to "Open"
   - Adds concern as first ticket activity/comment
4. If no match:
   - Displays error message
   - Form data is preserved for correction

## API Endpoints Used

- `GET /clients` - Search for client by Custom ID
- `POST /ticketing/tickets` - Create new support ticket
- `GET /ticketing/tickets` - Fetch tickets for admin dashboard

## Customization

### Styling
The plugin uses embedded CSS with a modern gradient design. To customize:
- Edit `public.php` for the public form styling
- Edit `main.php` for the admin dashboard styling

### Colors
Default gradient: Purple to Blue (`#667eea` to `#764ba2`)

## Security

- No authentication required for public form (by design)
- Account number validation prevents spam tickets
- All inputs are sanitized and escaped
- Uses UISP Plugin SDK for secure API communication

## Troubleshooting

### "Account number cannot be found"
- Verify the Custom ID field is set for the client in UISP
- Check exact spelling and case sensitivity
- Ensure client is not archived

### Ticket Not Appearing
- Check UISP Ticketing section
- Verify plugin has proper API permissions
- Check plugin logs for errors

### Form Not Loading
- Ensure plugin is enabled
- Verify public URL is correct
- Check PHP error logs

## Support

For plugin-related issues:
- Create issue in GitHub repository
- Check UISP documentation
- Verify system requirements

## Author

**xingginto**

## License

MIT License

## Version History

- **v1.0.0**: Initial release with public ticket submission and admin dashboard
