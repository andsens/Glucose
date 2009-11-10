<?php
require_once "phing/Task.php";
class CountLinesTask extends Task {
	private $dir;
	private $extensions;

	public function setDir($dir) { $this->dir = $dir; }
	public function setExtensions($extensions) { $this->extensions = explode(';', $extensions); }
    public function init() {}
	
	public function main() {
		$this->traverseDir($this->dir);
		echo "\n";
	}
	
	private function traverseDir($dir) {
		$entries = scandir($dir);
		$lines = 0;
		foreach($entries as $entry) {
			$path = $dir.'/'.$entry;
			if(is_dir($path) && $entry != '.' && $entry != '..')
				$lines += $this->traverseDir($path);
			foreach($this->extensions as $extension)
				if(substr($entry, -strlen($extension)) == $extension)
					$lines += $this->countlines($path);
			echo "\r ".$lines." lines of code";
		}
		return $lines;
	}
	
	private function countlines($path) {
		$matches;
		$noMatches = preg_match_all('/(\r\n)/', file_get_contents($path), $matches);
		if($noMatches === false)
			return 0;
		else
			return $noMatches;
	}
}
?>