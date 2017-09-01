=== Rooftop Queue Pusher ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: http://errorstudio.co.uk
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 4.8.1
Stable tag: 4.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Handles pushing events onto the queues, and queue runners for hosted Rooftop.

== Description ==

In addition to regular queues, we also have scheduled queues. When a job fails, we increment an 'attempts' attribute in
the payload and push it back onto a queue (using enqueueIn()) which accepts a delay in seconds before the job is attempted.

To start the scheduler and queues:
cd queues/runners
php queue_scheduler.php
php queue_runner.php

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `rooftop-queue-pusher.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Can this be used without Rooftop CMS? =

Yes, it's a Wordpress plugin you're welcome to use outside the context of Rooftop CMS. We haven't tested it, though.


== Changelog ==

= 1.2.1 =
* Tweak readme for packaging

= 1.2.0 =
* Only spawn 1 instance of the queue runner
* Decrease queue polling interval


== What's Rooftop CMS? ==

Rooftop CMS is a hosted, API-first WordPress CMS for developers and content creators. Use WordPress as your content management system, and build your website or application in the language best suited to the job.

https://www.rooftopcms.com
