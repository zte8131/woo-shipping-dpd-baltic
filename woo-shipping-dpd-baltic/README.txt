=== DPD Baltic Shipping ===
Contributors: DPD
Donate link: https://dpd.com
Tags: woocommerce, shipping, dpd, parcels
Requires at least: 6.0
Tested up to: 6.6.1
Stable tag: 1.2.83
Requires PHP: 7.4
WC requires at least: 8.2.0
WC tested up to: 8.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shipping extension for WooCommerce on WordPress of DPD Baltics. Manage your national and international shipments easily.

== Description ==

Shipping extension for WooCommerce on WordPress of DPD Baltics. Manage your national and international shipments easily.

Features of DPD plugin

1. Fast multiple label creation for national and international orders.
2. Supporting MPS (Multiparcel Shipping).
3. Create a pick-up order for courier.

**Available services:**

1. Delivery to **DPD Pickup** lockers and parcelshops in Baltics and in all Europe. Pickup map is displayed in checkout for user convenience.
2. Delivery to home in all Europe with **B2C** service.
3. Collection of money in Baltics by cash or card with **COD** service.
4. Saturday delivery in **Baltics** (restrictions to cities applied).
5. Sameday delivery **Baltics** (restrictions to cities applied).
6. **Delivery timeframes** in checkout, so that your customer can select a preffered delivery timeslot (restriction to cities applied).
7. **Document return** service to get back signed contracts, invoices or other documents.
8. **Collection request** service to send a parcel from somebody else to you. Excellent to manage returns from customers.
9. **Return** label with your address, so that your customer can send you back the shipment.

**Prerequisites:**

* This extension is available only for DPD Baltics (Lithuania, Latvia, Estonia).
* In order to use the extension you must have an active contract with one of the business units: DPD Lietuva, DPD Latvija or DPD Eesti.
* Additionally, you must have user credentials for API of DPD Baltics. Please contact DPD sales in your county.

== Installation ==

There are two methods to install the plugin: using WordPress Plugin installer and manually:

Using the WordPress Plugin Installer:

1. Unzip the downloaded DPD plugin .ZIP file into a new directory.
2. In your WordPress admin panel go to **Plugins > Add New > Upload Plugin**.
3. Upload the file **dpd-shipping-baltic.zip** which is in the directory you created in step 1.
4. Click the **Install Now** button.
5. After the installation is completed, click the button **Activate Now**.

Manual Installation:

1. Unzip the downloaded DPD plugin .ZIP file into a new directory.
2. Navigate to this directory and find the file **dpd-shipping-baltic.zip**.
3. Extract **dpd-shipping-baltic.zip** into a new directory.
4. Navigate to the newly extracted directory. You will notice it contains a directory called **dpd-shipping-baltic**.
5. Upload the contents of the **dpd-shipping-baltic** directory to **wp-content > plugins**, making sure to preserve the directory structure.
6. Go to your WordPress admin panel **Plugins > Installed Plugins > DPD Baltic Shipping ** and click **Activate**.

Congratulations! DPD Interconnector is now installed.

== Frequently Asked Questions ==

= Pickup points import =

DPD plugin updates Pickup points every time you save your credentials in module DPD settings, every day with cron job at the time of plugin activation.

Pickup point update takes about 100-150 seconds.

Cron job name "dpd_parcels_updater";
Data is saved in table "wp_dpd_terminals";

= COD settings and information =

COD fee settings can be found in the main plugin settings: **WooCommerce > Settings > DPD > General**

*COD limits:*

LT - 1000 EUR, LV - 1200 EUR, EE - 1278 EUR. If order’s total sum is above this limit, COD option is not displayed in the checkout.

== Screenshots ==

1. DPD Pickup lockers and parcelshops selection.
2. Printing label pdf.
3. DPD Pickup lockers and parcelshop map.
4. Shipping zones.
5. Shipping method settings.

== Changelog ==
= 1.2.83 =
* Added Search 2 functionality for PUDO points.
* Improved checkout block functionality.


= 1.2.80 =
* In WC Admin - Order List - DPD Bulk Actions are compatible with HPOS Mode
* Preserve order_meta_data (get and add / update) to assure DPD Plugin working properly in some cases Shop Admin switch "Order data storage" between Legacy and HPOS.
* Implement Logging Mode. By enabling it, the following flows can be tracking their processes
    * Updating Parcel List by Countries in Background Job
    * Select DPD Pickup Point in Frontstore > Checkout Form
    * Get/Add/Update Order MetaData in both modes Legacy and HPOS
* Improvement the list PUDO Points for first load in Frontstore > Checkout Form > Choose Pickup Points Dropdownlist to enhance UX

= 1.2.77 =
* Bug Fix - Fix the issue: Removed the unnecessary process logging caused the PHP Fatal Errors

= 1.2.76 =
* Bug Fix - Fix the issue: List of Pickup Points are empty in Checkout Form.

