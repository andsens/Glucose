<?php
use \Glucose\Entity as Entity;
require_once 'PHPUnit/Framework.php';
/**
 * Test class for Entity.
 * Generated by PHPUnit on 2009-07-16 at 23:03:39.
 */
class EntityTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Entity
	 * @access protected
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp() {
		/*
		 * Manually setting up the database objects in order to avoid
		 * too much dependence on the Model class.
		 */
//		$this->classes = array('Country', 'City', 'Person');
//		$this->tables = array();
//		$inflector = Glucose\Inflector::getInstance();
//		foreach($this->classes as $className) {
//			$tableName = $inflector->tableize($className);
//			$table = new Glucose\Table($tableName);
//			Entity::initialize($className, $table->columns, $table->uniqueConstraints);
//			$this->tables[$className] = $table;
//		}
	}
	
	public function testAnonymousIndependenceModel() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		var_dump(get_declared_classes());
		$this->anonymousIndependenceTest('modelValue');
	}
	
	public function testAnonymousIndependenceDB() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->anonymousIndependenceTest('dbValue');
	}
	
	private function anonymousIndependenceTest($field) {
		$aarhus = new Entity('City');
		$aarhus->fields['name']->{$field} = 'Århus';
		$odense = new Entity('City');
		$odense->fields['name']->{$field} = 'Odense';
		$copenhagen = new Entity('City');
		$copenhagen->fields['id']->{$field} = 3;
		$copenhagen->fields['name']->{$field} = 'København';
		if($field == 'modelValue')
			$copenhagen->updateIdentifiersModel();
		else
			$copenhagen->updateIdentifiersDB();
		$this->assertEquals('Århus', $aarhus->fields['name']->value);
		$this->assertEquals('Odense', $odense->fields['name']->value);
		$this->assertEquals('København', $copenhagen->fields['name']->value);
	}
	
	public function testIndependenceInSameClassModel() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->independenceInSameClassTest('modelValue');
	}
	
	public function testIndependenceInSameClassDB() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->independenceInSameClassTest('dbValue');
	}
	
	private function independenceInSameClassTest($field) {
		$denmark = new Entity('Country');
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue')
			$denmark->updateIdentifiersModel();
		else
			$denmark->updateIdentifiersDB();
		$germany = new Entity('Country');
		$germany->fields['id']->{$field} = 2;
		$germany->fields['name']->{$field} = 'Deutschland';
		if($field == 'modelValue')
			$germany->updateIdentifiersModel();
		else
			$germany->updateIdentifiersDB();
		$this->assertEquals('Danmark', $denmark->fields['name']->value);
		$this->assertEquals('Deutschland', $germany->fields['name']->value);
	}
	
	public function testIndependenceInDifferentClassesModel() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->independenceInDifferentClassesTest('modelValue');
	}
	
	public function testIndependenceInDifferentClassesDB() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->independenceInDifferentClassesTest('dbValue');
	}
	
	private function independenceInDifferentClassesTest($field) {
		$denmark = new Entity('Country');
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue')
			$denmark->updateIdentifiersModel();
		else
			$denmark->updateIdentifiersDB();
		$copenhagen = new Entity('City');
		$copenhagen->fields['id']->{$field} = 3;
		$copenhagen->fields['name']->{$field} = 'København';
		if($field == 'modelValue')
			$copenhagen->updateIdentifiersModel();
		else
			$copenhagen->updateIdentifiersDB();
		$this->assertEquals('Danmark', $denmark->fields['name']->value);
		$this->assertEquals('København', $copenhagen->fields['name']->value);
	}
	
	public function testEntityJoiningModel() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$this->EntityJoiningTest('modelValue');
	}
	
	public function testEntityJoiningDB() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
