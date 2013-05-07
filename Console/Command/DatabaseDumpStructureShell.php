<?php
class DatabaseDumpStructureShell extends Shell {
	function main() {
		// Auto config from known variables, etc
		App::Uses('ConnectionManager', 'Model');
		$db =& ConnectionManager::getDataSource('default');

		$domain = substr(ROOT, max(strrpos(ROOT, '\\'), strrpos(ROOT, '/'))+1);

		$database = str_replace(array('.', '-'), array('_','_'), $domain); // Source's & Destination's 'Subdomains' & 'Top Level Domains'
		$STRUCTUREPATH=ROOT.DS.'sql'.DS.'structure.sql';

		echo "\nUpdating sql/structure.sql\n";
		passthru("mysqldump --no-data -h {$db->config['host']} -u {$db->config['login']} --password=\"{$db->config['password']}\" $database | sed 's/ AUTO_INCREMENT=[0-9]* \b/ /' > $STRUCTUREPATH");
		return;
	}
}
?>
