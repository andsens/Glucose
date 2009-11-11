<?php
/**
 *
 * @author andsens
 * @package glucose
 *
 */
namespace Glucose;
use \Glucose\Exceptions\Entity as E;
class EntityEngine implements \SplObserver {
	
	private $modelEntities = array();
	private $dbEntities = array();
	
	private $entityModelHashes = array();
	private $entityDBHashes = array();
	
	private $uniqueConstraints;
	
	public function __construct(array $uniqueConstraints) {
		$this->uniqueConstraints = $uniqueConstraints;
		foreach($this->uniqueConstraints as $constraint) {
			$this->modelEntities[$constraint->name] = array();
			$this->dbEntities[$constraint->name] = array();
			$this->entityModelHashes[$constraint->name] = new \SplObjectStorage;
			$this->entityDBHashes[$constraint->name] = new \SplObjectStorage;
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
		$entity->attach($this);
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->modelEntities[$constraint->name]))
				if($this->modelEntities[$constraint->name][$hash] !== $entity)
					throw new E\ModelConstraintCollisionException(
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the model');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			$oldHash = null;
			if($this->entityModelHashes[$constraintName]->contains($entity))
				$oldHash = $this->entityModelHashes[$constraintName][$entity];
			if($hash != $oldHash) {
				unset($this->modelEntities[$constraintName][$oldHash]);
				if($hash !== null)
					$this->modelEntities[$constraintName][$hash] = $entity;
			}
			$this->entityModelHashes[$constraintName][$entity] = $hash;
		}
	}
	
	public function updateIdentifiersDB(Entity $entity) {
		$entity->attach($this);
		$newHashes = array();
		foreach($this->uniqueConstraints as $constraint) {
			$hash = $this->hashIdentifier($entity->getDBValues($constraint->columns));
			if($hash !== null && array_key_exists($hash, $this->dbEntities[$constraint->name]))
				if($this->dbEntities[$constraint->name][$hash] !== $entity)
					throw new E\DatabaseConstraintCollisionException( // This is fatal and should not happen AT ALL. It means the database screwed up.
						'An entity with the same set of values for the unique constraint '.$constraint->name.' already exists in the database');
			$newHashes[$constraint->name] = $hash;
		}
		foreach($newHashes as $constraintName => $hash) {
			$oldHash = null;
			if($this->entityDBHashes[$constraintName]->contains($entity))
				$oldHash = $this->entityDBHashes[$constraintName][$entity];
			if($hash != $oldHash) {
				unset($this->dbEntities[$constraintName][$oldHash]);
				if($hash !== null)
					$this->dbEntities[$constraintName][$hash] = $entity;
			}
			$this->entityDBHashes[$constraintName][$entity] = $hash;
		}
	}
	
	public function update(\SplSubject $entity) {
		if($entity->instanceCount == 0) {
			foreach($this->uniqueConstraints as $constraintName => $constraint) {
				if($this->entityModelHashes[$constraintName]->contains($entity)) {
					if($this->entityModelHashes[$constraintName]->contains($entity))
						$hash = $this->entityModelHashes[$constraintName][$entity];
					if($hash !== null)
						unset($this->modelEntities[$constraintName][$hash]);
					$this->entityModelHashes[$constraintName]->detach($entity);
				}
				
				if($this->entityDBHashes[$constraintName]->contains($entity)) {
						$hash = $this->entityDBHashes[$constraintName][$entity];
					if($hash !== null)
						unset($this->dbEntities[$constraintName][$hash]);
						$this->entityDBHashes[$constraintName]->detach($entity);
				}
			}
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