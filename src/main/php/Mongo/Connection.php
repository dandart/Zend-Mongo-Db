<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 * @todo:		Should make this more Zend friendly (ie taking a config object)
 * @todo:		Should support multiple connections (and slave only - read only connections)
 */

class Mongo_Connection 																{
	const	CONN_HOST_STRING						= "%s:%d,";
	const	CONN_AUTH								= "%s:%s@";
	
	const	DEFAULT_HOST							= Mongo::DEFAULT_HOST;
	const	DEFAULT_URL								= 'mongodb://';
	const	DEFAULT_PORT							= Mongo::DEFAULT_PORT;
	
	const	STR_CONNECTION							= 'connection';
	const 	STR_DATABASE							= "database";
	
	const	TYPE_DEFAULT_COLLECTION					= "Mongo_Collection";
	const	TYPE_MONGO_DOCUMENT						= "Mongo_Document";
	const	TYPE_MONGO_DOCUMENT_SET					= "Mongo_DocumentSet";
	
	private $_raw_mongo								= null;		//once connected this holds a Mongo object
	private $_raw_mongoDB							= null;		//once connected this holds a MongoDB object
	private $b_IsConnected							= false;
	private $p_strDatabaseName						= null;
	
	private static $_defaultConnectionString		= null;
	private static $_defaultDatabaseName			= null;
	
