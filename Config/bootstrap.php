<?php
/*
 * Cake Database Tools configuration variables
 *
 * Override varaible(s) by placing similar statement(s) after your ~/app/Config/bootstrap.php includes this file
 */

/**
 * Stage server's domain name
 */
Configure::write('stage_domain', 'sub.domain.tld');

/**
 * MySQL user name on stage server
 */
Configure::write('stage_domain_mysql_login', 'cloner');

/**
 * MySQL user password on stage server
 */
Configure::write('stage_domain_mysql_pass', 'password');

/**
 * Place unique value for user's passwords to be set to after data clone
 */
Configure::write('database_clone_from_stage_users_password', '');
