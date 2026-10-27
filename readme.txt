=== Webhook Buttons for WooCommerce ===
Contributors: noblestrategies
Tags: woocommerce, webhook, orders, automation, buttons
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add buttons to the WooCommerce order screen. Each button sends that order's data to a webhook URL you choose.

== Description ==

Webhook Buttons for WooCommerce adds a box of buttons to the order edit screen. Click a button and the plugin sends the order data to the webhook URL you set for that button. Use it to start a workflow in your automation tool, create a job in another system, or notify your team.

You choose where order data goes. You are responsible for the customer data you send to that destination.

**What you can do**

**Free version**

* Add as many buttons as you need, in one or more boxes on the order screen.
* Give each button its own webhook URL.
* Ask for confirmation before a send, or send right away.
* Send an order again whenever you need to.
* Leave chosen product attributes out of the data.
* Every send adds an order note with the result.

**Pro adds**

* Custom fields: values from the WooCommerce Admin Custom Order Fields extension, plus any order meta keys you pick (Checkout Field Editor, ACF and others).
* Flat data: one level, for tools that cannot read nested fields.
* A short staff note sent with the order.
* User access control: show a box or a button only to the staff you pick.
* Sample sends with up to 100 line items, so Zapier, Make and n8n see every field.
* An auth header on each button's requests, for example an API key.

**What is sent**

A JSON POST with the order ID, status, date, total, billing and shipping details, customer email and phone, the customer note, the line items (name, quantity, attributes), and the name of the user who clicked.

**License**

The free version has no limits and no license. Pro is a separate download for license holders. Everything is kept in your own database; nothing is checked on our servers.

== Installation ==

1. Make sure WooCommerce is installed and active.
2. Upload the `webhook-buttons` folder to `/wp-content/plugins/`, or upload the zip file in Plugins > Add New > Upload Plugin.
3. Activate the plugin.
4. Go to Webhook Buttons in the admin menu.
5. Click "Add your first button". Add a box title, a button label, and the webhook URL. Check "Enable this button".
6. Click "Save all".
7. Open an order. The box with your button is in the right-hand column.

== Frequently Asked Questions ==

= Which services can receive the data? =

Any service that gives you a webhook URL that accepts a JSON POST: Zapier, Make, n8n, Pabbly Connect, Microsoft Power Automate, IFTTT, Activepieces, Pipedream, Zoho Flow and others. Make and n8n offer webhooks on their free plans; Zapier and IFTTT need a paid plan for webhooks.

= Which WooCommerce setups and plugins are supported? =

* WooCommerce with High-Performance Order Storage (HPOS) on or off.
* Admin Custom Order Fields (SkyVerge): every custom field is sent automatically.
* Order meta from any other plugin (for example Advanced Custom Fields, Checkout Field Editor, Flexible Checkout Fields): pick the keys once in Advanced settings and they are sent under custom_fields.

= Can I send to a tool on the same server (localhost)? =

By default WordPress refuses requests to localhost and private network addresses (10.x, 192.168.x, 172.16-31.x, 100.64-127.x) and to any port other than 80, 443 and 8080. This protects shared hosts. To allow your own n8n or similar on the same machine, add these two lines to your theme or a small plugin. The first allows the host, the second allows its port. Change both to yours:

`add_filter( 'http_request_host_is_external', fn( $external, $host ) => $host === '192.168.1.20' ? true : $external, 10, 2 );`

`add_filter( 'http_allowed_safe_ports', fn( $ports, $host ) => $host === '192.168.1.20' ? array_merge( $ports, [ 5678 ] ) : $ports, 10, 2 );`

= Why does the settings page warn about http:// URLs? =

Order data includes customer names, emails and phone numbers. Over plain http:// it travels unencrypted. Use https:// wherever you can.

= What is the difference between Lite and Pro? =

The free version sends the standard order data (order, totals, billing, shipping, line items) from as many buttons and boxes as you like, as often as you like. Pro is extra code, not a switch: custom fields, flat data, staff notes, user access control, sample sends with up to 100 line items, and an auth header per button.

= What happens when my license expires? =

All buttons keep working with the free features. The Pro options switch off until you renew, and the values you set for them are kept, so they come back when you renew. Updates and support stop until then.

= Where do I get help? =

https://noblestrategies.dev/webhook-buttons

= When is a send counted as successful? =

When the webhook URL answers with an HTTP 2xx status. The order note records the result either way.

= Who can click the buttons? =

Users who can edit WooCommerce orders. Pro can limit each box and each button to chosen users.

= Does it work with High-Performance Order Storage (HPOS)? =

Yes. The plugin declares HPOS compatibility and supports both the HPOS and the classic order screens.

= Where does the order data go? =

Only to the webhook URLs you enter. You choose where order data goes. You are responsible for the customer data you send to that destination.

= What does the data look like? =

JSON with these groups: order, totals, billing, shipping, items (a list), button, sent_by, sent_at. Pro adds custom_fields and note, and can send one flat level instead (for example items_1_name). Dates use ISO 8601. Developers can change the data with the webhook_buttons_order_data filter, the body with webhook_buttons_request_body, and the headers with webhook_buttons_request_headers.

= Does it send custom order fields? =

Pro does. With the Admin Custom Order Fields extension (SkyVerge) active, every field is sent under custom_fields as field_{id}, so the key stays the same if you rename a field. Values typed on the order screen are sent even before you click Update. Pro also sends any order meta keys you pick in Advanced settings.

= Does the plugin send data to anyone else? =

License checks use Freemius. If you opt in, Freemius receives your site URL and email. Nothing else leaves your site except what you send to your own webhook URLs.

== Development ==

The settings page is a small React app. Its readable source is in react-settings-ui/src. To rebuild it:

`cd react-settings-ui && npm install && npm run build`

Build the release zips with `scripts/build.sh` in the product folder (freemius, marketplace, lite).

== Screenshots ==

1. The settings page: boxes and buttons, each with its own webhook URL.
2. A button box on the WooCommerce order screen.
3. The order data as received by a webhook.

== Changelog ==

= 1.0.5 =
* Ready for translation: every string on the settings page and the order screen can now be translated. German, Spanish and French are included.
* FAQ: the localhost answer now also covers the port rule (`http_allowed_safe_ports`).

= 1.0.4 =
* One review request after your store has sent 5 orders. Dismiss it for good or snooze it for 30 days.
* New action for developers: `webhook_buttons_sent` fires after every successful send.

= 1.0.3 =
* Cleaner Upgrade page: one Pro card with a site-count picker, yearly prices.

= 1.0.2 =
* Declares the WooCommerce versions it was tested with.

= 1.0.1 =
* Free version: no limits on boxes, buttons or resends. Pro features are separate code: custom fields, flat data, notes, user access control, sample sends, auth header.


= 1.0.0 =
* First release.
