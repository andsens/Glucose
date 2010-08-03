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
	
	private $uniqueConstraints;
	
	private $modelEntities = array();
	private $dbEntities = array();
	
	private $modelHashes = array();
	private $dbHashes = array();
	
	public function __construct(array $uniqueConstraints) {
		$this->uniqueConstraints = $uniqueConstraints;
		foreach($this->uniqueConstraints as $constraint) {
			$this->modelEntities[$constraint->name] = array();
			$this->dbEntities[$constraint->name] = array();
			$this->modelHashes[$constraint->name] = new \SplObjectStorage;
			$this->dbHashes[$constraint->name] = new \SplObjectStorage;
		}
	}
	
	public function newEntity() {
		
	}
	
	public function getEntityByPrimaryKey(array $primaryKeyValues) {
		
	}
	
	public function getEntity(Constraints\UniqueConstraint $constraint, $constraintValues) {
		
	}
	
	public function columnValueChanged(Entity $entity, Column $column) {
		
	}
	
	public function constraintValuesChanged(Entity $entity, Constraint\Constraint $column) {
		
	}
	
	public function isColliding(array $entityValues) {
		
	}
	
	public function findModel(array $identifier, Constraints\UniqueConstraint $constraint) {
		return $this->find($identifier, $constraint, $this->modelEntities);
	}
	
	public function findDB(array $identifier, Constraints\UniqueConstraint $constraint) {
		return $this->find($identifier, $constraint, $this->dbEntities);
	}
	
	private function find(array $identifier, Constraints\UniqueConstraint $constraint, array &$entities) {
		if(in_array(null, $identifier, true))
			return null;
		$hash = $this->hashIdentifier($identifier);
		if(array_key_exists($hash, $entities[$constraint->name]))
			return $entities[$constraint->name][$hash];
		return null;
	}
	
	/**
	 *
	 * @throws Glucose\Exceptions\Entity\ModelConstraintCollisionException
	 */
	public function updateIdentifiersModel(Entity $entity) {
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->modelEntities[$constraint->name]))
				if($this->modelEntities[$constraint->name][$hash] !== $entity)
					throw new E\ModelConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the model.');
			$newHashes[$constraint->name] = $hash;
			if($constraint instanceof \Glucose\Constraints\PrimaryKeyConstraint)
				$entity->identifier = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			$oldHash = null;
			if($this->modelHashes[$constraintName]->contains($entity))
				$oldHash = $this->modelHashes[$constraintName][$entity];
			if($hash != $oldHash) {
				unset($this->modelEntities[$constraintName][$oldHash]);
				if($hash !== null)
					$this->modelEntities[$constraintName][$hash] = $entity;
			}
			$this->modelHashes[$constraintName][$entity] = $hash;
		}
	}
	
	
	/**
	 *
	 * @throws Glucose\Exceptions\Entity\DatabaseConstraintCollisionException
	 */
	public function updateIdentifiersDB(Entity $entity) {
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getDBValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->dbEntities[$constraint->name]))
				if($this->dbEntities[$constraint->name][$hash] !== $entity)
					throw new E\DatabaseConstraintCollisionException( // This is fatal and should not happen AT ALL. It means the database screwed up.
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the database.');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			$oldHash = null;
			if($this->dbHashes[$constraintName]->contains($entity))
				$oldHash = $this->dbHashes[$constraintName][$entity];
			if($hash != $oldHash) {
				unset($this->dbEntities[$constraintName][$oldHash]);
				if($hash !== null)
					$this->dbEntities[$constraintName][$hash] = $entity;
			}
			$this->dbHashes[$constraintName][$entity] = $hash;
		}
	}
	
	public function dereference(Entity $entity) {
		foreach($this->uniqueConstraints as $constraintName => $constraint) {
			if($this->modelHashes[$constraintName]->contains($entity)) {
				$hash = $this->modelHashes[$constraintName][$entity];
				if($hash !== null)
					unset($this->modelEntities[$constraintName][$hash]);
				$this->modelHashes[$constraintName]->detach($entity);
			}
			if($this->dbHashes[$constraintName]->contains($entity)) {
				$hash = $this->dbHashes[$constraintName][$entity];
				if($hash !== null)
					unset($this->dbEntities[$constraintName][$hash]);
				$this->dbHashes[$constraintName]->detach($entity);
			}
		}
	}
	
	private static function hashIdentifier(array $identifier) {
		$compoundHash = '';
		foreach($identifier as $value)
			if($value === null)
				return null;
			else
				$compoundHash .= sha1($value);
		return sha1($compoundHash);
	}
}