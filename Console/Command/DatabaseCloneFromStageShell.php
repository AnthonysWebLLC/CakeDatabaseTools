<?php
class DatabaseCloneFromStageShell extends Shell {
	// Hard coded configuration vairables
	// * These would be bad things to put in the repository, except we want the same configuration everywhere
	// * Stage's WordPress database is kinda acting like code for us to reference due to WordPress's plugin architecture
	public $stage_domain_mysql_login = '';
	public $stage_domain_mysql_pass = '';
	public $stage_domain = '';

	//var $uses = array('WpOption', 'WpPost', 'WpUser', 'User');
	public $uses = array('User');

	function initialize(){
		// Get domain from site's file structure
		// * Expects Anthony's convention of naming the folder the site is in by domain name
		$this->new_domain = substr(ROOT, max(strrpos(ROOT, '\\'), strrpos(ROOT, '/'))+1);

		$this->stage_domain_mysql_login = Configure::read('stage_domain_mysql_login');
		$this->stage_domain_mysql_pass = Configure::read('stage_domain_mysql_pass');
		$this->stage_domain = Configure::read('stage_domain');

		parent::initialize();
	}

	function main() {
		//////////////////////////////////////////////////////////////////////////////////
		// Auto config from known variables, etc
		App::Uses('ConnectionManager', 'Model');
		$db =& ConnectionManager::getDataSource('default');

		// Source's & Destination's 'Subdomains' & 'Top Level Domains'
		$SRC_DB = str_replace(array('.', '-'), array('_','_'), $this->stage_domain);
		$DST_DB = str_replace(array('.', '-'), array('_','_'), $this->new_domain);

		$ARCHIVEPATH	= ROOT.DS.'sql'.DS.'full'; # Where to archive data at (don't lose a thing!)

		// Timestamp
		$TS = date('Y-m-d_H-i-s');
		// END: Auto config from known variables, etc
		//////////////////////////////////////////////////////////////////////////////////

		// Archive Destination DB prior to overwritting
		echo "\nCreating '$DST_DB' DB backup...\n";
		passthru("mysqldump -h {$db->config['host']} -u {$db->config['login']} --password=\"{$db->config['password']}\" ${DST_DB}> $ARCHIVEPATH/${DST_DB}__${TS}.sql");

		// Clean up invisibly
		passthru("gzip $ARCHIVEPATH/${DST_DB}__${TS}.sql");

		// Clone Source DB to Destination
		echo "\nCreating '$SRC_DB' DB backup from remote server...\n";
		passthru("mysqldump -h $this->stage_domain --password=\"{$this->stage_domain_mysql_pass}\" -u {$this->stage_domain_mysql_login} ${SRC_DB} > $ARCHIVEPATH/${SRC_DB}__${TS}.sql");

		// Clone Source DB to Destination DB
		echo "\nImporting '$SRC_DB' DB backup to '$DST_DB' DB...\n";
		passthru("mysql -u {$db->config['login']} --password={$db->config['password']} ${DST_DB} < $ARCHIVEPATH/${SRC_DB}__${TS}.sql");

		// Clean up invisibly
		passthru("gzip $ARCHIVEPATH/${SRC_DB}__${TS}.sql");

		// Setup
		/*
		echo "\nSetting up $DST_DB's database as needed (domain switch for WordPress)...\n";
		echo "* Note: PHP Warnings about failed unserialization are a known issue which may cause some of the site's links to remain as stage. See DatabaseCloneFromStageShell::str_replace_possibly_serialized_subject(...) for reasoning.\n";
		$this->moveWPBlog();

		// Setup 2
		echo "* WordPress 'WPx' user passwords from ~/app/Config/local.php (So you can login @ /wp-admin/)...\n";
		$this->setWPPasswords();
		*/

		// Setup 3
		echo "* CakePHP's 'CakeX' user passwords from ~/app/Config/local.php (So you can login @ /c/user/login/)...\n";
		$this->setCakePasswords();

		echo "\nDatabase clone of '$SRC_DB' to '$DST_DB' complete with backups of each.\n";
	}

