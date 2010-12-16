<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class DocumentTest_ChildDocument extends Mongo_Document			{
	protected 	$_strDatabase 			= Mongo_DocumentTest::TEST_DATABASE;
	
}

class Mongo_DocumentTest extends PHPUnit_Framework_TestCase		{	
	const	TEST_DATABASE	= "testMongo";
	const	TEST_COLLECTION	= "testDocumentTest";
	
	private $_colMongo		= null;
	private $_connMongo		= null;
	private $_dbMongo		= null;
	
	public function setUp()										{
		//Before we do anything we should drop any pre-existing test databases
		$this->_connMongo	= new Mongo_Connection();
		$this->_dbMongo		= new Mongo_Db(self::TEST_DATABASE, $this->_connMongo);
		$arrCollections		= $this->_dbMongo->getCollections();
		foreach($arrCollections AS $mongoCollection)
			$mongoCollection->drop();
		$this->_colMongo	= $this->_dbMongo->getCollection(self::TEST_COLLECTION);
	}
	public function testSUCCEED_createEmptyDocument()			{
		$mongoDocument		= new Mongo_Document();
		$arrDocument		= $mongoDocument->export();
		$this->assertEquals("Mongo_Document", $arrDocument[Mongo_Document::FIELD_TYPE]);
	}
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
}