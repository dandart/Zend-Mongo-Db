<?php
/**
 * @category   
 * @package    
 * @copyright  2010-12-21, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
**/
class Mongo_CursorTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	const	TEST_COLLECTION	= "CursorTest";
	
	private $_connMongo		= null;
	
	public function setUp()												{
		//Before we do anything we should drop any pre-existing test databases
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$this->_connMongo	= new Mongo_Connection($config->mongo);
		$this->_connMongo->executeFile(MOCK_DB_PATH."/Mongo/CursorTest.js");
	}
	//testConstruct
	public function testSUCCEED_construct_null()						{
		/**
		 *	We can't create cursors directly - instead they are a "by-product" of a collection->find()
		**/
		$colCollection		= new Mongo_Collection(self::TEST_DATABASE, self::TEST_COLLECTION);
		$colCollection->setConnection($this->_connMongo);
		$cursor				= $colCollection->find();
		$this->assertEquals("Mongo_Cursor", get_class($cursor));
	}
	//testCount
	public function testSUCCEED_count()									{
		/**
		 *	We can't create cursors directly - instead they are a "by-product" of a collection->find()
		**/
		$colCollection		= new Mongo_Collection(self::TEST_DATABASE, self::TEST_COLLECTION);
		$cursor				= $colCollection->find();
		$this->assertEquals("Mongo_Cursor", get_class($cursor));
		$this->assertEquals(10,						 count($cursor));
	}
	public function testFAIL_foreach()
	{
        $mockCursor = Mockery::mock('MongoCursor')
            ->shouldReceive('current')
            ->andThrow(new Exception('Fail'))
            ->mock();
        
        $mockCollection = Mockery::mock('Mongo_Collection')
            ->shouldReceive('getDatabaseName')
            ->andReturn(self::TEST_DATABASE)
            ->shouldReceive('getCollectionName')
            ->andReturn(self::TEST_COLLECTION)
            ->mock();
        //
        
		$cursor = new Mongo_Cursor($mockCursor, $mockCollection, array('query'), array('fields'));
        $cursor->current();
	}
	//testLimit
	public function testSUCCEED_limit()									{
		/**
		 *	We can't create cursors directly - instead they are a "by-product" of a collection->find()
		**/
		$intLimit			= 3;
		$colCollection		= new Mongo_Collection(self::TEST_DATABASE, self::TEST_COLLECTION);
		$cursor				= $colCollection->find()->limit($intLimit);
		$this->assertEquals("Mongo_Cursor", get_class($cursor));
		$this->assertEquals(10,						 count($cursor));
		$this->assertEquals($intLimit,				 $cursor->count(true));
	}
	//testSkip
	public function testSUCCEED_skip()									{
		/**
		 *	We can't create cursors directly - instead they are a "by-product" of a collection->find()
		**/
		$intSkip			= 5;
		$colCollection		= new Mongo_Collection(self::TEST_DATABASE, self::TEST_COLLECTION);
		$cursor				= $colCollection->find();
		$this->assertEquals("Mongo_Cursor", get_class($cursor));
		for($intI = 0; $intI <= $intSkip; $intI++)
			$cursor->next();
		$Item_5				= $cursor->current();
		$cursor2			= $colCollection->find()->skip($intSkip);
		$this->assertEquals("Mongo_Cursor", get_class($cursor2));
		$Item				= $cursor2->getNext();
		
		$this->assertEquals($Item_5['_id'],	$Item['_id']);
	}
	//testSort
	public function testSUCCEED_sort()									{
		/**
		 *	We can't create cursors directly - instead they are a "by-product" of a collection->find()
		**/
		$arrSort			= array("FirstName" => 1);
		$colCollection		= new Mongo_Collection(self::TEST_DATABASE, self::TEST_COLLECTION);
		$cursor				= $colCollection->find()->sort($arrSort);
		$item1				= $cursor->getNext();
		
		$arrSort_down		= array("FirstName" => -1);
		$cursor				= $colCollection->find()->sort($arrSort_down);
		for($intI = 0; $intI < 9; $intI++)
			$cursor->next();
		$item10				= $cursor->getNext();
		
		$this->assertEquals($item1['_id'],	$item10['_id']);
	}
}