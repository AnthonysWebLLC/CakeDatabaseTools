<?php
class DatabaseDumpFullShell extends Shell {
	function main() {
		// Auto config from known variables, etc
		App::Uses('ConnectionManager', 'Model');
		$db =& ConnectionManager::getDataSource('default');

		$domain = substr(ROOT, max(strrpos(ROOT, '\\'), strrpos(ROOT, '/'))+1);

		$database = str_replace(array('.', '-'), array('_','_'), $domain); // Source's & Destination's 'Subdomains' & 'Top Level Domains'
		$FULLDUMPPATH=ROOT.DS.'sql'.DS.'full'.DS.exec("date '+%Y-%m-%d_%H-%M-%S'").'.sql';

		echo "\nDumping DB to sql/full/$FULLDUMPPATH.gz\n";
		passthru("mysqldump -h {$db->config['host']} -u {$db->config['login']} --password=\"{$db->config['password']}\" $database > $FULLDUMPPATH");
		passthru("gzip $FULLDUMPPATH");
	}
}
?>
