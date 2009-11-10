<?php
/**
 *
 * @author andsens
 * @package glucose
 *
 */
namespace Glucose;
use \Glucose\Exceptions\Entity as E;
class EntityEngine {
	
	private $modelEntities = array();
	private $dbEntities = array();
	
	private $uniqueConstraints;
	
	public function __construct(array $uniqueConstraints) {
		$this->uniqueConstraints = $uniqueConstraints;
		foreach($this->uniqueConstraints as $constraint) {
			$this->modelEntities[$constraint->name] = array();
			$this->dbEntities[$constraint->name] = array();
		}
	}
	
	public function findModel(array $identifier, Constraints\UniqueConstraint $constraint) {
		return $this->find($identifier, $constraint, $this->modelEntities);
	}
	
	public function findDB(array $identifier, Constraints\UniqueConstraint $constraint) {
		return $this->find($identifier, $constraint, $this->dbEntities);
	}
	
	private function find(array $identifier, Constraints\UniqueConstraint $constraint, array &$entities) {
		if(in_array(null, $identifier, true))
			throw new E\InvalidIdentifierException('The identifier may not contain null');
		$hash = $this->hashIdentifier($identifier);
		if(array_key_exists($hash, $entities[$constraint->name]))
			return $entities[$constraint->name][$hash];
		return null;
	}
	
	public function updateIdentifiersModel(Entity $entity) {
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getModelValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->modelEntities[$constraint->name]))
				if($this->modelEntities[$constraint->name][$hash] !== $entity)
					throw new E\ModelConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the model');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			unset($this->modelEntities[$constraintName][$entity->modelHashes[$constraintName]]);
			$this->modelEntities[$constraintName][$hash] = $entity;
			$entity->modelHashes[$constraintName] = $hash;
		}
	}
	
	public function updateIdentifiersDB(Entity $entity) {
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getDBValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->dbEntities[$constraint->name]))
				if($this->dbEntities[$constraint->name][$hash] !== $entity)
					throw new E\DatabaseConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the database');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			unset($this->dbEntities[$constraintName][$entity->dbHashes[$constraintName]]);
			$this->dbEntities[$constraintName][$hash] = $entity;
			$entity->dbHashes[$constraintName] = $hash;
		}
	}
	
	private static function hashIdentifier(array $identifier) {
		$compoundHash = '';
		foreach($identifier as $value)
			$compoundHash .= sha1($value);
		return sha1($compoundHash);
	}
}
?>