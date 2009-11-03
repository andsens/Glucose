<?php
require_once "phing/Task.php";
class ConnectMySQLiTask extends Task {
	private $host;
	private $user;
	private $pass;
	private $schema;

	public function setHost($host) { $this->host = $host; }
	public function setUser($user) { $this->user = $user; }
	public function setPass($pass) { $this->pass = $pass; }
	public function setSchema($schema) { $this->schema = $schema; }
    public function init() {}
	
	public function main() {
		$GLOBALS['mysqli'] = new MySQLi($this->host, $this->user, $this->pass);
		$GLOBALS['mysqli']->select_db($this->schema);
	}
}
?>