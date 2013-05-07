# CakeDatabaseTools #

Tools to assist with CakePHP Database managment

# Install #
1. Copy files to `app/Plugin/CakeDatabaseTools`
1. `cp app/Plugin/CakeDatabaseTools/Config/bootstrap.php.template app/Plugin/CakeDatabaseTools/Config/bootstrap.php` and set stage server connection defaults
1. Load Plugin in `app/Config/bootstrap.php`*

*Code to add

````php
    if (Configure::read('debug') > 0) {
        CakePlugin::load('CakeDatabaseTools', array('bootstrap' => true));
        //Also see See app/Plugin/CodingStandards/Config/bootstrap.php for other variables you can tweak
    }
````
