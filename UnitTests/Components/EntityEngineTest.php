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
	
	public function test_P_AnonymousIndependenceModel() {
		$this->anonymousIndependenceTest('modelValue');
	}
	
	public function test_P_AnonymousIndependenceDB() {
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
	
	public function test_P_IndependenceInSameClassModel() {
		$this->independenceInSameClassTest('modelValue');
	}
	
	public function test_P_IndependenceInSameClassDB() {
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
	
	public function test_P_IndependenceInDifferentClassesModel() {
		$this->independenceInDifferentClassesTest('modelValue');
	}
	
	public function test_P_IndependenceInDifferentClassesDB() {
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
	
	public function test_P_EntityFindingModel() {
		$this->EntityFindingTest('modelValue');
	}
	
	public function test_P_EntityFindingDB() {
		$this->EntityFindingTest('dbValue');
	}
	
	private function EntityFindingTest($field) {
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
	
	public function test_P_ConcurrencyModel() {
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
	
	public function test_N_CollisionDetectionModel1() {
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
	
	public function test_N_CollisionDetectionModel2() {
		$engine = new EntityEngine(self::$constraints['cities']);
		
		$copenhagen1 = new Entity(self::$columns['cities']);
		$copenhagen1->fields['country']->modelValue = 1;
		$copenhagen1->fields['postal_code']->modelValue = 1000;
		$engine->updateIdentifiersModel($copenhagen1);
		
		$copenhagen2 = new Entity(self::$columns['cities']);
		$copenhagen2->fields['country']->modelValue = 1;
		$copenhagen2->fields['postal_code']->modelValue = 1000;
		
		$this->setExpectedException('Glucose\Exceptions\Entity\ModelConstraintCollisionException',
		'An entity with the same set of values for the unique constraint UNIQUE_cities__country__postal_code already exists in the model');
		$engine->updateIdentifiersModel($copenhagen2);
	}
	
	public function test_N_FindInvalidIdentifer() {
		$engine = new EntityEngine(self::$constraints['countries']);
		$this->setExpectedException('Glucose\Exceptions\Entity\InvalidIdentifierException', 'The identifier may not contain null');
		$engine->findDB(array(null), self::$constraints['countries']['PRIMARY']);
	}
	
	public function test_P_SuccessfulDestruction() {
		$engine = new EntityEngine(self::$constraints['countries']);
		
		$denmark1 = new Entity(self::$columns['countries']);
		$denmark1->fields['id']->modelValue = 1;
		$engine->updateIdentifiersModel($denmark1);
		$engine->dereference($denmark1);
		
		$denmark2 = $engine->findModel(array(1), self::$constraints['countries']['PRIMARY']);
		$this->assertNull($denmark2);
	}
	
	protected function tearDown() {
	}
}
?>
