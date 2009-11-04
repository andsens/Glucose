<?php
use \Glucose\Entity as Entity;
use \Glucose\Column as Column;
use \Glucose\Constraints\UniqueConstraint as UniqueConstraint;
use \Glucose\Constraints\PrimaryKeyConstraint as PrimaryKeyConstraint;
require_once 'PHPUnit/Framework.php';
/**
 * Test class for Entity.
 * Generated by PHPUnit on 2009-07-16 at 23:03:39.
 */
class EntityTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Entity
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
    	 * too much dependence on other classes.
    	 */
    	$this->countryClass = 'Country';
    	$this->countryId = new Column('id', 'int', null, true, null);
    	$this->countryName = new Column('name', 'varchar', 255, false, null);
    	$this->countryColumns = array($this->countryId, $this->countryName);
    	$this->countryPrimaryKey = new PrimaryKeyConstraint('PRIMARY');
    	$this->countryPrimaryKey->addColumn($this->countryId);
    	$this->countryPrimaryKey->autoIncrementColumn = $this->countryId;
    	$this->countryConstraints = array($this->countryPrimaryKey);
    	
    	Entity::initialize($this->countryClass, $this->countryColumns, $this->countryConstraints);
    	
    	$this->cityClass = 'City';
    	$this->cityId = new Column('id', 'int', null, true, null);
    	$this->cityCountry = new Column('country', 'int', null, true, null);
    	$this->cityName = new Column('name', 'varchar', 255, true, null);
    	$this->cityPostalCode = new Column('postal_code', 'int', null, true, null);
    	$this->cityColumns = array($this->cityId, $this->cityCountry, $this->cityName, $this->cityPostalCode);
    	$this->cityPrimaryKey = new PrimaryKeyConstraint('PRIMARY');
    	$this->cityPrimaryKey->addColumn($this->cityId);
    	$this->cityPrimaryKey->autoIncrementColumn = $this->cityId;
    	$this->cityConstraints = array($this->cityPrimaryKey);
    	
    	Entity::initialize($this->cityClass, $this->cityColumns, $this->cityConstraints);
    	
    	$this->personClass = 'Person';
    	$this->personId = new Column('id', 'int', null, true, null);
    	$this->personFirstName = new Column('first_name', 'varchar', 255, true, null);
    	$this->personLastName = new Column('last_name', 'varchar', 255, true, null);
    	$this->personEmail = new Column('email', 'varchar', 255, false, null);
    	$this->personAddress = new Column('address', 'varchar', 255, true, null);
    	$this->personColumns = array($this->personId, $this->personFirstName,
    		$this->personLastName, $this->personEMail, $this->personAddress);
    	$this->personPrimaryKey = new PrimaryKeyConstraint('PRIMARY');
    	$this->personPrimaryKey->addColumn($this->personId);
    	$this->personPrimaryKey->autoIncrementColumn = $this->personId;
    	$this->personEmailConstraint = new UniqueConstraint('UNIQUE_customers__email');
    	$this->personEmailConstraint->addColumn($this->personEmail);
    	$this->personConstraints = array($this->personPrimaryKey, $this->personEmailConstraint);
    	
    	Entity::initialize($this->personClass, $this->personColumns, $this->personConstraints);
    }
    
    public function testAnonymousIndependenceModel() {
    	$class = $this->cityClass;
    	$idField = $this->cityId->name;
    	$valuefield = $this->cityName->name;
    	$entity1 = new Entity($this->cityClass);
    	$entity1->fields[$valuefield]->modelValue = 'Århus';
    	$entity2 = new Entity($class);
    	$entity2->fields[$valuefield]->modelValue = 'Odense';
    	$entity3 = new Entity($class);
    	$entity3->fields[$idField]->modelValue = 3;
    	$entity3->fields[$valuefield]->modelValue = 'København';
    	$entity3->updateIdentifiersModel();
    	$this->assertEquals('Århus', $entity1->fields[$valuefield]->value);
    	$this->assertEquals('Odense', $entity2->fields[$valuefield]->value);
    	$this->assertEquals('København', $entity3->fields[$valuefield]->value);
    }
    
    public function testIndependenceInSameClass() {
    	$class = 'DummyClass';
    	$entity1 = new Entity($class, array(1, 2, 3));
    	$entity1->testField = 1;
    	$entity2 = new Entity($class, array(1, 2, 4));
    	$entity2->testField = 2;
    	$this->assertEquals(1, $entity1->testField);
    	$this->assertEquals(2, $entity2->testField);
    }
    
    public function testIndependenceInDifferentClasses() {
    	$entity1 = new Entity('Class1', array(1, 2));
    	$entity1->testField = 3;
    	$entity2 = new Entity('Class2', array(1, 2));
    	$entity2->testField = 4;
    	$this->assertEquals(3, $entity1->testField);
    	$this->assertEquals(4, $entity2->testField);
    }
    
    public function testEntityEquality() {
    	$class = 'DummyClass';
    	$identifer = array(1);
    	$entity1 = new Entity($class, $identifer);
    	$entity2 = new Entity($class, $identifer);
    	$this->assertEquals($entity1, $entity2);
    }
    
    public function testConcurrency() {
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
    	$class = 'DummyClass';
    	$identifier1 = array(1);
    	$entity1 = new Entity($class, $identifier1);
    	$identifier2 = array(2);
    	$entity2 = new Entity($class, $identifier2);
    	$this->setExpectedException('EntityCollisionException', 'Identifier collision! The specified entity already exists.');
    	$entity1->updateIdentifier($identifier2);
    }
    
    public function testAnonymousToIdentifiedConversion() {
    	$class = 'DummyClass';
    	$entity1 = new Entity($class);
    	$entity1->testField = 3;
    	$identifier = array(2);
    	$entity1->updateIdentifier($identifier);
    	$entity2 = new Entity($class, $identifier);
    	$this->assertEquals(3, $entity2->testField);
    }
    
    public function testInvalidIdentifer1() {
    	$class = 'DummyClass';
    	$identifier1 = array(1);
    	$entity1 = new Entity($class, $identifier1);
    	$identifier2 = array(2);
    	$entity1->updateIdentifier($identifier2);
    	$this->setExpectedException('EntityInvalidIdentifierException', 'The entity identifier is no longer valid.');
    	$entity2 = new Entity($class, $identifier1);
    }
    
    public function testInvalidIdentifer2() {
    	$class = 'DummyClass';
    	$identifier1 = array(1);
    	$entity1 = new Entity($class, $identifier1);
    	$identifier2 = array(2);
    	$entity1->updateIdentifier($identifier2);
    	$this->setExpectedException('EntityInvalidIdentifierException', 'The entity identifier is no longer valid.');
    	$entity2 = new Entity($class, $identifier1);
    }
    
    public function testUpdateToInvalidIdentifer() {
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
