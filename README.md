# CakeDatabaseTools #

Tools to assist with CakePHP Database managment

# Install #
1. Copy files to `app/Plugin/CakeDatabaseTools`
1. Override configuration defaulst from `app/Plugin/CakeDatabaseTools/Config/bootstrap.php` in `~/app/Config/local.php`*
1. Load Plugin in `app/Config/bootstrap.php`**

*MySQL cloner user credentials - That MySQL user is expected to have only read access and we're okay with anyone who has repository access to have read access to the stage server's database.

**Code to add

````php
    if (Configure::read('debug') > 0) {
        CakePlugin::load('CakeDatabaseTools', array('bootstrap' => true));
        //Also see See app/Plugin/CodingStandards/Config/bootstrap.php for other variables you can tweak
    }
````
