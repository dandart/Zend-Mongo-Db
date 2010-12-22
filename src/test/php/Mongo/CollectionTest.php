<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class testCollection extends Mongo_Collection					{
	const		COLLECTION_TEST			= 'Test';	
	protected 	$_strDatabase 			= Mongo_CollectionTest::TEST_DATABASE;
	protected 	$_strCollection			= testCollection::COLLECTION_TEST;
	protected	$_strClassDocumentType	= "testDocument";
}
class testDocument extends Mongo_Document						{
	protected 	$_strDatabase 			= Mongo_CollectionTest::TEST_DATABASE;
	protected 	$_strCollection			= testCollection::COLLECTION_TEST;
	protected	$_classCollectionType	= "testCollection";
}

class testCollection_NoDB extends Mongo_Collection					{
	const		COLLECTION_TEST			= 'Test_NoDb';	
	protected 	$_strCollection			= testCollection::COLLECTION_TEST;
	protected	$_strClassDocumentType	= "testDocument_NoDB";
}
class testDocument_NoDB extends Mongo_Document						{
	protected 	$_strCollection			= testCollection_NoDB::COLLECTION_TEST;
	protected	$_classCollectionType	= "testCollection_NoDB";
}

class Mongo_CollectionTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	
	private $_connMongo		= null;
	
	public function setUp()										{
		//Before we do anything we should drop any pre-existing test databases
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$this->_connMongo	= new Mongo_Connection($config->mongo);
		$this->_connMongo->setDatabase(self::TEST_DATABASE);
		
		$arrCollections		= $this->_connMongo->getCollections();
		foreach($arrCollections AS $mongoCollection)
			$mongoCollection->drop();
	}
	//__construct
	public function testFAIL_construct_wrongCollection()		{
		try 													{
			$colTest		= new testCollection("FAILURE_ITS_A_STRING");
			$this->fail("Exception expected");
		} catch (Mongo_Exception $e)							{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION,'Test','FAILURE_ITS_A_STRING')
											, $e->getMessage());
		}
	}
	public function testSUCCEED_construct_noConn()				{
		$coln				= new Mongo_Collection("testCollection");
		$this->assertFalse($coln->isConnected());
	}
	public function testFAIL_construct_Conn_wrongCollection()	{
		try 													{
			$coln				= new Mongo_Collection("testCollection", $this->_connMongo->getrawCollection("wrongCollection"));
			$this->fail("Exception expected");
		} catch (Mongo_Exception $e)							{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION,'testCollection','wrongCollection')
											, $e->getMessage());
		}
	}
	public function testSUCCEED_construct_Conn()				{
		$coln				= new Mongo_Collection("testCollection", $this->_connMongo->getrawCollection("testCollection"));
		$this->assertTrue($coln->isConnected());
	}
	//__toString()
	public function testSUCCEED_toString()						{
		$coln				= new Mongo_Collection("testCollection");
		$this->assertEquals("Mongo_Collection", 	get_class($coln));
		$this->assertEquals("testCollection", 		$coln->__toString());
		$this->assertEquals("testCollection", 		(string)$coln);
	}
	//setConnection & isConnected
	public function testSUCCEED_isConnected()					{
		$coln				= new Mongo_Collection("testCollection");
		$this->assertFalse($coln->isConnected());
		$coln->setConnection($this->_connMongo);
		$this->assertFalse($coln->isConnected());
		$coln->connect();
		$this->assertTrue($coln->isConnected());
	}
	
	//createDocument
	public function testSUCCEED_create_MongoDocument()			{
		$colTest			= new Mongo_Collection("a_collection");
		$this->assertEquals("Mongo_Collection", 				get_class($colTest));
		$mongoDocument		= $colTest->createDocument();
		$this->assertEquals("Mongo_Document",					get_class($mongoDocument));
	}
	public function testSUCCEED_create_ChildDocument()			{
		$colTest			= new testCollection();
		$this->assertEquals("testCollection", 					get_class($colTest));
		$mongoDocument		= $colTest->createDocument();
		$this->assertEquals("testDocument",						get_class($mongoDocument));
	}
	public function testSUCCEED_create_ChildDocument_set()		{
		$colTest			= new testCollection();
		$this->assertEquals("testCollection", 					get_class($colTest));
		$colTest->setDefaultDocumentType("testDocument");
		$mongoDocument		= $colTest->createDocument();
		$this->assertEquals("testDocument",						get_class($mongoDocument));
	}
	public function testFAIL_create_ChildDocument_set()			{
		$strNewColn			= "testDocument_NoDB";
		$colTest			= new testCollection();
		$this->assertEquals("testCollection", 					get_class($colTest));
		$colTest->setDefaultDocumentType($strNewColn);
		try 													{
			$mongoDocument		= $colTest->createDocument();
			$this->fail("Exception expected");
		} catch (Exception $e) {
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION,'Test',testCollection_NoDB::COLLECTION_TEST)
									, $e->getMessage());
		}	
	}
	//decodeReference


	//drop
	public function testSUCCEED_drop()							{
		$intNoCollections	= $this->_connMongo->getCollections();
		$this->assertEquals(0, count($intNoCollections));
		
		$strTestCollection	= "testCollection";
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$intNoCollections	= $this->_connMongo->getCollections();
		$this->assertEquals(0, count($intNoCollections));
		
		$mongoDocument		= new Mongo_Document(null, $collTest);
		$mongoDocument->key	= "value";
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
		//Note: Creating a collection is a Lazy exercise (ie line 43 showed there were NO collections)
		$intNoCollections	= $this->_connMongo->getCollections();
		$this->assertEquals(1, count($intNoCollections));
		
		$return 			= $collTest->drop();
		
		$intNoCollections	= $this->_connMongo->getCollections();
		$this->assertEquals(0, count($intNoCollections));
	}
	//find
	public function testSUCCEED_findAll_One()					{
		$strTestCollection	= "testCollection";
		$arrDocument		= array("key" => "value");
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
		
		$cursor				= $collTest->find();
		$this->assertEquals(1,					count($cursor));
	}
	public function testSUCCEED_findAll_Two()					{
		$strTestCollection	= "testCollection";
		$arrDocument		= array("key"  => "value");
		$arrDocument2		= array("key2" => "value2");
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
		
		$mongoDocument		= new Mongo_Document($arrDocument2, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value2", 	$arrReturn["key2"]);
		
		$cursor				= $collTest->find();
		$this->assertEquals(2,			count($cursor));
	}
	public function testSUCCEED_findAll_filtered()				{
		$strTestCollection	= "testCollection";
		$arrDocument		= array("key"  => "value");
		$arrDocument2		= array("key2" => "value2");
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
		
		$mongoDocument		= new Mongo_Document($arrDocument2, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value2", 	$arrReturn["key2"]);
		
		$cursor				= $collTest->find();
		$this->assertEquals(2,			count($cursor));
		
		$cursor				= $collTest->find(array("key" => "value"));
		$this->assertEquals(1,			count($cursor));
	}
	//findOne
	public function testSUCCEED_findOne()						{
		$strTestCollection	= "testCollection";
		$arrDocument		= array (	"string" 	=> "value"
									,	"integer"	=> 1
									,	"array"		=> array("array_string" => "array_value")
									);
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["string"]);
		
		$docMongo			= $collTest->findOne();
		$this->assertEquals("value", 			$docMongo->string);
		$this->assertEquals($arrReturn["_id"], 	$docMongo->_id);
	}
	//getCollectionClass
	//getCollectionName
	//getDatabaseName
	
	//insert
	public function testSUCCEED_insert()						{
		$strTestCollection	= "testCollection";
		$arrDocument		= array("key" => "value");
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
	}
	public function testFAIL_insert_twice()						{
		$strTestCollection	= "testCollection";
		$arrDocument		= array("key" => "value");
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		
		$mongoDocument		= new Mongo_Document($arrDocument, $collTest);
		$arrReturn			= $collTest->insert($mongoDocument);
		$this->assertEquals("value", 	$arrReturn["key"]);
		
		try 													{
			$collTest->insert($mongoDocument);
			$this->fail("Exception expected");
		} catch (MongoCursorException $e) {
			$this->assertEquals("E11000 duplicate key error index: testMongo.testCollection.\$_id_  dup key: { : ObjectId('".$arrReturn["_id"]."') }", $e->getMessage());
		}
	}
	//save
	
	//addtoarray
	
	
	//count
	public function testSUCCEED_count()							{
		$strTestCollection	= "testCollection";
		$collTest			= $this->_connMongo->getCollection($strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		$this->assertEquals(1, count($collTest));
	}

}