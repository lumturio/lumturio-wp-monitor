=== Lumturio WP Monitor ===
Contributors: lumturioteam
Donate link: https://lumturio.com
Tags: dashboard, updates, security, wordpress, updates, management, admin, WordPress Admin, custom
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Lumturio offers users and agencies powerful and reliable tools to monitor CMS security.
As such, our platform empowers you to quickly and efficiently monitor all your
clients’ websites, allowing you to proactively update them and keep them safe.

This plugin offers a way to collect data from Wordpress websites
to check on modules and versions used, instead of having every site
check for updates separately.

This allows administrators to build their own monitoring interface to
check on multiple installations at once.

== Installation ==

* Project URL: https://wordpress.org/plugins/lumturio-wp-monitor/
* Svn URL: http://plugins.svn.wordpress.org/lumturio-wp-monitor/

Download and install the module the same way you would download and
install other contributed modules.


After installation check the admin page under the Lumturio menu item or
/wp-admin/admin.php?page=lumturio-plugin-settings and copy your siteUUID
and enter it at https://lumturio.com

== Frequently Asked Questions ==

= Why do I need to install a plugin on my Wordpress site? =

The default update monitor build into Wordpress works by each site checking
for updates triggered by a cron job. Our service works the oppisite way,
we will contact your site and ask for the currently installed modules and their
versions. We will then compare this to our database of Wordpress contrib modules
and calculate an upgrade path for you if required.

= How can I be sure that my data is secure? =

Lumturio uses SHA256 and supports TLS 1.2 for all communication with
and from the platform and uses the DHE-RSA Key Exchange Algorithm.
Passwords are encrypted through unique salts per account, using the SHA512 algorithm.
We monitor the security community's output closely and work promptly to
upgrade the service to respond to new vulnerabilities as they are discovered.

= How will you use this data that I provide? =

The plugin itself will always be reviewed and worked on by the Wordpress community
to ensure that it only delivers a list of currently used modules and their versions.
No other data is or will ever be transmitted.

= Who can I get in touch with if I have questions? =

You are welcome to send an e-mail to hello@lumturio.com, we will do our best to
respond as soon as possible.

= I have a feature request, what should i do? =

All feature requests are welcome, feel free to send us an e-mail on hello@lumturio.com.
No promises are made, but we will definitely look into it.

= I like this project, can I help? =

We're always looking for enthousiastic souls that want to help make this project better.
If you feel you have something to contribute, contact us by e-mail at hello@lumturio.com.