	private function raw_mongo()													{
		/**
		 *	@purpose:	This private function manages the _raw_mongo parameter ensuring it's connected
		 *	@return:	The _raw_mongo (Mongo) object
		 */
		if($this->b_IsConnected)
			return $this->_raw_mongo;
		$this->_raw_mongo->Connect();
		
		return $this->_raw_mongo;
	}
	private function raw_mongoDB()													{
		/**
		 *	@purpose:	This private function manages the _raw_mongoDB parameter ensuring it's connected to a DB
		 *	@return:	the _raw_mongoDB (Mongo) object
		 */
		if($this->b_IsConnected)
			return $this->_raw_mongoDB;
			
		if(is_null($this->p_strDatabaseName))										{
			$this->p_strDatabaseName				= self::getDefaultDatabase();
			if(is_null($this->p_strDatabaseName))
				throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		}

		//Otherwise the hard work - lets make a connection to the right database
		$this->_raw_mongoDB				= $this->raw_mongo()->selectDB($this->p_strDatabaseName);
		$this->b_IsConnected 			= true;
		return $this->_raw_mongoDB;
	}
	public  function __construct($connection 		= null, $options = array())		{
		/**
		 *	@purpose:	Constructs a new Mongo_Connection
		 *	@param:		$connection		= see $this->createConnectionString (but array, string, Zend_Config object)
		 *	@param:		$options		= array of Mongo options
		 *
		 *	@todo:		Improve the $options so that this can take a Zend object too 	
		 */
		$arrConnection		 	= $this->createConnectionArray($connection);
	
		$connectionString		= $arrConnection[self::STR_CONNECTION];
		$databaseName			= $arrConnection[self::STR_DATABASE];
		if(!Mongo_Connection::$_defaultConnectionString)
			Mongo_Connection::$_defaultConnectionString	= $connectionString;
		$this->setDatabase($databaseName);
	
		//NOTE: This overrides to make sure that it only connects when required
		$this->b_IsConnected	= false;
		$options['connect'] 	= false;
		$this->_raw_mongo		= new Mongo($connectionString, $options);
	}
	public  function __get($strCollectionName)										{
		/**
		 *	@purpose:	This is not implemented (because we have no way to capture the type of classCollection required)
		 */
		throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
	}
	public  function __toString()													{
		/**
		 *	@purpose:	Returns the current database name
		 *	@NOTE:		This DOES NOT mean that it's connected (or that the database exists)
		 */
		return $this->p_strDatabaseName;
	}
	public 	function connect($strDatabaseName 		= null)							{
		/**
		 *	@purpose: 	If connected this returns the Mongo instance
		 *					otherwise it tries to connect
		 *	@param:		$strDatabaseName (if this is null then tries $this->p_strDatabaseName)
		 *	@return:	true | exception
		 */
		if(!is_null($strDatabaseName))
			$this->setDatabase($strDatabaseName);
			
		if($this->b_IsConnected)
			return true;
		
		$this->raw_mongoDB();
		
		return true;
	}
	public	function decodeReference($arrReference, $strDatabaseName = null)		{
		/**
		 *	@purpose:	This decodes a DBReference
		 *	@param:		$arrReference = DBreference array ($id, $ref)
		 *	@return:	null (if not found) | $arrDocument
		 */
		if(!Mongo_Type_Reference::isRef($arrReference))
			return null;
		$mongo		= new Mongo(Mongo_Connection::$_defaultConnectionString);
		$strDatabase= is_null($strDatabaseName)?$this->p_strDatabaseName:$strDatabaseName;
		$mongoDB	= $mongo->selectDB($strDatabase);
		return MongoDBRef::get($mongoDB,$arrReference);
	}
	public  function dropDatabase($strDatabaseName 	= null)							{
		/**
		 *	@purpose: 	This drops the database 
		 *				(if it's already connected to a different DB then this is cached and returned to later)
		 *	@param:		$strDatabaseName (if null then drops currently connected one)
		 */
		$bBackupConnected	= $this->b_IsConnected;
		$strBackupDBName	= $this->p_strDatabaseName;
		$this->setDatabase(is_null($strDatabaseName)?$this->p_strDatabaseName:$strDatabaseName);
		
		if(is_null($this->p_strDatabaseName))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		
		$this->raw_mongoDB()->drop();
		
		$this->setDatabase($strBackupDBName);
		if($bBackupConnected)
			$this->connect();
		return true;
	}
	public 	function executeFile($strFileNameAndPath)								{
		/**
		 *	@purpose: 	This opens a file from the file system and runs it within the MongoDb
		 *				This is mainly used for unit testing where the file will contain details how to set-up the test
		 *	@param:		$strFileNameAndPath - full name and path to the file
		 */
		$handle					= fopen($strFileNameAndPath, "r");
		if(!$handle)
			throw new Mongo_Exception(Mongo_Exception::ERROR_FILE_NOT_FOUND);
		$strJavascriptString	= fread($handle, filesize($strFileNameAndPath));
		fclose($handle);
		$mongoCode				= new MongoCode($strJavascriptString);
		return $this->raw_mongoDB()->execute($mongoCode);
	}
	public  function getCollection($strCollectionName, $strClassCollection = null)	{
		/**
		 * @purpose:	This sets the current collection to $strCollectionName
		 * @return:		This returns a Mongo_Collection (or child of this)
		 */
		$rawCollection	= $this->getrawCollection($strCollectionName);
		$classType		= (!is_null($strClassCollection) && class_exists($strClassCollection))?
								$strClassCollection:self::TYPE_DEFAULT_COLLECTION;
		$colCollection	= new $classType($strCollectionName, $rawCollection);
		$colCollection->setConnection($this);
		$colCollection->setDatabaseName($this->getDatabase());
		return $colCollection;
	}
	public  function getCollections()												{
		/**
		 *	@purpose:	Returns an array of MongoConnection objects
		 *	@return: 	array(MongoConnection, MongoConnection etc...)
		 *	@todo: 		probably we should put this in to a "wrapped class" CollectionIterator
		 */
		return $this->raw_mongoDB()->listCollections();
	}
	public  function getDatabase()													{
		return $this->p_strDatabaseName;
	}
	public 	function getDatabases()													{
		/**
		 *	@purpose: 	Returns a list of all the databases on this Mongo server
		 *	@NOTE:		This function can be called WITHOUT the DatabaseName parameter being selected
		 */
		$arrDatabases	= $this->raw_mongo()->listDBs();
		//Time for some major sanity checking
		if(!is_array($arrDatabases) && !is_set($arrDatabases["ok"]) && 1 != $arrDatabases["ok"] && !is_set($arrDatabases["databases"]))
			throw new Mongo_Exception(Mongo_Exception::ERROR_UNKNOWN);
		return $arrDatabases["databases"];
	}
	public  function getrawCollection($strCollectionName)							{
		/**
		 * @purpose:	This sets the current collection to $strCollectionName
		 * @NOTE:		This is a "naughty helper function for other classes"
		 * @return:		This returns a MongoCollection
		 */
		if(is_null($strCollectionName))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		
		return $this->raw_mongoDB()->selectCollection($strCollectionName);
	}
	public 	function getrawDatabase()												{
		/**
		 * @purpose:	This returns the current database object
		 * @NOTE:		This is a "naughty helper function for other classes"
		 * @return:		This returns a MongoDB
		 */
		return $this->raw_mongoDB();
	}
	public  function isConnected()													{
		/**
		 *	@purpose: 	Is this connected to a Mongo server (and database)
		 *	@return:	true | false
		 */
		return $this->b_IsConnected;
	}
	public  function setDatabase($strDatabaseName)									{
		/**
		 *	@purpose:	Sets (or changes) the current database
		 *	@param:		strDatabaseName
		 */
		if($strDatabaseName								== $this->p_strDatabaseName)
			return true;
		
		if(!Mongo_Connection::$_defaultDatabaseName)
			Mongo_Connection::$_defaultDatabaseName		= $strDatabaseName;
		$this->b_IsConnected							= false;
		return $this->p_strDatabaseName					= $strDatabaseName;
	}
	
