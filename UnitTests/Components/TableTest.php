<?php
require_once 'TableComparisonTestCase.class.php';

use \Glucose\Entity as Entity;
class TableTest extends TableComparisonTestCase {
	
	private static $tables;
	private static $mysqli;
	
	protected function setUp() {
		$this->comparisonSchema = $GLOBALS['comparisonSchema'];
		$this->actualSchema = $GLOBALS['schema'];
		
		self::$mysqli = $GLOBALS['mysqli'];
		self::$tables = array();
		foreach(array('countries', 'cities', 'people', 'users') as $tableName) {
			self::$tables[$tableName] = new Glucose\Table($tableName);
		}
		self::$mysqli->query('START TRANSACTION;');
	}
	
	protected function getConnection() {
		return self::$mysqli;
	}
	
	public function test_N_NonExistentTable() {
		$this->setExpectedException('Glucose\Exceptions\Table\MissingTableException', 'The table \'non_existent_table\' does not exist.');
		new Glucose\Table('non_existent_table');
	}
	
	public function test_P_Select1() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$this->assertEquals('Hamburg', $hamburg->fields['name']->value);
		$this->assertEquals('20095', $hamburg->fields['postal_code']->value);
	}
	
	public function test_P_Select2() {
		$people = self::$tables['people'];
		$anders = $people->select(array(1), $people->primaryKeyConstraint);
		$this->assertEquals('Anders', $anders->fields['first_name']->value);
	}
	
	public function test_P_SelectUniqueIdentifier1() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(1, 20095), $cities->uniqueConstraints['UNIQUE_cities__country__postal_code']);
		$this->assertEquals('Hamburg', $hamburg->fields['name']->value);
	}
	
	public function test_N_SelectNonexistentEntity() {
		$cities = self::$tables['cities'];
		$this->setExpectedException('Glucose\Exceptions\Table\NonExistentEntityException', 'The values you specified do not match any entry in the table.');
		$atlantis = $cities->select(array(0), $cities->primaryKeyConstraint);
	}
	
	public function test_P_PreviousSelect() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$hamburg2 = $cities->select(array(2), $cities->primaryKeyConstraint);
		$this->assertEquals($hamburg, $hamburg2);
		$this->assertTrue($hamburg === $hamburg2);
	}
	
	public function test_P_ChangeIdentifierSelect() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$hamburg->fields['id']->modelValue = 16;
		$cities->updateIdentifiers($hamburg);
		$hamburg2 = $cities->select(array(16), $cities->primaryKeyConstraint);
		$this->assertEquals($hamburg, $hamburg2);
		$this->assertTrue($hamburg === $hamburg2);
	}
	
	
	public function test_N_SelectOutdatedIdentifier() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$hamburg->fields['id']->modelValue = 16;
		$cities->updateIdentifiers($hamburg);
		$this->setExpectedException('Glucose\Exceptions\Table\EntityValuesChangedException', 'The values you specified no longer match an entity.');
		$hamburg2 = $cities->select(array(2), $cities->primaryKeyConstraint);
	}
	
	public function test_N_SelectWithInvalidIdentifier() {
		$cities = self::$tables['cities'];
		$people = self::$tables['people'];
		$this->setExpectedException('Glucose\Exceptions\Table\InvalidUniqueConstraintException', 'The unique constraint does not match any constraint in the table.');
		$hamburg = $cities->select(array('anders@ingemann.de'), $people->uniqueConstraints['UNIQUE_customers__email']);
	}
	
	public function test_P_Insert1() {
		$values = array(
			'country' => 2,
			'name' => 'København',
			'postal_code' => 1000);
		$cities = self::$tables['cities'];
		$copenhagen = $cities->newEntity();
		$copenhagen->referenceCount++;
		foreach($values as $field => $value)
			$copenhagen->fields[$field]->modelValue = $value;
		$copenhagen->referenceCount--;
		$insertID = $this->insertInto('cities', $values);
		$this->assertEquals($insertID, $copenhagen->fields['id']->value);
		$this->assertTablesEqual('cities');
	}
	
	public function test_P_Insert2() {
		$values = array(
			'first_name' => 'Anders',
			'last_name' => 'And',
			'email' => 'anders@ande.net',
			'address' => 'Paradisæblevej 111',
			'city' => 4);
		$people = self::$tables['people'];
		$donaldDuck = $people->newEntity();
		$donaldDuck->referenceCount++;
		foreach($values as $field => $value)
			$donaldDuck->fields[$field]->modelValue = $value;
		$donaldDuck->referenceCount--;
		$insertID = $this->insertInto('people', $values);
		$this->assertEquals($insertID, $donaldDuck->fields['id']->value);
		$this->assertTablesEqual('people');
	}
	
	public function test_P_SelectUpdate1() {
		$cities = self::$tables['cities'];
		$aarhus = $cities->select(array(1), $cities->primaryKeyConstraint);
		$aarhus->referenceCount++;
		$aarhus->fields['name']->modelValue = 'Smilets by';
		$aarhus->referenceCount--;
		
		$this->update('cities', array('id' => 1), array('name' => 'Smilets by'));
		$this->assertTablesEqual('cities');
	}
	
	public function test_P_SelectUpdate2() {
		$people = self::$tables['people'];
		$anders = $people->select(array(1), $people->primaryKeyConstraint);
		$anders->referenceCount++;
		$anders->fields['first_name']->modelValue = '';
		$anders->referenceCount--;
		
		$this->update('people', array('id' => 1), array('first_name' => ''));
		$this->assertTablesEqual('people');
	}
	
	public function test_P_Refresh() {
		$values = array(
			'person' => 2,
			'nickname' => 'eclecnant',
			'password' => sha1('secret'));
		$users = self::$tables['users'];
		$eclecnant = $users->newEntity();
		$eclecnant->referenceCount++;
		foreach($values as $field => $value)
			$eclecnant->fields[$field]->modelValue = $value;
		
		$insertStart = time();
		$eclecnant->referenceCount--;
		$this->insertInto('users', $values);
		$insertTime = time()-$insertStart;
		$this->assertTablesEqual('users', array('registered'));
		$users->syncWithDB($eclecnant);
		$comparisonRegistered = $this->selectSingle('users', 'registered', array('person'=>2));
		$this->assertLessThanOrEqual($insertTime, strtotime($comparisonRegistered)-strtotime($eclecnant->fields['registered']->value));
	}
	
	public function test_P_SelectUpdateNothing() {
		$people = self::$tables['people'];
		$anders = $people->select(array(1), $people->primaryKeyConstraint);
		$anders->referenceCount++;
		$anders->referenceCount--;
		
		$this->assertTablesEqual('people');
	}
	
	public function test_P_SelectUpdate_UpdateIdentifier_Select() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$hamburg->referenceCount++;
		$hamburg->fields['id']->modelValue = 16;
		$cities->updateIdentifiers($hamburg);
		$hamburg->referenceCount--;
		$hamburg2 = $cities->select(array(16), $cities->primaryKeyConstraint);
		$this->assertEquals('Hamburg', $hamburg2->fields['name']->value);
	}
	
	public function test_P_SelectDelete1() {
		$people = self::$tables['people'];
		$casper = $people->select(array(2), $people->primaryKeyConstraint);
		$casper->referenceCount++;
		$casper->deleted = true;
		$casper->referenceCount--;
		
		$this->deleteFrom('people', array('id' => 2));
		$this->assertTablesEqual('people');
	}
	
	public function test_P_SelectDelete2() {
		$cities = self::$tables['cities'];
		$hamburg = $cities->select(array(2), $cities->primaryKeyConstraint);
		$hamburg->referenceCount++;
		$hamburg->deleted = true;
		$hamburg->referenceCount--;
		
		$this->deleteFrom('cities', array('id' => 2));
		$this->assertTablesEqual('cities');
	}
	
	public function test_P_DeleteAnonymous() {
		$cities = self::$tables['cities'];
		$copenhagen = $cities->newEntity();
		$copenhagen->referenceCount++;
		$copenhagen->fields['country']->modelValue = 2;
		$copenhagen->fields['name']->modelValue = 'København';
		$copenhagen->fields['postal_code']->modelValue = 1000;
		$copenhagen->deleted = true;
		$copenhagen->referenceCount--;
		$this->assertTablesEqual('cities');
	}
	
	public function test_N_Collision() {
		$countries = self::$tables['countries'];
		$uganda1 = $countries->newEntity();
		$uganda1->fields['name']->modelValue = 'Uganda';
		$countries->updateIdentifiers($uganda1);
		$uganda2 = $countries->newEntity();
		$uganda2->fields['name']->modelValue = 'Uganda';
		$this->setExpectedException('Glucose\Exceptions\Entity\ModelConstraintCollisionException',
		'An entity with the same set of values for the unique constraint UNIQUE_countries__name already exists in the model.');
		$countries->updateIdentifiers($uganda2);
		$this->assertTablesEqual('countries');
	}
	
	protected function tearDown() {
		self::$mysqli ->query('ROLLBACK;');
	}
}
?>