= 1.2.75 =
* Bug Fix - Fix the issue of duplicate options when selecting a pickup point.
* Bug Fix - Fix the issue of incorrect phone numbers displayed on the label.

= 1.2.74 =
* Bug Fix - Fix the issue with the "Load more" functionality not working for the PUDO list.
* Improvement - The database migration will be seamless, no plugin deactivation/reactivation needed.

= 1.2.73 =
* Feature - If there are more than 500 items, Lazy Load needs to be used to query additional PUDO points.

= 1.2.72 =
* Bug Fix - Fix issue unable to add shipping method in shipping form with WooCommerce 8.4.0.

= 1.2.71 =
* Feature - Declaring extension (in)compatibility HPOS.

= 1.2.70 =
* Bug Fix - Download manifest not generating PDF file.

= 1.2.69 =
* Feature - Fix the issue where outdated PUDO points aren't removed from the checkout page's PUDO point list.

= 1.2.68 =
* Feature - Fix critical error DPD manifest when country code is LT

= 1.2.67 =
* Feature - Manually update label count.

= 1.2.66 =
* Feature - Restrictions by dimensions validation.

= 1.2.65 =
* Feature - Validate the total weight of the cart variable when it is empty.

= 1.2.64 =
* Feature - Update the mechanism to get the parcels list and compare the data with the api.

= 1.2.63 =
* Feature - Remove check compatibility with wordPress and wooCommerce versions.

= 1.2.62 =
* Feature - Apply compatibility check of Wordpress and WooCommerce versions.

= 1.2.61 =
* Bug Fix - DPD is not defined after chosing shipping method.

= 1.2.60 =
* Bug Fix - Plugin generates error when chosen shipping method is empty.

= 1.2.59 =
* Bug Fix - Translation changes for EE language.
* Bug Fix - Pick-up points delivery does not show up in the checkout for SK, SI, HU, IE.

= 1.2.58 =
* Bug Fix - Orders without selected pickup point when create an account.

= 1.2.57 =
* Bug Fix - Options table are updating plugin DB version every time plugin loads.

= 1.2.56 =
* Bug Fix - Orders without selected pickup point.
* Bug Fix - Courier pick-up request issue.

= 1.2.55 =
* Bug Fix - Fix authorization user for delete warehouse.

= 1.2.54 =
* Bug Fix - Fix variable sanitized incorrectly.

= 1.2.53 =
* Bug Fix - Fix variable escaped incorrectly.

= 1.2.52 =
* Bug Fix - Changes in plugin back-end. 

= 1.2.51 =
* Bug Fix - Pick-up points in checkout issue fix. 

= 1.2.50 =
* Feature - Improved plugin security ( Validate, Sanitize, Escape, Nonce ).

= 1.2.11 =
* Bug Fix - Label single and bulk printing improvements/bug fixes. 

= 1.2.9 =
* Feature - Improved plugin security ( CSRF, XSS, MySQL injection). 

= 1.2.8 = 
* Bug Fix - Weight system and parcel printing improvements. 
* Bug Fix - More PHP 8.0 compatibility improvements. 


= 1.2.7 = 
* Feature - More weight system improvements.
* Feature - LV COD availability.
* Feature - More countries available for "DPD Pickup points" delivery method. 
* Feature - Sustainability logo when API country is LV. 
* Bug Fix - Coupon system improvements and bug fixes. 
* Bug Fix - Parcelshops list disappearing when daily cronjob is running. And other improvement to parcelshop system. 


= 1.2.5 = 
* Feature - Part 2 of weight system improvements. Now works with grams. 

= 1.2.4 = 
* Feature – Weight system improvements.
* Bug Fix – Tracking codes links fix. 
* Other small bug fixes and impovements.

= 1.2.2 =
* Hotfix – Compatibility with OPAY Payments plugin.

= 1.2.1 =
* Feature - Customers can see their tracking codes in emails.
* Feature - Added more accurate translations for LT, EE, LV. 
* Bug Fix - Some API ports were not usable so they were deleted.
* Bug Fix - Pick-up points loss in database after plugin update. 
* Bug Fix - VAT is not being added to COD price. 
* Other small bug fixes and impovements.

= 1.1.1 =
* Feature - Pick-up points list is now ordered in alphabetical order.
* Notice - Lithuanian service changes.
* Other bug fixes and impovements.

= 1.1.0 =
* Feature - Ability to select for which countries fetch pickup points list.
* Notice - France and Germany pickup points will be fetched without working hours if server max_execution_time is lower than 60 seconds.

= 1.0.9 =
* Fix - Display message, then order DPD label cannot be generated for order.
* Tweak - WP 5.4 compatibility.
* Tweak - WC 4.1 compatibility.

= 1.0.8 =
* Fixed endless flickering bug in checkout.

= 1.0.7 =
* Fixed LT, LV, EE plugin localization.

= 1.0.6 =
* Added mass DPD labels printing.

= 1.0.5 =
* Fixes for shipping methods availability by cart weight.
* Other bugfixes and improvements.

== Upgrade Notice ==

= 1.0 =
