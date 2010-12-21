<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

defined('MONGO_TEST_PATH') or define('MONGO_TEST_PATH', "");

class Mongo_ConnectionTest extends PHPUnit_Framework_TestCase	{
	const	TEST_DATABASE	= "testMongo";
	
	//test CONSTRUCT
		//@todo - have to test with different "options" parameter
		//NOTE: These tests are all a little "spurious" because they are confusing __connect with other stuff
	public function testFAIL_invalidHost()						{
		try 													{
			$mongoConn	= new Mongo_Connection("FAIL");
			$mongoConn->connect("FAIL");
			$this->fail("Exception expected");
		} catch(MongoConnectionException $e)					{
			$this->assertEquals("connecting to FAIL failed: couldn't get host info for FAIL", $e->getMessage());
		}
	}	
	public function testSUCCEED_string()						{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$strConnString		= $config->mongo->hosts->host1->host.":".$config->mongo->hosts->host1->port;
		$mongoConn			= new Mongo_Connection($strConnString);
		$arrDatabases		= $mongoConn->getDatabases();
		$this->assertTrue(2 <= count($arrDatabases));
	}
	public function testSUCCEED_array()							{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$strHost1			= $config->mongo->hosts->host1->host;
		$intPort1			= $config->mongo->hosts->host1->port;
		$arrArray			= array("hosts" => array("host1" => array("host" => $strHost1, "port" => $intPort1)));
		
		$mongoConn			= new Mongo_Connection($arrArray);
		$arrDatabases		= $mongoConn->getDatabases();
		$this->assertTrue(2 <= count($arrDatabases));
	}
	public function testSUCCEED_zendConfigFile()				{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$arrDatabases		= $mongoConn->getDatabases();
		$this->assertTrue(2 <= count($arrDatabases));
	}
	//test __get
	public function testFAIL_get_notImplemented()				{
		$strCollectionName	= "Accounts";
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		try 													{
			$mongoConn->$strCollectionName;
			$this->fail("Exception expected");
		} catch(Mongo_Exception $e)								{
			$this->assertEquals(Mongo_Exception::ERROR_NOT_IMPLEMENTED, $e->getMessage());
		}
	}
	//test __toString
	public function testSUCCEED_toString()						{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		//This time the default database has been loaded from the Mongo.ini
		$this->assertEquals(self::TEST_DATABASE, $mongoConn->__toString());
		
		$mongoConn->setDatabase("newDatabase");
		$this->assertEquals("newDatabase", $mongoConn->__toString());
	}
	//test connect 
	public function testSUCCEED_connect()						{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$bReturn			= $mongoConn->connect(self::TEST_DATABASE);
		$this->assertTrue($bReturn);
	}
	//test dropDatabase
	public function testSUCCEED_drop()							{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$return 			= $mongoConn->dropDatabase(self::TEST_DATABASE);
		$this->assertTrue($return);
	}
	//test ExecuteFile
	public function testFAIL_executeFile_NotFound()				{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$mongoConn->setDatabase(self::TEST_DATABASE);
		try 													{
			$mongoConn->executeFile("FAIL");
			$this->fail("Exception expected");
		} catch (Exception $e) {
			$this->assertEquals("fopen(FAIL): failed to open stream: No such file or directory", $e->getMessage());
		}
	}
	public function testSUCCEED_executeFile()					{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$mongoConn->setDatabase(self::TEST_DATABASE);
		$return 			= $mongoConn->executeFile(MOCK_DB_PATH."/Mongo/DbTest.js");
		$this->assertEquals(1, $return["ok"]);
			
		//Here we check that it actually worked
		$dbColn				= $mongoConn->getCollection("Accounts");
		$dbAccount			= $dbColn->findOne();
		$this->assertEquals("lcfcomputers", $dbAccount->AccountURL);
	}
	//test getCollection
	//test getrawCollection
	
	//test getCollections
	public function testSUCCEED_getCollections_empty()			{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$mongoConn->setDatabase(self::TEST_DATABASE);
		$arrCollections		= $mongoConn->getCollections();
		$intNoCollections	= count($arrCollections);
		//This is all very brittle - we have ONE collection because we created it during the SUCCEED_executeFile
		$this->assertEquals(1, $intNoCollections);
	}
	public function testSUCCEED_getCollections()				{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$mongoConn->setDatabase(self::TEST_DATABASE);
		$arrCollections		= $mongoConn->getCollections();
		$intNoCollections	= count($arrCollections);
		$this->assertEquals(1, $intNoCollections);
		
		//Now create a couple of collections
		$colCollection1		= $mongoConn->getCollection("collection1");		
		$arrCollections		= $mongoConn->getCollections();
		$intNoCollections	= count($arrCollections);
		//NOTE: This is still ZERO because collections are Lazy saved
		$this->assertEquals(1, $intNoCollections);
	}
	//getDatabase
	//getDatabases
	//isConnected
	//setDatabase

}