<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class Mongo_Db 																		{
	private $_connection 	= null;
	private $_strDatabase	= null;
	private $_mongoDB		= null;	//This is the mongoDB instance for this database
									//NOTE: Do not call this directly - call $this->raw_mongoDB()-> instead
									//		this handles the lazy connections (and later any connection sharing)
	
	public  function __construct($strDatabase, Mongo_Connection $connection = null)	{
		if(!$strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		$this->_strDatabase	= $strDatabase;
		$this->_connection	= $connection;
		return $this;
	}
	public  function __get($strCollectionName)										{
		/**
		 *	@purpose:	This is not implemented (because we have no way to capture the type of classCollection required)
		 */
		throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
	}
	public  function __toString()													{
		return $this->raw_mongoDB()->__toString();
	}
	public  function drop()															{
		/**
		 *	@purpose:	Drop the currently selected database as specified in $_strDatabase
		 */
		return $this->raw_mongoDB()->drop();
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
		 *				This returns a MongoCollection
		 */
		if(!$strCollectionName)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		
		$classType	= (class_exists($strClassCollection))?$strClassCollection:Mongo_Document::DEFAULT_COLLECTION_TYPE;
		return new $classType($this->raw_mongoDB()->selectCollection($strCollectionName));
	}
	public  function getCollections()												{
		/**
		 *	@purpose:	Returns an array of MongoConnection objects
		 *	@return: 	array(MongoConnection, MongoConnection etc...)
		 *	@todo: 		probably we should put this in to a "wrapped class" CollectionIterator
		 */
		return $this->raw_mongoDB()->listCollections();
	}
	public  function getRawCollection($strCollectionName)							{
		/**
		 *	@purpose:		This sets the current collection to $strCollectionName
		 *					This returns the "raw" MongoCollection - this is a little naughty
		 */	
		if(!$strCollectionName)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		return $this->raw_mongoDB()->selectCollection($strCollectionName);
	}
	public 	function raw_mongoDB()													{
		if($this->_mongoDB)
			return $this->_mongoDB;
		
		if(!$this->_connection)
			//If the connection hasn't been specified then try the default
			$this->_connection = new Mongo_Connection();
		
		$this->_mongoDB		= $this->_connection->connect()->selectDB($this->_strDatabase);
		return $this->_mongoDB;
	}
	public  function selectDB($strDatabase)											{
		/**
		 *	@purpose: Switches the database 
		 */
		if(!$strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		if($this->_mongoDB && ($this->_mongoDB->toString() == $strDatabase))
			return true;
		
		//This doesn't actually instance Mongo (it resets the current _mongoDB so that on the next call...)
		$this->_strDatabase	= $strDatabase;
		if($this->_mongoDB)
			$this->_mongoDB	= null;
		return true;
	}
	public  function setConnection(Mongo_Connection $connection)					{
		$this->$_connection	= $connection;
		if($this->_mongoDB)
			$this->_mongoDB	= null;
	}
}