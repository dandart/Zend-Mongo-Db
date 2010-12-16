<?
/**
 * @category   
 * @package    
 * @copyright  2010-12-10, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class Mongo_DocumentSetTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	const	TEST_COLLECTION	= "testDocumentSetTest";
	
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
	public function testSUCCEED_createEmptyDocumentSet()		{
		/**
		 *	@NOTE: DocumentSets can't really be created by themselves - they are created out of a array in a Document
		 */
		$mongoDocument			= new Mongo_Document();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->Test	= array (	array(	"Name" => "Tim")
										,	array(	"Name" => "James")
										,	array(	"Name" => "Jim"));
		$mongoDocument->save();
		$mongoDocumentSet		= $mongoDocument->Test;
		$this->assertEquals("Mongo_DocumentSet", 	$mongoDocumentSet->_Type);
		$this->assertEquals(null, 					$mongoDocumentSet->_id);
		$this->assertEquals("Mongo_DocumentSet", 	get_class($mongoDocumentSet));
		//There are THREE elements
		$this->assertEquals(3, 						count($mongoDocumentSet));
		
		$mongoDocument0			= $mongoDocumentSet[0];
		$this->assertEquals("Mongo_Document", 	$mongoDocument0->_Type);
		$this->assertEquals("Tim", 				$mongoDocument0->Name);
	}
	public function testFAIL_get_notImplemented()				{
		/**
		 *	@NOTE: DocumentSets can't really be created by themselves - they are created out of a array in a Document
		 */
		$mongoDocument			= new Mongo_Document();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->Test	= array (	array(	"Name" => "Tim")
										,	array(	"Name" => "James")
										,	array(	"Name" => "Jim"));
		$mongoDocument->save();
		$mongoDocumentSet		= $mongoDocument->Test;
		try 													{
			$mongoDocumentSet->_Fail;
		} catch (Mongo_Exception $e)							{
			$this->assertEquals(Mongo_Exception::ERROR_NOT_IMPLEMENTED, $e->getMessage());
		}
	}
	public function testSUCCEED_iterator()						{
		/**
		 *	@NOTE: DocumentSets can't really be created by themselves - they are created out of a array in a Document
		 */
		$mongoDocument			= new Mongo_Document();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->Test	= array (	array(	"Name" => "Tim")
										,	array(	"Name" => "James")
										,	array(	"Name" => "Jim"));
		$mongoDocument->save();
		$mongoDocumentSet		= $mongoDocument->Test;
		$this->assertEquals("Mongo_DocumentSet", 	$mongoDocumentSet->_Type);
		
		foreach($mongoDocumentSet AS $mongoDocument)			{
			$this->assertEquals("Mongo_Document", 	$mongoDocument->_Type);
		}
	}
	public function testSUCCEED_arrayAccess()					{
		$mongoDocument			= new Mongo_Document();
		$mongoDocument->setDatabaseName(self::TEST_DATABASE);
		$mongoDocument->setCollectionName(self::TEST_COLLECTION);
		$mongoDocument->setConnection($this->_connMongo);
		$mongoDocument->Test	= array (	array(	"Name" => "Tim")
										,	array(	"Name" => "James")
										,	array(	"Name" => "Jim"));
		$mongoDocument->save();
		$mongoDocumentSet		= $mongoDocument->Test;
		$this->assertEquals("Mongo_DocumentSet", 	$mongoDocumentSet->_Type);
		
		$this->assertEquals("Mongo_Document", 		$mongoDocumentSet[0]->_Type);
		$this->assertEquals("Mongo_Document", 		$mongoDocumentSet[1]->_Type);
		$this->assertEquals("Mongo_Document", 		$mongoDocumentSet[2]->_Type);
	}
}