RecognizeFilemanagerBundle
========================

![filemanager](https://bitbucket.org/recognize/filemanagerbundle/raw/master/filemanager.png)

This bundle allows you to use a custom filemanager that works simular to Moxiemanager.
It gives users the option to organize files withouts links in the database breaking as long as
the managing is done through the filemanager apis.

It has the following benefits over Moxiemanager:

* Customizable API paths
* Deep Symfony2 integration
* Form widget that can select and upload files to the filesystem with a working fallback if javascript is disabled
* Configurable security options for actions like opening directories and deleting files based on user roles
* Directory level ACLs that can grant or disallow actions for user roles or users.
* HTML that can laid out in any way you want, giving you full flexibility over the styling and the UI

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
			"url":  "git@bitbucket.org:recognize/filemanagerbundle.git"
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

Add the form widget and add the example config.yml contents from the Resources/config folder
to the app config.yml

```yml
// app/config.yml
twig:
	form:
		resources:
			- 'RecognizeFilemanagerBundle::widget.html.twig'
			
recognize_filemanager:
	directories:
		default: /var/www/Filemanager/app/cache
		example_directory: /var/www/Filemanager/app/example
	
    thumbnail:
        directory: /var/www/Filemanager/web/cache
        size: 80
        strategy: indexed_only
		
    api_paths:
        read: _fileapi_read
        search: _fileapi_search
        create: _fileapi_create
        upload: _fileapi_create
        rename: _fileapi_rename
        move: _fileapi_move
        delete: _fileapi_delete
        download: _fileapi_download
        preview: _fileapi_preview
```

Finally, add a new Controller class with routes that serves as the API entrance.

```php
// src/AppBundle/Controller/FileController.php

class FileController extends FilemanagerController {

    /**
     * @return FilemanagerService
     */
    protected function getFilemanager(){
        $manager = parent::getFilemanager();
        return $manager;
    }

    /**
     * @Route("/fileapi", name="_fileapi_read")
     * @param Request $request
     */
    public function read(Request $request){
        return parent::read( $request );
    }

    /**
     * @Route("/fileapi/search", name="_fileapi_search")
     * @param Request $request
     */
    public function search(Request $request){
        return parent::search( $request );
    }

    /**
     * @Route("/fileapi/create", name="_fileapi_create")
     * @param Request $request
     */
    public function create(Request $request) {
        return parent::create( $request );
    }

    /**
     * @Route("/fileapi/move", name="_fileapi_move")
     * @param Request $request
     */
    public function move(Request $request) {
        return parent::move( $request );
    }

    /**
     * @Route("/fileapi/rename", name="_fileapi_rename")
     * @param Request $request
     */
    public function rename(Request $request) {
        return parent::rename( $request );
    }

    /**
     * @Route("/fileapi/delete", name="_fileapi_delete")
     * @param Request $request
     */
    public function delete(Request $request) {
        return parent::delete( $request );
    }

    /**
     * @Route("/fileapi/preview", name="_fileapi_preview")
     * @param Request $request
     */
    public function preview(Request $request) {
        return parent::preview( $request );
    }

    /**
     * @Route("/fileapi/download", name="_fileapi_download")
     * @param Request $request
     */
    public function download(Request $request) {
        return parent::download( $request );
    }
}
```

Usage
--------------

You can use the filemanager in two major ways. In forms as a widget and as a standalone element.

**Form widget**

Using a filemanager element in your form is quite easy. Simply use the filereference form type. 
This form type gives you a FileReference entity after a submit. 
You can decide whether you want to use the absolute path of the file or the database ID, 
although it is recommended to use the ID to make sure the file can be moved and renamed without causing errors somewhere else in the application.

This formtype supports the same validation constraints as file uploads and image uploads.

```php
$form = $this->createFormBuilder( array() )
    ->add('image', 'filereference', array(
        "constraints" => array(
            new Image(
                array(
                    'maxWidth' => "500",    
                    'maxHeight' => "500"
                )
            )
        ))
    );
```                

You can navigate to a directory in the widget as well. 
The following configuration will make sure the user starts off in the working directory's images directory.
This also means that submitted uploaded files will end up in this directory if it isn't changed by the user.

```php
$form = $this->createFormBuilder( array() )
    ->add('image', 'filereference', 
        array( 'directory' => "images/" );
    );
```

If you only want to use the file uploader, you can turn set the 'is_simple' option to true. 
This will disable the modal window which includes the filemanager widget.

```php
$form = $this->createFormBuilder( array() )
    ->add('image', 'filereference', 
        array("is_simple" => false )
    );
```      

**Standalone**

Using it as a standalone element can be done using the twig function. 

```twig

{{ filemanager("unique_id", recognize_filemanager_config, "bootstrap" ) }}
```

This will create a standalone element with the bootstrap theme of the filemanager. 
It uses the filemanager configuration variable that is pushed into the globally into the twig templates.
You can override this with your own configuration object if you want to use a different configuration for the standalone element.

Cleaning
--------------

You can clean both the filesystem and the database from unindexed files and directories using the command line tool.
It is recommended that you use the verbose flag to see exactly what files and directories are removed.

Command for cleaning the filesystem
```sh
php app/console filemanager:filesystem:clean -v
```

Command for cleaning the database
```sh
php app/console filemanager:database:clean -v
```

Configuration
--------------

The configuration in the app config should give you an example of the options you have at your disposal.

**Working directories**

First up is the directories array. 
This array contains all the possible absolute paths that can be served as working directories for the filemanager.
The default option is always used if no directory has been explicitly set.

The working directory can be changed during runtime in your FileController in the getFilemanager method. 
This can be useful if you want to show a different set of folders for users and admins.

```php
// FileController.php
    
    /**
     * @return FilemanagerService
     */
    protected function getFilemanager(){
        $manager = parent::getFilemanager();
        
        $securityContext = $this->get('security.context');
        if( $securityContext->isGranted('ROLE_USER') ){
            $manager->setWorkingDirectory( "example_directory" );
        }
        return $manager;
    }

```

**Thumbnails**

The thumbnails are automatically generated when images are uploaded to the server 
and added to the thumbnail directory that is set in the configuration file.

Not setting the thumbnail directory has a significant performance impact.
It means the complete files will be retrieved through php from the server instead of an image.

If the thumbnail directory is set and the thumbnail strategy is set to all, thumbnails will be generated for ALL
files when they are retrieved from the filemanager API. Otherwise, only indexed files have their thumbnails generated.

If there are some files that don't have proper thumbnails, you can generate them using the following command

```sh
php app/console filemanager:thumbnails:generate
```

**Api paths**

The api paths can be changed individually. 
They should be path names responding to a path that exists in the router.

**Security**

Security is divided into two seperate parts, a voter part based on the configuration, and ACLs.
To enable security features for the filemanager bundle, add the following to the filemanager configuration

```yml
// app/config
recognize_filemanager:
    security: enabled
```

Configuration voters

Add the following to the configuration of the filemanager

```yml
// app/config
recognize_filemanager:
    access_list:
        - { path: ^/$, directory: default, roles: [ ROLE_USER ], actions: [open] }
        
```

The access list works similarly like the one found in the security.yml one.
It checks if the currently accessed directory matches the path and the working directory,
And then checks if the currently logged in users roles are allowed to perform the actions defined.

When one of the access_list nodes grants access, then the user is granted access, 
otherwise it will check the next access_list node. If no node has given acces, it is denied.

The actions possible are
- open : Read access to the directory, but if the directories inside it lack open access, they will not be shown
- create: Creating directories inside this directory
- upload: Uploading files - Specific file types cannot be specified
- move: Moving this directory
- rename: Renaming this directory
- delete: Deleting this directory

ACLs

To enable ACLs, make sure the acl connection is defined in the security.yml

ACLs work on a per directory basis, meaning that they can be enabled solely on directories, not on specific files.
When a directory has ACLs matching the user or one of its roles, any definite permission is immediately used.
This means if the ACL for opening this directory is denied or granted, then access is given based on the outcome of the ACL.

When no ACL is found for a directory, it checks if the next directory has ACLs, until it reaches the root directory, 
where it starts to fall back on the configuration voter system.

Granting or denying access to a directory for a user or a role must be done through the FileACLManagerService.
There is no default UI for this, so to enable this for users, you have to make a UI encapsulating this functionality.


```yml
#app/config/security.yml

security:
    acl:
        connection: default
```

Testing PHP
--------------

To set up the testing enviroment you have to do two things

  * [Install phpunit][1]

[1]:  https://phpunit.de/manual/current/en/installation.html

After this, you can simply run the following command to test all the files.

```sh
phpunit --testsuite all
```

**NOTE:** This testsuite requires a testdatabase. If you just want to test the units without a database, run the following command.

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

Documentation
-------------