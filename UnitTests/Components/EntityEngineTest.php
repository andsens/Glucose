<?php
use \Glucose\Entity as Entity;
use \Glucose\EntityEngine as EntityEngine;
require_once 'PHPUnit/Framework.php';

class EntityEngineTest extends PHPUnit_Framework_TestCase {

	private static $columns = array();
	private static $constraints = array();
	
	public static function setUpBeforeClass() {
		foreach(array('countries', 'cities', 'people') as $tableName) {
			$table = new Glucose\Table($tableName);
			self::$columns[$tableName] = $table->columns;
			self::$constraints[$tableName] = $table->uniqueConstraints;
		}
	}
	
	public function testAnonymousIndependenceModel() {
		$this->anonymousIndependenceTest('modelValue');
	}
	
	public function testAnonymousIndependenceDB() {
		$this->anonymousIndependenceTest('dbValue');
	}
	
	private function anonymousIndependenceTest($field) {
		$engine = new EntityEngine(self::$constraints['cities']);
		
		$aarhus = new Entity(self::$columns['cities']);
		$aarhus->fields['name']->{$field} = 'Århus';
		$odense = new Entity(self::$columns['cities']);
		$odense->fields['name']->{$field} = 'Odense';
		$copenhagen = new Entity(self::$columns['cities']);
		$copenhagen->fields['id']->{$field} = 3;
		$copenhagen->fields['name']->{$field} = 'København';
		if($field == 'modelValue')
			$engine->updateIdentifiersModel($copenhagen);
		else
			$engine->updateIdentifiersDB($copenhagen);
		$this->assertEquals('Århus', $aarhus->fields['name']->value);
		$this->assertEquals('Odense', $odense->fields['name']->value);
		$this->assertEquals('København', $copenhagen->fields['name']->value);
	}
	
	public function testIndependenceInSameClassModel() {
		$this->independenceInSameClassTest('modelValue');
	}
	
	public function testIndependenceInSameClassDB() {
		$this->independenceInSameClassTest('dbValue');
	}
	
	private function independenceInSameClassTest($field) {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark = new Entity(self::$columns['cities']);
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue')
			$engine->updateIdentifiersModel($denmark);
		else
			$engine->updateIdentifiersDB($denmark);
		$germany = new Entity(self::$columns['cities']);
		$germany->fields['id']->{$field} = 2;
		$germany->fields['name']->{$field} = 'Deutschland';
		if($field == 'modelValue')
			$engine->updateIdentifiersModel($germany);
		else
			$engine->updateIdentifiersDB($germany);
		$this->assertEquals('Danmark', $denmark->fields['name']->value);
		$this->assertEquals('Deutschland', $germany->fields['name']->value);
	}
	
	public function testIndependenceInDifferentClassesModel() {
		$this->independenceInDifferentClassesTest('modelValue');
	}
	
	public function testIndependenceInDifferentClassesDB() {
		$this->independenceInDifferentClassesTest('dbValue');
	}
	
	private function independenceInDifferentClassesTest($field) {
		$countryEngine = new EntityEngine(self::$constraints['countries']);
		
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue')
			$countryEngine->updateIdentifiersModel($denmark);
		else
			$countryEngine->updateIdentifiersDB($denmark);
		
		$countryEngine = new EntityEngine(self::$constraints['cities']);
		$copenhagen = new Entity(self::$columns['cities']);
		$copenhagen->fields['id']->{$field} = 3;
		$copenhagen->fields['name']->{$field} = 'København';
		if($field == 'modelValue')
			$countryEngine->updateIdentifiersModel($copenhagen);
		else
			$countryEngine->updateIdentifiersDB($copenhagen);
		$this->assertEquals('Danmark', $denmark->fields['name']->value);
		$this->assertEquals('København', $copenhagen->fields['name']->value);
	}
	
	public function testEntityFindingModel() {
		$this->EntityFindingTest('modelValue');
	}
	
	public function testEntityFindingDB() {
		$this->EntityFindingTest('dbValue');
	}
	
	public function EntityFindingTest($field) {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark = new Entity(self::$columns['countries']);
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue') {
			$engine->updateIdentifiersModel($denmark);
			$denmark2 = $engine->findModel(array(1), self::$constraints['countries']['PRIMARY']);
		} else {
			$engine->updateIdentifiersDB($denmark);
			$denmark2 = $engine->findDB(array(1), self::$constraints['countries']['PRIMARY']);
		}
		$this->assertEquals('Danmark', $denmark2->fields['name']->value);
		$this->assertEquals($denmark, $denmark2);
	}
	
	public function testConcurrencyModel() {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark1 = new Entity(self::$columns['countries']);
		$denmark1->fields['id']->modelValue = 1;
		$denmark1->fields['name']->modelValue = 'Danmark';
		$engine->updateIdentifiersModel($denmark1);
		$denmark2 = $engine->findModel(array(1), self::$constraints['countries']['PRIMARY']);
		$denmark3 = $engine->findModel(array(1), self::$constraints['countries']['PRIMARY']);
		
		$denmark2->fields['id']->dbValue = 2;
		$this->assertEquals(2, $denmark1->fields['id']->value);
		$this->assertEquals(2, $denmark3->fields['id']->value);
		$denmark3->fields['id']->modelValue = 3;
		$this->assertEquals(3, $denmark1->fields['id']->value);
		$this->assertEquals(3, $denmark2->fields['id']->value);
	}
	
	public function testCollisionDetectionModel() {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark1 = new Entity(self::$columns['countries']);
		$denmark1->fields['id']->modelValue = 1;
		$engine->updateIdentifiersModel($denmark1);
		
		$denmark2 = new Entity(self::$columns['countries']);
		$denmark2->fields['id']->modelValue = 1;
		
		$this->setExpectedException('Glucose\Exceptions\Entity\ModelConstraintCollisionException',
		'An entity with the same set of values for the unique constraint PRIMARY already exists in the model');
		$engine->updateIdentifiersModel($denmark2);
	}
	
	public function testFindInvalidIdentifer() {
		$engine = new EntityEngine(self::$constraints['countries']);
		$this->setExpectedException('Glucose\Exceptions\Entity\InvalidIdentifierException', 'The identifier may not contain null');
		$engine->findDB(array(null), self::$constraints['countries']['PRIMARY']);
	}
	
	public function testSuccessfulDestruction() {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark1 = new Entity(self::$columns['countries']);
		$denmark1->fields['id']->modelValue = 1;
		$engine->updateIdentifiersModel($denmark1);
		$denmark1->instanceCount++;
		$denmark1->instanceCount--;
		
		$denmark2 = $engine->findModel(array(1), self::$constraints['countries']['PRIMARY']);
		$this->assertNull($denmark2);
	}
	
	protected function tearDown() {
	}
}
?>
