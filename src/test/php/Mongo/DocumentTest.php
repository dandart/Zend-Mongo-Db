<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class DocumentTest_ChildDocument extends Mongo_Document				{
	protected 	$_strDatabase 			= Mongo_DocumentTest::TEST_DATABASE;
	
}
class DocumentTest_ChildDocumentRequirements extends Mongo_Document	{
	protected 	$_strDatabase 			= Mongo_DocumentTest::TEST_DATABASE;
	protected 	$_strCollection			= Mongo_DocumentTest::TEST_COLLECTION;
}

class Mongo_DocumentTest extends PHPUnit_Framework_TestCase		{	
	const	TEST_DATABASE	= "testMongo";
	const	TEST_COLLECTION	= "testDocumentTest";
	
	private $_connMongo		= null;
	
	public function setUp()										{
		//Before we do anything we should drop any pre-existing test databases
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$this->_connMongo	= new Mongo_Connection($config->mongo);
		$this->_connMongo->executeFile(MOCK_DB_PATH."/Mongo/DocumentTest.js");
	}
	//construct
	public function testSUCCEED_construct_null()				{
		$mongoDocument		= new Mongo_Document();
		$arrDocument		= $mongoDocument->export();
		$this->assertEquals("Mongo_Document", $arrDocument[Mongo_Document::FIELD_TYPE]);
	}
	public function testSUCCEED_construct_data()				{
		$arrData			= array("Tim" => "Langley", "More" => "Data");
		$mongoDocument		= new Mongo_Document($arrData);
		$arrDocument		= $mongoDocument->export();
		$this->assertEquals("Mongo_Document", 		$arrDocument[Mongo_Document::FIELD_TYPE]);
		$this->assertEquals("Data",					$arrDocument["More"]);
		$this->assertEquals("Langley",				$arrDocument["Tim"]);
	}
	//setConnection
	public function testSUCCEED_construct_setConnection()		{
		$mongoDocument		= new Mongo_Document();
		$mongoDocument->setConnection($this->_connMongo);
		//Until we've set the Database name it should be null
		$this->assertEquals(null,					$mongoDocument->getDatabaseName());
		
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$this->assertEquals(self::TEST_DATABASE,	$mongoDocument->getDatabaseName());
	}
	//addItemToArray
	
	//createToken
	public function testSUCCEED_createToken()					{
		$mongoDocument		= new Mongo_Document();
		$strToken			= $mongoDocument->createToken();
		$this->assertRegExp('/[a-zA-Z0-9]{32}/',	$strToken);
	}
	//save
	public function testSUCCEED_save()							{
		$mongoDocument		= new Mongo_Document();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->save();
		
		$strFieldType		= Mongo_Document::FIELD_TYPE;
		$this->assertEquals("Mongo_Document", $mongoDocument->$strFieldType);
		$this->assertEquals("Mongo_Document", $mongoDocument[Mongo_Document::FIELD_TYPE]);
	}
	public function testSUCCEED_save_childDocument()			{
		$mongoDocument		= new DocumentTest_ChildDocument();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->save();	
		$strFieldType		= Mongo_Document::FIELD_TYPE;
		$this->assertEquals("DocumentTest_ChildDocument", $mongoDocument->$strFieldType);
		$this->assertEquals("DocumentTest_ChildDocument", $mongoDocument[Mongo_Document::FIELD_TYPE]);
	}
	//setCollectionName
	public function testFAIL_setCollection_Invalid()			{
		$childDocument		= new DocumentTest_ChildDocumentRequirements();
		$strNewColn			= "FAIL";
		try 													{
			$childDocument->setCollectionName($strNewColn);
			$this->fail("Exception expected");
		} catch (Exception $e) {
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION,
									$strNewColn,Mongo_DocumentTest::TEST_COLLECTION), $e->getMessage());
		}
		
	}

	//Test different types of __get
	public function testSUCEED_get_data()						{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBRef()						{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBRef_null()					{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBSet()						{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBSet_data()					{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBSet_DBRef_null()			{
		$this->markTestSkipped();
	}
	public function testSUCEED_get_DBSet_DBRef()				{
		$colAccountUser		= $this->_connMongo->getCollection(self::TEST_DATABASE, "Account_Users");
		$docAccountUser		= $colAccountUser->findOne();
		$this->assertEquals("Tim", $docAccountUser->FirstName);
		
		$docAccount			= $docAccountUser->Accounts[0];
		$this->assertEquals("lcfcomputers", $docAccount->AccountURL);
	}
}