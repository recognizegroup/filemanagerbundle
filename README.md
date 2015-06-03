RecognizeFilemanagerBundle
========================

A bundle that allows you to quickly render a WYSIWYG editor ( currently supports CKEditor ).
Simply add a 'wysiwyg' element to your form and you're good to go.

Features

* Can render regular and inline WYSIWYG editors.
* Supports automatic language detection from the Symfony locale
* Allows seperate configuration per wysiwyg element

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
            new Recognize\WysiwygBundle\RecognizeFilemanagerBundle(),
        );
    }
```

Testing
--------------

To set up the testing enviroment you have to do two things

  * [Install phpunit][1]
  
  * Install the pre-commit hook


[1]:  https://phpunit.de/manual/current/en/installation.html

##Installing the pre-commit hook

Run the following command in the root directory of this project

**Linux and Mac:**
```sh
cp .hooks/pre-commit-phpunit .git/hooks/pre-commit
chmod 755 .git/hooks/pre-commit
```

**Windows:**
```sh
copy .hooks/pre-commit-phpunit .git/hooks/pre-commit
```

This will make sure the unit tests will be run before each commit.
If you want to disable the unit tests before a commit, you can use the following command

```sh
git commit --no-verify -m "Commit message!"
```

Testing with php and javascript
------------------------

First, make sure you have npm and grunt-cli installed on your machine.

```sh
npm install -g grunt-cli
```

Then, run this command to install all the required packages locally

```sh
npm install grunt --save-dev
npm install grunt-contrib-jasmine --save-dev
npm install grunt-contrib-uglify --save-dev
npm install jasmine-jquery
```