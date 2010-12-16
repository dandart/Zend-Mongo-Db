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
	public function testFAIL_invalidHost()						{
		try 													{
			$mongoConn	= new Mongo_Connection("FAIL");
		} catch(MongoConnectionException $e)					{
			$this->assertEquals("connecting to mongodb://FAIL failed: couldn't get host info for FAIL", $e->getMessage());
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
		$this->markTestSkipped("Need to implement array access");
	}
	public function testSUCCEED_zendConfigFile()				{
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$mongoConn			= new Mongo_Connection($config->mongo);
		$arrDatabases		= $mongoConn->getDatabases();
		$this->assertTrue(2 <= count($arrDatabases));
	}
}