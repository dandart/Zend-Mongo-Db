<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class childCollection extends Mongo_Collection			{
	
}

class Mongo_DbTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	
	private $_connMongo		= null;
	
	public function setUp()								{
		//Before we do anything we should drop any pre-existing test databases
		$this->_connMongo	= new Mongo_Connection();
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$dbMongo->drop();
	}
	public function testFAIL_create_missingDBName()		{
		try 											{
			$dbMongo			= new Mongo_Db(null);
			$this->fail("Exception expected");
		} catch(Mongo_Exception $e)						{
			$this->assertEquals(Mongo_Exception::ERROR_MISSING_DATABASE, $e->getMessage());
		}
	}
	public function testFAIL_get_notImplemented()		{
		$strCollectionName	= "Accounts";
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		try 											{
			$dbMongo->$strCollectionName;
			$this->fail("Exception expected");
		} catch(Mongo_Exception $e)						{
			$this->assertEquals(Mongo_Exception::ERROR_NOT_IMPLEMENTED, $e->getMessage());
		}
	}
	public function testSUCCEED_toString()				{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$this->assertEquals(self::TEST_DATABASE, $dbMongo->__toString());
	}
	public function testSUCCEED_drop()					{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$return 			= $dbMongo->drop();
		$this->assertEquals(1,	$return["ok"]);
	}
	//ExecuteFile
	public function testFAIL_executeFile_NotFound()		{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		try 											{
			$dbMongo->executeFile("FAIL");
			$this->fail("Exception expected");
		} catch (Exception $e) {
			$this->assertEquals("fopen(FAIL): failed to open stream: No such file or directory", $e->getMessage());
		}
	}
	public function testSUCCEED_executeFile()			{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$return 			= $dbMongo->executeFile(MOCK_DB_PATH."/Mongo/DbTest.js");
		$this->assertEquals(1, $return["ok"]);
		//Here we check that it actually worked
		$dbColn				= $dbMongo->getCollection("Accounts");
		$dbAccount			= $dbColn->findOne();
		$this->assertEquals("lcfcomputers", $dbAccount->AccountURL);
	}
	//Get Collection
	public function testFAIL_getCollection_null()		{
		$strCollectionName	= null;
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		try 											{
			$dbMongo->getCollection($strCollectionName);
			$this->fail("Exception expected");
		} catch(Mongo_Exception $e)						{
			$this->assertEquals(Mongo_Exception::ERROR_COLLECTION_NULL, $e->getMessage());
		}
	}
	public function testSUCCEED_getCollection()			{
		$strCollectionName	= "Accounts";
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$colnAccounts		= $dbMongo->getCollection($strCollectionName);
		
		$this->assertEquals($strCollectionName, $colnAccounts->getCollectionName());
	}
	public function testSUCCEED_getCollection_child()	{
		$strCollectionName	= "childCollection";
		$strCollectionClass	= "childCollection";
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$colnChild			= $dbMongo->getCollection($strCollectionName, $strCollectionClass);
		
		$this->assertEquals($strCollectionName, 	$colnChild->getCollectionName());
		$this->assertEquals($strCollectionClass, 	$colnChild->getCollectionClass());
		$this->assertEquals($strCollectionClass, 	get_class($colnChild));
	}
	//List Collections
	public function testSUCCEED_listCollections_empty()	{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$arrCollections		= $dbMongo->getCollections();
		$intNoCollections	= count($arrCollections);
		$this->assertEquals(0, $intNoCollections);
	}
	public function testSUCCEED_listCollections()		{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$arrCollections		= $dbMongo->getCollections();
		$intNoCollections	= count($arrCollections);
		$this->assertEquals(0, $intNoCollections);
		
		//Now create a couple of collections
		$colCollection1		= $dbMongo->getCollection("collection1");		
		$arrCollections		= $dbMongo->getCollections();
		$intNoCollections	= count($arrCollections);
		//NOTE: This is still ZERO because collections are Lazy saved
		$this->assertEquals(0, $intNoCollections);
	}
	//SelectDB
	public function testFAIL_selectDB_null()			{
		$dbMongo			= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		try 											{
			$dbMongo->selectDB(null);
			$this->fail("Exception expected");
		} catch(Mongo_Exception $e)						{
			$this->assertEquals(Mongo_Exception::ERROR_MISSING_DATABASE, $e->getMessage());
		}
	}
	public function testSUCCEED__selectDB_same()		{
		$strDB				= self::TEST_DATABASE;
		$dbMongo			= new Mongo_Db($strDB, $this->_connMongo);
		$this->assertTrue($dbMongo->selectDB($strDB));
	}
	public function testSUCCEED__selectDB_different()	{
		$strDB				= self::TEST_DATABASE;
		$strDB2				= "AnotherDB";
		$dbMongo			= new Mongo_Db($strDB, $this->_connMongo);
		$this->assertTrue($dbMongo->selectDB($strDB2));
	}
}