	/**
	 * Prepares local database for clone from stage.connectacal.com
	 * * By changing the domain in appropriate WordPress tables
	 * * More than a simple script str_replace due to WordPress's potentially serialized fields
	 */
	 /*
	private function moveWPBlog(){
		$WpOptions = $this->WpOption->find('all', array('fields'=>array($this->WpOption->primaryKey)));
		foreach($WpOptions AS $WpOption){
			$this->WpOption->read(null, $WpOption['WpOption'][$this->WpOption->primaryKey]);
			$this->WpOption->set('option_value', self::str_replace_possibly_serialized_subject($this->stage_domain, $this->new_domain, $this->WpOption->data['WpOption']['option_value']));
			$this->WpOption->save();
		}

		$WpPosts = $this->WpPost->find('all', array('fields'=>array($this->WpPost->primaryKey)));
		foreach($WpPosts AS $WpPost){
			$this->WpPost->read(null, $WpPost['WpPost'][$this->WpPost->primaryKey]);
			$this->WpPost->set('guid',			self::str_replace_possibly_serialized_subject($this->stage_domain, $this->new_domain, $this->WpPost->data['WpPost']['guid']));
			$this->WpPost->set('post_content',	self::str_replace_possibly_serialized_subject($this->stage_domain, $this->new_domain, $this->WpPost->data['WpPost']['post_content']));
			$this->WpPost->save();
		}
	}
	*/

	private function setCakePasswords(){
		App::Uses('AuthComponent', 'Controller/Component');
		$users = $this->User->find('all');
		$password = Configure::read("database_clone_from_stage_users_password");
		if ($password == '') {
			throw new Exception("Invalid value of '$password' in config value database_clone_from_stage_users_password");
		}
		foreach($users AS $user){
			$U = $user['User'];
			$this->User->read(null, $U['id']);
			$this->User->set('password', Configure::read("database_clone_from_stage_users_password"));
			if(!$this->User->save(null, false)){
				throw new Exception ("Failure setting password for Cake user (Validation errors:".print_r($this->User->validationErrors, true).")");
			}
		}
		return true;
	}

	/*
	private function setWPPasswords(){
		foreach(array('WPSubscriber', 'WPAdministrator', 'WPEditor', 'WPAuthor', 'WPContributor') AS $user_login){
			$WpUser = $this->WpUser->find('first', array('conditions'=>array('user_login'=>$user_login)));
			$this->WpUser->read(null, $WpUser['WpUser']['ID']);
			$this->WpUser->set('user_pass', md5(Configure::read("database_clone_from_stage_password_$user_login")));
			$this->WpUser->save();
		}
		return true;
	}

	static private function str_replace_possibly_serialized_subject($search, $replace, $subject){
		// Don't modify data without our search string
		if(!is_string($subject) OR false === strpos($subject, $search)){
			return $subject;
		}
		$reserialize = false;
		if(self::is_serialized($subject)){
			$origSubject = $subject;
			$subject = unserialize($subject);
			$reserialize = true;

			// Hack because sometimes we have poorly serialized data, which PHP throws warnings about and doesn't really unserializ
			// Serialization fail probably residing in WordPress or PHP it's self (maybe from different PHP versions?)  However this bandaid basically makes it so we don't modify data that won't unserialize.  There is ia potential workaround here possible by manually editing the serialized data, but that feels fragile so it's been skipped"
			if($subject === false && (strlen($origSubject) > 10)){
				return $origSubject;
			}
		}
		$subject = self::str_replace_recursive($search, $replace, $subject);
		if($reserialize){
			$subject = serialize($subject);
		}
		return $subject;
	}
	*/

	/**
	 * Run string replace on all strings in array.
	 * Also specifically return correct for one string, and return non-string non-array values as they are.
	 */
	 /*
	static private function str_replace_recursive($search, $replace, $subject){
		// Base case
		if(is_string($subject)){
			return str_replace($search, $replace, $subject);
		}

		// Recurse
		if(is_array($subject)){
			$newArr = array();
			foreach($subject AS $key=>$val){
				$newArr[$key] = self::str_replace_recursive($search, $replace, $val);
			}
			return $newArr;
		}

		return $subject;
	}

	// Method taken from WP as noted @ http://stackoverflow.com/questions/1369936/check-to-see-if-a-string-is-serialized
	static private function is_serialized($data){
		// if it isn't a string, it isn't serialized
		if ( !is_string( $data ) )
			return false;

		$data = trim( $data );

		if ( 'N;' == $data )
			return true;

		if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
			return false;

		switch ( $badions[1] ) {
			case 'a' :
			case 'O' :
			case 's' :
				if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
				return true;
				break;
			case 'b' :
			case 'i' :
			case 'd' :
				if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
					return true;
				break;
		}
		return false;
	}
	*/
}
?>
