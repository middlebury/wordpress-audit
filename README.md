About
=====

There are a number of great tools for profiling a WordPress Multisite
installation to list the available plugins, themes, and sites, but these tools
can be resource intensive when your installation has many thousands of sites and
they can only examine a single installation of WordPress at once.

This application allows you to aggregate information from multiple installations
of WordPress and view it in a single interface. Because this is done in a
separate system from your WordPress site(s), you can schedule the aggregation of
the information using cron to occur outside of peak hours.

Assumptions
===========

This applications makes the following assumptions about your environment:

* Your WordPress installations are using the same codebase, though it may be
stored on separate servers.
* You can put a copy of your WordPress codebase in a directory that this
application can read.
* You are using Git to version control your WordPress codebase.
* You are using MySQL for your WordPress databases.

Presumptions
------------

* You are using the [Multisite Plugin
Manager](https://wordpress.org/plugins/multisite-plugin-manager/) plugin to
control whether users can activate plugins on their sites. If you are not using
this, there will be a blank "Permissions" field on the single plugin view.
* You are using the [More Privacy
Options](http://wordpress.org/extend/plugins/more-privacy-options/) plugin to
control whether sites are publicly visible or can only be seen by certain groups
within WordPress. If you are not using this, you will not notice any difference.
* You are using the [Restricted Site
Access](https://wordpress.org/plugins/restricted-site-access/) plugin to limit
access to some of your sites by IP range. If you are not using this, you will
not notice any difference.

Installation
============

1. Clone the Git repository to your machine.

    git clone https://github.com/middlebury/wordpress-audit.git

2. Install `composer` if you don't have it.

    curl -sS https://getcomposer.org/installer | php

3. Go to your application directory in your terminal.

    cd wordpress-audit

4. Install dependencies via `composer`. You will be prompted to enter some
configuration values for the application. One of these will look like a big
cluster of option names because the install process doesn't handle nested option
arrays. You can skip over that for now.

    php composer.phar install

5. Edit the `app/config/parameters.yml` file. See the documentation in the
`parameters.yml.dist` file for the values expected.

6. Create your application's database.

    php bin/console doctrine:database:create

7. Create your database tables.

    php bin/console doctrine:schema:update --force

8. If you have not placed this in a web server directory, go to the application
directory in your terminal and start Symfony's local web server.

    php bin/console server:run

9. Browse to the `/refresh` route to fetch an initial import of data.

10. Browse to `/` to view the options.

Copyright and License
=====================

This software is copyright &copy; *The President and Fellows of Middlebury
College* and is provided as Free Software under the terms of the [GPLv3 (or
later) license](http://www.gnu.org/licenses/gpl-3.0.en.html).

This relies on the Symfony framework, which is provided [under the MIT
License](http://symfony.com/license).

Authors
-------

* Ian McBride