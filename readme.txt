=== Lock Out ===
Contributors: skullbit
Donate link: http://skullbit.com/donate
Tags: maintenance, lock, private, roles
Requires at least: 2.3
Tested up to: 2.5
Stable tag: 1.1

Lock out users from accessing your website while performing upgrades or maintenance to your website, while still allowing certain user roles access.

== Description ==

This plugin will allow you to put your website into Lock Out mode to prevent access while you preform upgrades or maintenance on your site.  Includes the ability to upload a pre-made html file for use as a placeholder page while in lock out mode or build your own online.  The login page is still accessible and will allow only the user role you set to view the site normally while in lock out mode.

== Installation ==

1. Upload the `lock-out` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Set the options in the Settings Panel

== Frequently Asked Questions ==

= Somethings gone horribly wrong and my site is down =

Some settings may alter your .htaccess file which, though unlikely, could cause this problem.  If this happens, remove the plugin from the wp-content/plugins directory and remove or edit your .htaccess file to fix your site. If editing, remove the rules located between `#Lock Out` and `#End Lock Out`

== Screenshots ==

1. Lock Out Settings