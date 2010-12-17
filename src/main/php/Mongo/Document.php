<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_Document is the base class for everything todo with a Document in Mongo
 *				(with the exception of an Array of Documents which are held in a DocumentSet)
 * @copyright  	2010, Campaign and Digital Intelligence Ltd
 * @license    	New BSD License
 * @author     	Tim Langley
 */
class Mongo_Document extends Mongo_Document_Abstract 								{
	const		DEFAULT_COLLECTION_TYPE	= "Mongo_Collection";
	
	/****
	 **	These are the parameters that can (optionally be overridden in the child classes)
	 ****/
	protected 	$_strDatabase 			= null;
	protected 	$_strCollection			= null;
	protected	$_classCollectionType	= self::DEFAULT_COLLECTION_TYPE;
	/****
	 **	END
	 ****/
	private		$_Mongo_Connection		= null;
	
	protected	function mongoCollection()											{
		/**
		 *	@purpose: 	This handles the mongoCollection parameter (if it's null then this tries to create from the connection...)
		 *	@return:	class Mongo_Collection (or more specifically the class in $_classCollectionType)
		 */
		if($this->_Mongo_Collection)
			return $this->_Mongo_Collection;
		
		if(!$this->_strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		if(!$this->_strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		if(!$this->_Mongo_Connection)
			$this->_Mongo_Connection	= new Mongo_Connection();
		$mongoDb						= new Mongo_Db($this->_strDatabase, $this->_Mongo_Connection);
		$this->_Mongo_Collection		= $mongoDb->getCollection($this->_strCollection, $this->_classCollectionType);
		return $this->_Mongo_Collection;
	}
	public  	function __construct($arrDocument = null, Mongo_Collection $mongoCollection = null)	{
		/**
		 *	@purpose: 
		 *	@param:		$arrDocument	- this is the array to use as the initial parameters for the document
		 *	@param:		$mongoCollection- a Mongo_Collection which is where the document "lives"
		 */
		parent::__construct($arrDocument);
		if(!is_null($mongoCollection))												{
			$this->setDatabaseName($mongoCollection->getDatabaseName());
			$this->setCollectionName($mongoCollection->getCollectionName());
			$this->_Mongo_Collection	= $mongoCollection;
		}
	}
	public  	function __get($name)												{
		/**
		 *	@purpose: Get the Document Properties
		 */
		return $this->getByName($name);
	}
	public  	function __set($name, $value)										{
		/**
		 *	@purpose:	Set the Document Properties
		 */
		return $this->setByName($name, $value);
	}
	
	public  	function getCollectionName()										{
		/**
		 *	@purpose: Returns the name of the Collection that this document belongs to
		 */
		return $this->_strCollection;
	}
	public  	function getDatabaseName()											{
		/**
		 *	@purpose:	Returns the name of the database that this document belongs to
		 */
		return $this->_strDatabase;
	}
	
	public		function addItemToArray($strElementName, $strItemToAdd, $bUnique)	{
		/**
		 *	@purpose:	This performs an update $push or $addToSet (depending on $bUnique)
		 *				NOTE: This matches on the _Id
		 *	@param:		$strElementName	- the array to add this element to
		 *	@param:		$strElementToAdd- the item to add
		 *	@param:		$bUnique	= true 	(perform a $addToSet)
		 *							= false	(perform a $push)
		 */
		$this->setArrDocument($this->mongoCollection()->addToArray($this, $strElementName, $strItemToAdd, $bUnique));
		return true;
	}
	public		function createToken()												{
		/**
		 *	@purpose:	Ok this a little naughty having this here (but it's a useful helper function)
		 *				This creates a random md5 string
		 */
		$chars 				= "abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@£$%^&*()"; 
		srand((double)microtime()*1000000); 
		$strToken			= "";
		$lenChars			= strlen($chars);
		for($i = 0; $i < 8; $i++)
			$strToken 		.= substr($chars, rand() % $lenChars, 1);
		return md5($strToken);
	}
	public 		function isNew()													{
		/**
		 *	@purpose:	Returns whether this document has been saved before
		 *				defined by whether the _id element exists
		 *	@return:	true | false
		 */
		return !$this->nameExists(Mongo_Document_Abstract::FIELD_ID);
	}
	public  	function save()														{
		/**
		 *	@purpose:	Saves the document (actually it does an upsert)
		 *	@return:	fluent interface - returns $this
		 *
		 *	@todo:		Probably this should accept a query so that we can save a document at different positions
		 */
		$bNewDocument	= $this->isNew();
		($bNewDocument)?$this->_PreInsert():$this->_PreUpdate();
		$this->_PreSave();
		$this->setArrDocument($this->mongoCollection()->save($this)->export());
		$this->_PostSave();
		($bNewDocument)?$this->_PostInsert():$this->_PostUpdate();
		return $this;
	}
//@TODO - should switch this so that it can take a MongoCollection or Mongo_Collection class too
	public  	function setCollectionName($strCollection)							{
		if(!$strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
			
		if($this->_strCollection)
			if($strCollection != $this->_strCollection)
				throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION);

		$this->_strCollection = $strCollection;
		return true;
	}
	public  	function setConnection(Mongo_Connection $mongoConnection)			{
		/**
		 *	@purpose:	This sets the Connection object for this document (so that we can save it etc...)
		 */
		if($this->_Mongo_Connection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_ALREADY_CONNECTED);
		$this->_Mongo_Connection	= $mongoConnection;
		return true;
	}
//@TODO - should switch this so that it can take a MongoDB or a Mongo_DB class too
	public  	function setDatabaseName($strDatabase)								{
		if(!$strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
			
		if($this->_strDatabase)
			if($strDatabase != $this->_strDatabase)
				throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_WRONG_DATABASE);
		
		$this->_strDatabase = $strDatabase; 
		return true;
	}
	
	//Overload these functions in children to gain functionaliaty
	protected 	function _PreInsert()												{}
	protected 	function _PostInsert()												{}
	protected	function _PreSave()													{}
	protected	function _PostSave()												{}
	protected	function _PreUpdate()												{}
	protected	function _PostUpdate()												{}
}