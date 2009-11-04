<?php
namespace Glucose;
use \Glucose\Exceptions\Entity as E;
class Entity {
	private static $DB = true;
	private static $MODEL = false;
	
	private static $columns = array();
	
	private static $uniqueConstraints = array();
	
	private static $modelEntities = array();
	
	private static $dbEntities = array();
	
	/*
	 * Maybe use a balance binary tree here someday.
	 */
	private $modelHashes = array();
	
	private $dbHashes = array();
	
	private $className;
	
	private $instanceCount;
	
	public $fields;
	
	public function __construct($className) {
		$this->className = $className;
		$this->instanceCount = 0;
		$this->fields = new \ArrayObject();
		foreach(self::$columns[$this->className] as $column)
			$this->fields[$column->name] = new Field($column);
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
	
	public static function joinModel($className, array $identifier, Constraints\UniqueConstraint $constraint) {
			return self::join($className, $identifier, $constraint, self::$modelEntities);
	}
	
	public static function joinDB($className, array $identifier, Constraints\UniqueConstraint $constraint) {
		return self::join($className, $identifier, $constraint, self::$dbEntities);
	}
	
	private static function join($className, array $identifier, Constraints\UniqueConstraint $constraint, array $entities) {
		if(in_array(null, $identifier, true))
			throw new E\InvalidIdentifierException('When joining the identifier may not contain null');
		$hash = $this->hashIdentifier($identifier);
		if(array_key_exists($hash, $entities[$className][$constraint->name]))
			return $entities[$className][$constraint->name][$hash];
		else
			return null;
	}
	
	public function updateIdentifiersModel() {
		$this->updateIdentifiers(self::$MODEL);
	}
	
	public function updateIdentifiersDB() {
		$this->updateIdentifiers(self::$DB);
	}
	
	private function updateIdentifiers($database) {
		$hashesArray = $database?$this->dbHashes:$this->modelHashes;
		$entities = $database?self::$dbEntities[$this->className]:self::$modelEntities[$this->className];
		$newHashes = array();
		foreach(self::$uniqueConstraints[$this->className] as $constraint) {
			$hash = $this->hashConstraint($constraint, $database);
			if($hash != $hashesArray[$constraint->name] && $hash !== null)
				if(array_key_exists($hash, $entities[$constraint->name]))
					if($database)
						throw new E\DatabaseConstraintCollisionException(
							'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the database');
					else
						throw new E\ModelConstraintCollisionException(
							'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the model');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			if($hash != $hashesArray[$constraintName]) {
				unset($entities[$constraintName][$hashesArray[$constraintName]]);
				if($hash !== null) {
					$entities[$constraintName][$hash] = $this;
					$hashesArray[$constraintName] = $hash;
				} else {
					$hashesArray[$constraintName] = null;
				}
			}
		}
	}
	
	private function hashConstraint(Constraints\UniqueConstraint $constraint, $database) {
		$fieldValue = $database?'dbValue':'value';
		$compoundHash = '';
		foreach($constraint->columns as $column) {
			$value = $this->fields[$column->name]->{$fieldValue};
			if($value === null)
				return null;
			$compoundHash .= sha1($value);
		}
		return sha1($compoundHash);
	}
	
	private function hashIdentifier(array $identifier) {
		$compoundHash = '';
		foreach($identifier as $value)
			$compoundHash .= sha1($value);
		return sha1($compoundHash);
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