<?php
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
**/

class testCollection extends Mongo_Collection					{
	const		COLLECTION_TEST			= 'Test';	
	protected 	$_strDatabase 			= Mongo_CollectionTest::TEST_DATABASE;
	protected 	$_strCollection			= testCollection::COLLECTION_TEST;
	protected	$_strClassDocumentType	= "testDocument";
}

class Mongo_CollectionTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	
	private $_connMongo		= null;
	
	public function setUp()										{
		//Before we do anything we should drop any pre-existing test databases
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$this->_connMongo	= new Mongo_Connection($config->mongo);
		$arrCollections		= $this->_connMongo->getCollections(self::TEST_DATABASE);
		foreach($arrCollections AS $mongoCollection)
			$mongoCollection->drop();
	}
	//__construct
	public function testFAIL_construct_wrongCollection()		{
		try 													{
			$colTest		= new testCollection(self::TEST_DATABASE, "FAILURE_ITS_A_STRING");
			$this->fail("Exception expected");
		} catch (Mongo_Exception $e)							{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION,'Test','FAILURE_ITS_A_STRING')
											, $e->getMessage());
		}
	}
	public function testSUCCEED_construct_noConn()				{
		$coln				= new Mongo_Collection(self::TEST_DATABASE, "testCollection");
		$this->assertFalse($coln->isConnected(self::TEST_DATABASE));
	}
	//setConnection & isConnected
	public function testSUCCEED_isConnected()					{
		$coln				= new Mongo_Collection(self::TEST_DATABASE,"testCollection");
		$this->assertFalse($coln->isConnected());
		$coln->setConnection($this->_connMongo);
		$this->assertFalse($coln->isConnected());
		$coln->connect();
		$this->assertTrue($coln->isConnected());
	}
	
	//decodeReference

	
	//getCollectionClass
	//getCollectionName
	//getDatabaseName

	//save
	
	//addtoarray
	
	
	//count
	public function testSUCCEED_count()							{
		$strTestCollection	= "testCollection";
		$collTest			= $this->_connMongo->getCollection(self::TEST_DATABASE, $strTestCollection);
		$this->assertEquals($strTestCollection, $collTest->getCollectionName());
		$this->assertEquals(1, count($collTest));
	}

}