//		$this->EntityJoiningTest('dbValue');
	}
	
	public function EntityJoiningTest($field) {
		$denmark = new Entity('Country');
		$denmark->fields['id']->{$field} = 1;
		$denmark->fields['name']->{$field} = 'Danmark';
		if($field == 'modelValue') {
			echo $field."Identifiers\n";
			$denmark->updateIdentifiersModel();
			echo $field."Join\n";
			$denmark2 = Entity::joinModel('Country', array(1), $this->tables['Country']->primaryKeyConstraint);
		} else {
			$denmark->updateIdentifiersDB();
			$denmark2 = Entity::joinDB('Country', array(1), $this->tables['Country']->primaryKeyConstraint);
		}
		$this->assertEquals($denmark->fields['name']->value, $denmark2->fields['name']->value);
	}
	
	public function testEntityEquality() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifer = array(1);
		$entity1 = new Entity($class, $identifer);
		$entity2 = new Entity($class, $identifer);
		$this->assertEquals($entity1, $entity2);
	}

	public function testConcurrency() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier = array(1);
		$entity1 = new Entity($class, $identifier);
		$entity1->testField = 2;
		$entity2 = new Entity($class, $identifier);
		$this->assertEquals(2, $entity1->testField);
		$this->assertEquals(2, $entity2->testField);
		$entity2->testField = 3;
		$this->assertEquals(3, $entity2->testField);
		$this->assertEquals(3, $entity1->testField);
	}

	public function testIdentifierUpdate() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier1 = array(1);
		$entity1 = new Entity($class, $identifier1);
		$entity1->testField = 3;

		$identifier2 = array(2);
		$entity1->updateIdentifier($identifier2);

		$this->assertEquals(3, $entity1->testField);

		$entity2 = new Entity($class, $identifier2);
		$this->assertEquals(3, $entity2->testField);
	}

	public function testCollisionDetection() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier1 = array(1);
		$entity1 = new Entity($class, $identifier1);
		$identifier2 = array(2);
		$entity2 = new Entity($class, $identifier2);
		$this->setExpectedException('EntityCollisionException', 'Identifier collision! The specified entity already exists.');
		$entity1->updateIdentifier($identifier2);
	}

	public function testAnonymousToIdentifiedConversion() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$entity1 = new Entity($class);
		$entity1->testField = 3;
		$identifier = array(2);
		$entity1->updateIdentifier($identifier);
		$entity2 = new Entity($class, $identifier);
		$this->assertEquals(3, $entity2->testField);
	}

	public function testInvalidIdentifer1() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier1 = array(1);
		$entity1 = new Entity($class, $identifier1);
		$identifier2 = array(2);
		$entity1->updateIdentifier($identifier2);
		$this->setExpectedException('EntityInvalidIdentifierException', 'The entity identifier is no longer valid.');
		$entity2 = new Entity($class, $identifier1);
	}

	public function testInvalidIdentifer2() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier1 = array(1);
		$entity1 = new Entity($class, $identifier1);
		$identifier2 = array(2);
		$entity1->updateIdentifier($identifier2);
		$this->setExpectedException('EntityInvalidIdentifierException', 'The entity identifier is no longer valid.');
		$entity2 = new Entity($class, $identifier1);
	}

	public function testUpdateToInvalidIdentifer() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier1 = array(1);
		$entity1 = new Entity($class, $identifier1);
		$entity1->testField = 1;
		$identifier2 = array(2);
		$entity1->updateIdentifier($identifier2);
		$entity2 = new Entity($class);
		$entity2->updateIdentifier($identifier1);
		$this->assertEquals(1, $entity1->testField);
		$this->assertNull($entity2->testField);
	}

	public function testSuccessfulDestruction() {
		$this->markTestIncomplete('Does not apply to refactored version yet');
		$class = 'DummyClass';
		$identifier = array(1);
		$entity1 = new Entity($class, $identifier);
		$entity1->testField = 1;
		unset($entity1);
		$entity2 = new Entity($class, $identifier);
		$this->assertNull($entity2->testField);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown() {
	}
}
?>
