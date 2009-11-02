<?php
abstract class Model {
	/**
	 * Array of models that have been initialized
	 * @var array
	 */
	private static $models = array();
	
	/**
	 * CakePHPs Inflector class, slightly modified.
	 * Works quite well.
	 * @var Inflector
	 */
	private static $inflector;
	
	/**
	 * The table this model is associated with
	 * @var Table
	 */
	private $table;
	
	/**
	 * Allows multiple instances with the
	 * same PK, to point to the same fields
	 * @var InstanceController
	 */
	private $instance;
	
	public final function __construct() {
		$className = get_class($this);
		$this->initializeModel();
		$table = self::$models[$className]['table'];
		$values = new ArrayObject();
		$arguments = func_get_args();
		if(count($arguments) > 0) {
			if(count($table->primaryKeys) != count($arguments))
				throw new ModelException('Wrong argument count!');
			if(in_array(null, $arguments, true))
				throw new ModelException('Illegal argument [null]!');
			$this->instance = new Instance($className, $arguments);
			if(!$this->instance->hasBeenConstructed()) {
				foreach($table->fields as $field)
					$values[$field->name] = new FieldValue($field);
				for($i = 0; $i < count($table->primaryKeys); $i++)
					$values[$table->primaryKeys[$i]->name]->dbValue = $arguments[$i];
				$this->instance->values = $values;
				$this->instance->shouldBeInDB = true;
			}
		} else {
			$this->instance = new Instance($className);
			foreach($table->fields as $field)
				$values[$field->name] = new FieldValue($field);
			$this->instance->values = $values;
			$this->instance->shouldBeInDB = false;
		}
		$this->table = $table;
	}
	
	public static final function connect(mysqli $mysqli) {
		if(!isset(self::$inflector))
			Model::$inflector = new Inflector();
		if(!isset(self::$models))
			self::$models = array();
		Table::prepareStatements($mysqli);
	}
	
	private final function initializeModel() {
		$className = get_class($this);
		if(!array_key_exists($className, self::$models)) {
			self::$models[$className] = array();
			$tableName = Model::$inflector->tableize($className);
			self::$models[$className]['table'] = new Table($tableName);
		}
	}
	
	private function getPrimaryKeyValues() {
		$primaryKeyValues = array();
		foreach($this->table->primaryKeys as $field) {
			$primaryKeyValues[] = $this->instance->values[$field->name]->value;
		}
		return $primaryKeyValues;
	}
	
	private final function createModel() {
		$oldPrimaryKeysValues = $this->getPrimaryKeyValues();
		$insertValues = array();
		$insertTypes = '';
		foreach($this->instance->values as $value) {
			if(!isset($value->value))
				$insertValues[] = $value->field->default;
			else
				$insertValues[] = $value->value;
			$insertTypes .= $value->field->statementType;
		}
		$insertID = $this->table->insert($insertValues, $insertTypes);
		// TODO: Duplicate key error
		foreach($this->instance->values as $value)
			if($value->updateModel && $value->field === $this->table->primaryKeyConstraint->autoIncrementField)
				$value->dbValue = $insertID;
			else
				$value->dbUpdated();
		$this->updateIdentifier($oldPrimaryKeysValues);
		$this->instance->shouldBeInDB = true;
	}
	
	private final function updateDB() {
		$updateValues = array();
		$updateTypes = '';
		foreach($this->instance->values as $value) {
			if($value->updateDB) {
				$updateValues[$value->field->name] = $value->value;
				$updateTypes .= $value->field->statementType;
			}
		}
		if(count($updateValues) > 0) {
			$this->table->update($updateValues, $updateTypes, $this->getPrimaryKeyValues());
			foreach($updateValues as $fieldName => $value)
				if($this->instance->values[$fieldName] !== $this->table->primaryKeyConstraint->autoIncrementField)
					$this->instance->values[$fieldName]->dbUpdated();
			// TODO: Check for PK defined, num affected rows
		}
	}
	
	private final function updateModel() {
		foreach($this->instance->values as $value) {
			if($value->updateModel) {
				$primaryKeyValues = $this->getPrimaryKeyValues();
				$newValues = $this->table->select($primaryKeyValues);
				if($newValues == null)
					throw new UndefinedPrimaryKeysException(
						'The primary key(-s) you specified are not represented in the database.');
				else
					foreach($this->instance->values as $fieldValue)
						if($fieldValue && $fieldValue->updateModel)
							$fieldValue->dbValue = $newValues[$fieldValue->field->name];
				$this->updateIdentifier($primaryKeyValues);
				break;
			}
		}
	}
	
	private final function updateIdentifier(array $oldIdentifier = null) {
		$primaryKeyValues = $this->getPrimaryKeyValues();
		if($oldIdentifier !== null && in_array(null, $oldIdentifier, true))
			$oldIdentifier = null;
		if($primaryKeyValues !== null && !in_array(null, $primaryKeyValues, true))
			$this->instance->updateIdentifier($primaryKeyValues, $oldIdentifier);
	}
	
	public function __set($name, $value) {
		$name = Model::$inflector->underscore($name);
		if(isset($this->instance->values[$name])) {
			if($this->instance->values[$name]->value !== $value) {
				if(in_array($this->instance->values[$name]->field, $this->table->primaryKeys)) {
					$oldPrimaryKeyValues = $this->getPrimaryKeyValues();
					$this->instance->values[$name]->modelValue = $value;
					$this->updateIdentifier($oldPrimaryKeyValues);
				} else {
					$this->instance->values[$name]->modelValue = $value;
				}
			}
		} else {
			throw new Exception();
		}
	}
	
	public function __get($name) {
		$name = Model::$inflector->underscore($name);
		if(isset($this->instance->values[$name])) {
			if($this->instance->values[$name]->updateModel) {
				if($this->instance->shouldBeInDB) {
					$this->updateModel();
				} else {
					$this->createModel();
					if($this->instance->values[$name]->updateModel) {
						$this->updateModel();
					}
				}
			}
			return $this->instance->values[$name]->value;
		} else {
			throw new Exception();
		}
	}
	
	public final function __isset($name) {
		return isset($this->instance->values[$name]->value);
	}
	
	public final function __unset($name) {
		unset($this->instance->values[$name]->value);
	}
	
	public final function forceUpdate() {
		if($this->instance->shouldBeInDB)
			$this->updateDB();
		else
			$this->createModel();
	}
	
	public final function __destruct() {
		if($this->instance->isLastInstance())
			$this->forceUpdate();
		$this->instance->removeInstance($this->getPrimaryKeyValues());
	}
}
?>