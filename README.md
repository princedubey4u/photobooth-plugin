Photobooth Quote Request Plugin
(A professional WordPress plugin for handling photobooth quote requests with WooCommerce integration.)

Features
- Step-by-step quote form with event details, package selection, and extras
- WooCommerce integration - pulls packages and extras from products
- Admin dashboard - view and manage all quote requests
- Email notifications - admin receives email for each new quote
- Responsive design - works perfectly on desktop and mobile

Requirements
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+

Installation
1. Download and extract the plugin folder
2. Upload to `/wp-content/plugins/`
3. Activate the plugin in WordPress admin
4. The plugin will automatically create a database table for quotes

Setup Instructions

Step 1: Create WooCommerce Products - Create two product categories:
1. photobooth-packages - Create your photobooth packages (Basic, Premium, Deluxe, etc.)
2. photobooth-extras - Create extras/add-ons (Props, Backdrop, Green screen, etc.)

Step 2: Add Products - Packages:
- Go to Products > Add New
- Set name, price, short description
- In Product data > Categories, add "photobooth-packages"
- Publish

Extras:
- Same process but add to "photobooth-extras" category
- Add product images for better display

Step 3: Display the Form - Add this shortcode to any page or post: [photobooth_quote]

Or for a specific package pre-selected: [photobooth_quote default_package_id="123"]
Replace `123` with your package product ID.

Step 4: View Quotes
- Admin Dashboard > Photobooth Quotes
- See all submitted quote requests
- Delete requests as needed
- Emails are automatically sent to your admin email address

Usage - Users will:
1. Fill in event details (date, location, time, hours)
2. Select a photobooth package
3. Choose optional extras
4. Enter contact information
5. Submit the form

The quote data is saved to your WordPress database and an email is sent to the admin.

Customization: 

Change Submit Button Text
Edit `/templates/quote-form.php` line for the button text.

Change Email Recipient
The plugin uses your WordPress admin email by default. To change it:
- Edit `/includes/class-form-handler.php`
- Modify the `send_admin_email()` function

Customize CSS
Edit `/assets/css/style.css` to match your branding colors, fonts, etc.

Troubleshooting
"WooCommerce is required" message:
- Install and activate WooCommerce plugin first

No products showing in form:
- Create WooCommerce products
- Make sure they're in the correct categories:
  - "photobooth-packages" for packages
  - "photobooth-extras" for extras

Emails not sending:
- Check your WordPress email settings
- Make sure your server can send emails
- Check spam folder

Support
For issues or questions, contact your developer.

Version
Version 1.3.0
