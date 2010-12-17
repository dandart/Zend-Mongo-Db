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
	const	CONN_HOST_STRING= "%s:%d,";
	const	CONN_AUTH		= "%s:%s@";
	
	const	DEFAULT_HOST	= Mongo::DEFAULT_HOST;
	const	DEFAULT_URL		= 'mongodb://';
	const	DEFAULT_PORT	= Mongo::DEFAULT_PORT;
	
	private $_mongo				= null;		//once connected this holds a Mongo object
	private $b_IsConnected		= false;
	
	private static $_defaultConnectionString= null;
	
	public function __construct($connection = null, $options = array())				{
		/**
		 *	@purpose:	Constructs a new Mongo_Connection
		 *	@param:		$connection		= see $this->createConnectionString
		 *	@param:		$options		= array of Mongo options
		 *
		 *	@todo:		Improve the $options so that this can take a Zend object too 	
		 */
		$connectionString	 	= $this->createConnectionString($connection);
		if(!Mongo_Connection::$_defaultConnectionString)
			Mongo_Connection::$_defaultConnectionString	= $connectionString;

		$options['connect'] 	= false;
		$this->_mongo			= new Mongo($connectionString, $options);
	}
	private static function createConnectionString($connection = null)				{
		/**
		 *	@purpose:	Creates the Mongo Connection string
		 *	@param:		$connection	- can be:	1. 	null					=> use the default parameters
		 *										2.	a string				=> we assume that this is correct and use it
		 *										3.	an array 				=> 
		 *								associative array like:		hosts 	=> array("name" => array( host => , port => ))
		 *															auth  	=> array(username =>, password => )
		 *																			(if host = null then DEFAULT is taken)
		 *																			(if port = null then DEFAULT is taken)
		 *										4. 	a Zend_Config object
		 *															mongo.hosts.HOST_NAME.host	= 127.0.0.1
		 *															mongo.hosts.HOST_NAME.port	= 27017
		 *															mongo.auth.username			= "Tim"
		 *															mongo.auth.password			= "abc123";									
		 */
		if(is_null($connection))													{
			//If $connection is null then try to load the default one first
			$connection		= Mongo_Connection::$_defaultConnectionString;
			if(is_null($connection))
				$connection	= self::DEFAULT_URL.trim(sprintf(self::CONN_HOST_STRING, self::DEFAULT_HOST, self::DEFAULT_PORT),",");
			return $connection;
		}
		if(is_string($connection))
			return $connection;
			
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
		return $strConnectionString;
	}
	public 	function connect()														{
		/**
		 *	@purpose: 	If connected this returns the Mongo instance
		 *					otherwise it tries to connect
		 *	@return:	An instance of the MongoDB object
		 */
		if($this->b_IsConnected)
			return $this->_mongo;
		
		//Otherwise the hard work - lets make a connection
		$this->_mongo->Connect();
		$this->b_IsConnected 	= true;
		return $this->_mongo;
	}
	public 	function getDatabases()													{
		return $this->connect()->listDBs();
	}
	public 	static function setDefaultConnectionString($connection)					{
		Mongo_Connection::$_defaultConnectionString	= Mongo_Connection::createConnectionString($connection);
	}
	public 	static function getDefaultConnectionString()							{
		return Mongo_Connection::$_defaultConnectionString;
	}
}