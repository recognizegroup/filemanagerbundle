RecognizeFilemanagerBundle
========================

This bundle allows you to use a custom filemanager that works simular to Moxiemanager.
It gives users the option to organize files withouts links in the database breaking as long as
the managing is done through the filemanager apis.

It has the following benefits over Moxiemanager:

* Customizable API paths
* Deep Symfony2 integration
* Form widget that can select and upload files to the filesystem with a working fallback if javascript is disabled
* Configurable security options for actions like opening directories and deleting files based on user roles
* Directory level ACLs that can grant or disallow actions for user roles or users.

The widget has been tested on IE9 and above, as well as Firefox, Chrome and Safari.
It also works on mobile, and extra care has been taken to ensure that it works with just keyboard navigation as well.

Installation
-----------

Add the bundle to your composer.json

```json
# composer.json
{
	"repositories": [
		{
			"type": "git",
			"url":  "git@bitbucket.org:recognize/filemanager-bundle.git"
		}
	],
	 "require": {
		"recognize/filemanager-bundle": "dev-master",
	}
}
```

Run composer install

```sh
php ./composer.phar install
```

Enable the bundle in the kernel

```php
	<?php
	// app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Recognize\FilemanagerBundle\RecognizeFilemanagerBundle(),
        );
    }
```

Add the form widgets to the app config.yml

```yml
	# app/config.yml
	# Twig Configuration
	twig:
    	form:
        	resources:
            	- 'RecognizeFilemanagerBundle::widget.html.twig'

```

Documentation
--------------



Testing PHP
--------------

To set up the testing enviroment you have to do two things

  * [Install phpunit][1]

[1]:  https://phpunit.de/manual/current/en/installation.html

After this, you can simply run the following command to test all the files.

```sh
phpunit --testsuite all
```

NOTE: This testsuite requires a testdatabase. If you just want to test the units without a database, run the following command.

```sh
phpunit --testsuite unit
```

Testing javascript
------------------------

First, make sure you have npm and the dependencies installed on your machine.
Use the following command to get the dependencies after retrieving npm

```sh
npm install
```

Then, you can run the following command to test the javascript

```sh
grunt jasmine
```

