<?php
namespace Glucose;
use Exceptions\Entity as E;
class Entity {
	
	private static $columns = array();
	
	private static $uniqueConstraints = array();
	
	private static $modelEntities = array();
	
	private static $dbEntities = array();
	
	private $className;
	
	private $instanceCount;
	
	private $modelHashes = array();
	
	private $dbHashes = array();
	
	public $fields;
	
	public function __construct($className) {
		$this->className = $className;
		$this->fields = new \ArrayObject();
		foreach(self::$columns[$this->className] as $column)
			$this->fields[$column->name] = new Field($column);
		$this->instanceCount = 0;
		foreach(self::$uniqueConstraints[$this->className] as $constraint) {
			$this->dbHashes[$constraint->name] = null;
			$this->modelHashes[$constraint->name] = null;
		}
	}
	
	public static function initialize($className, array $columns, array $uniqueConstraints) {
		if(!array_key_exists($className, self::$columns))
			self::$columns[$className] = $columns;
		if(!array_key_exists($className, self::$modelEntities)) {
			self::$modelEntities[$className] = array();
			foreach($uniqueConstraints as $constraint)
				self::$modelEntities[$className][$constraint->name] = array();
		}
		if(!array_key_exists($className, self::$dbEntities)) {
			self::$dbEntities[$className] = array();
			foreach($uniqueConstraints as $constraint)
				self::$dbEntities[$className][$constraint->name] = array();
		}
		if(!array_key_exists($className, self::$uniqueConstraints))
			self::$uniqueConstraints[$className] = $uniqueConstraints;
	}
	
	public static function join($className, array $identifier, Constraints\UniqueConstraint $constraint) {
		if(in_array(null, $identifier, true))
			throw new E\InvalidIdentifierException('When joining the identifier may not contain null');
		$compoundHash = '';
		foreach($identifier as $value)
			$compoundHash .= sha1($value);
		$hash = sha1($compoundHash);
		if(array_key_exists($hash, self::$modelEntities[$className][$constraint->name]))
			return self::$entities[$className][$constraint->name];
		else
			return new Entity($className);
	}
	
	public function updateIdentifiers() {
		foreach(self::$uniqueConstraints[$this->className] as $constraint) {
			$this->updateHash($constraint, false);
			$this->updateHash($constraint, true);
		}
	}
	
	private function updateHash(Constraints\UniqueConstraint $constraint, $database = false) {
		$fieldValue = $database?'dbValue':'value';
		$hashesArray = $database?$this->dbHashes:$this->modelHashes;
		$entities = $database?self::$dbEntities[$this->className][$constraint->name]:self::$modelEntities[$this->className][$constraint->name];
		$compoundHash = '';
		foreach($constraint->columns as $column) {
			if($this->fields[$column->name]->{$fieldValue} == null) {
				if($hashesArray[$constraint->name] != null) {
					unset($entities[$hashesArray[$constraint->name]]);
					$hashesArray[$constraint->name] = null;
				}
				return;
			}
			$compoundHash .= sha1($this->fields[$column->name]->{$fieldValue});
		}
		$hash = sha1($compoundHash);
		if($hash != $hashesArray[$constraint->name]) {
			if(array_key_exists($hash, $entities))
				if($database)
					throw new E\DatabaseConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the database');
				else
					throw new E\ModelConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the model');
			
			$entities[$hash] = $this;
			if($hashesArray[$constraint->name] != null)
				unset($entities[$hashesArray[$constraint->name]]);
			$hashesArray[$constraint->name] = $hash;
		}
	}
	
	private $shouldBeInDB;
	
	private $existsInDB;
	
	private $deleted;
	
	public function __set($name, $value) {
		switch($name) {
			case 'shouldBeInDB':
				$this->shouldBeInDB = $value;
				break;
			case 'existsInDB':
				if($value === true)
					$this->deleted = false;
				$this->shouldBeInDB = $value;
				$this->existsInDB = $value;
				break;
			case 'deleted':
				if($value === true)
					$this->shouldBeInDB = false;
					$this->existsInDB = false;
				$this->deleted = $value;
				break;
		}
	}
	
	public function __get($name) {
		switch($name) {
			case 'shouldBeInDB':
				return $this->shouldBeInDB;
			case 'existsInDB':
				return $this->existsInDB;
			case 'deleted':
				return $this->deleted;
			case 'instanceCount':
				return $this->instanceCount;
		}
	}
}
?>