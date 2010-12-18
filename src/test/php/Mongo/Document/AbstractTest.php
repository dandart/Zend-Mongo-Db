<?
/**
 * @category   
 * @package    
 * @copyright  2010-12-17, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class AbstractTest_Document extends Mongo_Document 						{
	//This is a temp object to test an abstract class
}
class AbstractTest_Document_wReqs extends Mongo_Document 				{
	//This is a temp object to test an abstract class which has requrements
	protected	$_arrRequirements		= array (	'MachineGUID'	=> array( "Required"
																			, "Validate:StringLength" 	=> array("min" => 5))
												, 	'InvalidValid'	=> array("Validate:StringLength" 	=> array("min" => 5))
												, 	'InvalidValid2'	=> array("Validate_Fail" 			=> array("min" => 5))
												, 	'InvalidValid3'	=> array("Validate_StringLength" 	=> array("min" => 5))
												,	'Browsers'		=> array("Optional", "DocumentSet"));
}
class AbstractTest_Document_wReqsClosed extends Mongo_Document 			{
	//This is a temp object to test an abstract class which has requrements
	protected	$_arrRequirements		= array (	'MachineGUID'	=> "Required"
												,	'Browsers'		=> array("Optional", "DocumentSet")
												,	'Status'		=> array("Validate_InArray"			=> array(1,2)));
	protected 	$_bClosed				= true;
}
class AbstractTest_Document_wReqsFromDoc extends Mongo_Document 		{
	//This is a temp object to test an abstract class which has requrements
	protected	$_arrRequirements		= array (	'_Collection'	=> "Models"
												,	'_Ref'			=> "Abstract"
												,	'_Closed'		=> true);
}

class Mongo_Document_AbstractTest extends PHPUnit_Framework_TestCase	{	
	const	TEST_DATABASE	= "testMongo";
	const	TEST_COLLECTION	= "testDocumentTest";
	
	private $_colMongo		= null;
	private $_connMongo		= null;
	
	public function setUp()												{
		//Before we do anything we should drop any pre-existing test databases
		$config			 	= new Zend_Config_Ini(MONGO_TEST_PATH.'mongo.ini', APPLICATION_ENV);
		$this->_connMongo	= new Mongo_Connection($config->mongo);
		$this->_connMongo->setDatabase(self::TEST_DATABASE);
		$arrCollections		= $this->_connMongo->getCollections();
		foreach($arrCollections AS $mongoCollection)
			$mongoCollection->drop();
		$this->_colMongo	= $this->_connMongo->getCollection(self::TEST_COLLECTION);
	}
	//testConstruct
	public function testSUCCEED_construct_null()						{
		$docAbstract		= new AbstractTest_Document();
		$this->assertEquals("AbstractTest_Document", $docAbstract[AbstractTest_Document::FIELD_TYPE]);
	}
//@TODO - put a dbRef in here too
	public function testSUCCEED_construct_data()						{
		$arrData			= array (	"Tim" 			=> "Langley"
									, 	"Document" 		=> array(	"FirstName" => "Tim"
																,	"LastName"	=> "Langley")
									, 	"DocumentType" 	=> array(	"FirstName" => "Tim"
																,	"LastName"	=> "Langley"
																,	"_Type"		=> "AbstractTest_Document")
									,	"DocumentSet"	=> array(	array("CompanyName"	=> "Company1")
																,	array("CompanyName"	=> "Company2"))
									,	"DocumentSetType"=> array(	array("CompanyName"	=> "Company1")
																,	array("CompanyName"	=> "Company2")
																,	"_Type"		=> "Mongo_DocumentSet")
									);
		$docAbstract		= new AbstractTest_Document($arrData);
		$this->assertEquals("AbstractTest_Document", $docAbstract[AbstractTest_Document::FIELD_TYPE]);
		
		//Now lets check the decoding stuff
		$this->assertEquals("Langley",	$docAbstract->Tim);
		$docDocument		= $docAbstract->Document;
			//NOTE this is just a Mongo_Document because there are no requirements / no type field
		$this->assertEquals("Mongo_Document", 		$docDocument[AbstractTest_Document::FIELD_TYPE]);
		$this->assertEquals("Tim",					$docDocument->FirstName);
		$this->assertEquals("Langley",				$docDocument->LastName);
		
		$docDocumentType	= $docAbstract->DocumentType;
			//NOTE this time it returns an AbstractTest_Document ;-)
		$this->assertEquals("AbstractTest_Document",$docDocumentType[AbstractTest_Document::FIELD_TYPE]);
		$this->assertEquals("Tim",					$docDocumentType->FirstName);
		$this->assertEquals("Langley",				$docDocumentType->LastName);
		
		$docDocumentSet		= $docAbstract->DocumentSet;
		$this->assertEquals("Mongo_DocumentSet",	$docDocumentSet[AbstractTest_Document::FIELD_TYPE]);
		$this->assertEquals(2,						count($docDocumentSet));
		$this->assertEquals("Company1",				$docDocumentSet[0]["CompanyName"]);
		$this->assertEquals("Mongo_Document",		$docDocumentSet[0][AbstractTest_Document::FIELD_TYPE]);
		
		$docDocumentSetType		= $docAbstract->DocumentSetType;
			//NOTE DocumentSet's arn't overridable
		$this->assertEquals("Mongo_DocumentSet",	$docDocumentSetType[AbstractTest_Document::FIELD_TYPE]);
		$this->assertEquals(2,						count($docDocumentSetType));
	}
	public function testFAIL_construct_reqs_null()						{
		$docAbstract		= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		
		try 															{
			$docAbstract->export();
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_PROPERTY_REQUIRED, "MachineGUID"), $e->getMessage());
		}
	}
	public function testFAIL_construct_reqs_invalid()					{
		$docAbstract				= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		try 															{
			$docAbstract->InvalidValid 	= 3;
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_REQUIREMENT_NOT_EXIST, "Validate:StringLength"),
			 						$e->getMessage());
		}
	}
	public function testFAIL_construct_reqs_invalid2()					{
		$docAbstract				= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		try 															{
			$docAbstract->InvalidValid2 	= 3;
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals("include_once(Zend/Validate/Fail.php): failed to open stream: No such file or directory",
			 						$e->getMessage());
		}
	}
	public function testFAIL_construct_reqs_invalid3()					{
		$docAbstract				= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		try 															{
			$docAbstract->InvalidValid3 = 3;
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals("Invalid type given. String expected", $e->getMessage());
		}
	}
	public function testSUCCEED_construct_reqs_invalid3()				{
		$strString					= "This Is Over 5 letters";
		$docAbstract				= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		$docAbstract->InvalidValid3 = $strString;
		$this->assertEquals($strString, $docAbstract->InvalidValid3);
	}
	public function testSUCCEED_construct_reqs_browsers()				{
		$docAbstract				= new AbstractTest_Document_wReqs();
		$this->assertEquals("AbstractTest_Document_wReqs", $docAbstract[AbstractTest_Document_wReqs::FIELD_TYPE]);
		$docSet						= new Mongo_DocumentSet(null);
//@TODO - there are a series of bugs here to resolve
		$docAbstract->Browsers[0]	= $docSet;
		$tim	= $docAbstract->Browsers;
	}
	//Now check about a CLOSED array
	public function testFAIL_construct_reqsClosed_newProperty()			{
		$docAbstract				= new AbstractTest_Document_wReqsClosed();
		$this->assertEquals("AbstractTest_Document_wReqsClosed", $docAbstract[AbstractTest_Document_wReqsClosed::FIELD_TYPE]);
		try 															{
			$docAbstract->NewProperty = 3;
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals(sprintf(Mongo_Exception::ERROR_CLOSED_DOCUMENT, "NewProperty"), $e->getMessage());
		}
	}
	public function testFAIL_construct_reqsClosed_testArray()			{
		$docAbstract				= new AbstractTest_Document_wReqsClosed();
		$this->assertEquals("AbstractTest_Document_wReqsClosed", $docAbstract[AbstractTest_Document_wReqsClosed::FIELD_TYPE]);
		try 															{
			$docAbstract->Status	= 3;
			$this->fail("Exception expected");
		} catch (Exception $e) 											{
			$this->assertEquals("'3' was not found in the haystack", $e->getMessage());
		}
	}
	//Now check for "loading Requs" from another object
	public function testSUCCEED_construct_requFromOther()				{
		$this->markTestIncomplete("We have to write the code to load this from another Collection Object");
	}
}