	private static function createConnectionArray($connection = null)				{
		/**
		 *	@purpose:	Creates the Mongo Connection string
		 *	@param:		$connection	- can be:	1. 	null						=> use the default parameters
		 *										2.	a string					=> we assume that this is correct and use it
		 *																			NOTE: the php engine doesn't like hots:port/dbname
		 *																			NOTE: therefore at moment you have to pass this 
		 *																			NOTE: separately if you want to use the string
		 *										3.	an array 					=> 
		 *								associative array like:		hosts 		=> array("name" => array( host => , port => ))
		 *															auth  		=> array(username =>, password => )
		 *																				(if host = null then DEFAULT is taken)
		 *																				(if port = null then DEFAULT is taken)
		 *															defaultDB 	=> string
		 *										4. 	a Zend_Config object
		 *															mongo.hosts.HOST_NAME.host	= 127.0.0.1
		 *															mongo.hosts.HOST_NAME.port	= 27017
		 *															mongo.auth.username			= "Tim"
		 *															mongo.auth.password			= "abc123";	
		 *															mongo.defaultDB				= ""								
		 *	@return:	array[0] = the connection string
		 *				array[1] = the databasename
		 */
		if(is_null($connection))													{
			//If $connection is null then try to load the default one first
			$strConnectionString		= Mongo_Connection::$_defaultConnectionString;
			if(is_null($strConnectionString))
				$strConnectionString	= self::DEFAULT_URL.
											trim(sprintf(self::CONN_HOST_STRING, self::DEFAULT_HOST, self::DEFAULT_PORT),",");
			$databaseName				= Mongo_Connection::$_defaultDatabaseName;
			return array(self::STR_CONNECTION 	=> $strConnectionString
						,self::STR_DATABASE 	=> $databaseName);
		}
		if(is_string($connection))													{
//@TODO HERE - have to parse the Connection
			$strConnectionString	= $connection;
			$databaseName 			= "";
			return array(self::STR_CONNECTION 	=> $strConnectionString
						,self::STR_DATABASE 	=> $databaseName);
		}
			
		if (is_a($connection, "Zend_Config"))
            $connection 				= $connection->toArray();
        
		$strConnectionString			= "";
		//Now build the connectionString from the array parameters
		if(isset($connection["hosts"]) && is_array($connection["hosts"]))
			foreach($connection["hosts"] AS $arrHost)								{
				$strHost				= (array_key_exists("host", $arrHost))?$arrHost["host"]:self::DEFAULT_HOST;
				$intPort				= (array_key_exists("port", $arrHost))?$arrHost["port"]:self::DEFAULT_PORT;	
				$strConnectionString	.=sprintf(self::CONN_HOST_STRING, $strHost, $intPort);
			}
		else
			$strConnectionString		= sprintf(self::CONN_HOST_STRING, self::DEFAULT_HOST, self::DEFAULT_PORT);
		
		$strConnectionString			= trim($strConnectionString, ',');
		if(isset($connection["auth"]) && is_array($connection["auth"]))
			$strConnectionString		= sprintf(self::CONN_AUTH, $connection["auth"]["username"], $connection["auth"]["password"])
											.$strConnectionString;
		$strConnectionString			= self::DEFAULT_URL.$strConnectionString;
		
		$databaseName					= isset($connection["defaultDB"])?
												$connection["defaultDB"]:Mongo_Connection::$_defaultDatabaseName;
		
		return array(self::STR_CONNECTION 	=> $strConnectionString
					,self::STR_DATABASE 	=> $databaseName); 
	}
	public 	static function setDefaultConnectionString($connection)					{
		$arrConnection								= Mongo_Connection::createConnectionArray($connection);
		Mongo_Connection::$_defaultConnectionString	= $arrConnection[self::STR_CONNECTION];
		Mongo_Connection::$_defaultDatabaseName		= $arrConnection[self::STR_DATABASE];
	}
	public 	static function getDefaultConnectionString()							{
		return Mongo_Connection::$_defaultConnectionString;
	}
	public 	static function getDefaultDatabase()									{
		return Mongo_Connection::$_defaultDatabaseName;
	}
	public 	static function setDefaultDatabase($strDatabaseName)					{
		return Mongo_Connection::$_defaultDatabaseName = $strDatabaseName;
	}
}