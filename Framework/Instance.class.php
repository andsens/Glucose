<?php
class Instance {
	private static $instances = array();
	private static $instancesCount= array();
	
	private $fields;
	private $instanceCount;
	private $className;
	
	public function __construct($className, array $identifier = null) {
		$this->className = $className;
		if($identifier !== null) {
			$hash = $this->createHash($identifier);
			if(!array_key_exists($hash, self::$instances)) {
				self::$instances[$hash] = array();
				self::$instancesCount[$hash] = 0;
			} else {
			}
			$this->instanceCount = &self::$instancesCount[$hash];
			$this->fields = &self::$instances[$hash];
		} else {
			$this->fields = array();
			$this->instanceCount = 0;
		}
		$this->instanceCount++;
	}
	
	public function hasBeenConstructed() {
		return $this->instanceCount > 1;
	}
	
	public function updateIdentifier(array $newIdentifier, array $oldIdentifier = null) {
		$newHash = $this->createHash($newIdentifier);
		if($oldIdentifier !== null) {
			$oldHash = $this->createHash($oldIdentifier);
			if($newHash == $oldHash)
				return;
			if(array_key_exists($newHash, self::$instances))
				throw new InstanceException('Primary key collision! The specified key has already been initialized.');
			$oldHash = $this->createHash($oldIdentifier);
			self::$instances[$newHash] = &self::$instances[$oldHash];
			self::$instancesCount[$newHash] = &self::$instancesCount[$oldHash];
			unset(self::$instances[$oldHash]);
			unset(self::$instancesCount[$oldHash]);
		} else {
			if(array_key_exists($newHash, self::$instances))
				throw new InstanceException('Primary key collision! The specified key has already been initialized.');
			self::$instances[$newHash] = &$this->fields;
			self::$instancesCount[$newHash] = &$this->instanceCount;
		}
	}
	
	private function createHash(array $identifier) {
		$compoundHash = sha1($this->className);
		foreach($identifier as $value)
			$compoundHash .= sha1($value);
		return sha1($compoundHash);
	}
	
	public function __get($name) {
		if(array_key_exists($name, $this->fields))
			return $this->fields[$name];
	}
	
	public function __set($name, $value) {
		$this->fields[$name] = $value;
	}
	
	public function isLastInstance() {
		return $this->instanceCount <= 1;
	}
	
	public function removeInstance(array $identifier = null) {
		$this->instanceCount--;
		if($this->instanceCount == 0) {
			if($identifier !== null) {
				$hash = $this->createHash($identifier);
				unset(self::$instances[$hash]);
				unset(self::$instancesCount[$hash]);
			}
			return true;
		} else {
			return false;
		}
	}
}
?>