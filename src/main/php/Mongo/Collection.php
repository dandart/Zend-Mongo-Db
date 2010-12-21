<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class Mongo_Collection implements Countable, Mongo_Connection_Interface				{
	
	private   	$_rawMongoCollection	= null;	//Holds the MongoCollection object 
												//NOTE: Don't access this directly use $this->raw_mongoCollection() instead
	
	private 	$_Mongo_Connection		= null;	//This holds the Connection variable
	
	
	/****
	 **	These are the parameters that can (optionally be overridden in the child classes)
	 ****/
	protected 	$_strClassDocumentType 	= Mongo_Connection::TYPE_MONGO_DOCUMENT;
	protected 	$_strDatabase 			= null;
	protected 	$_strCollection			= null;
	/****
	 **	END
	 ****/
	
	public  function __construct($strCollectionName = null, 
									MongoCollection $raw_MongoCollection = null)	{
		/**
		 *	@purpose: 	This tries to create a Mongo_Collection
		 *	@param:		$mixedVariable - this is a very very versatile beast ;-)
		 *					= null 			=> This is ONLY possible in child classes where $_strDatabase has been over ridden
		 *									=> in this case we take the default connection and try to connect to $_strDatabase
		 *					= a Collection
		 *					= a Connection
		 */

		//Some sanity checking - basically we check that the default class exists
		$this->setCollectionName($strCollectionName);
		//	This deals with situtations where the $this->_strClassDocumentType has been overridden
		$this->_strClassDocumentType	= $this->setDefaultDocumentType($this->_strClassDocumentType);
		$this->setCollection($raw_MongoCollection);
	}
	public  function __toString()													{
		/**
		 *	@purpose: 	Returns the name of the collection
		 *	@NOTE:		This does NOT mean that the collection is connected
		 */
		return $this->_strCollection;
	}
	
	public  function createDocument($arrDocument = null)							{
		/**
		 *	@purpose: 	This creates a default document for this collection
		 *	@return:	Mongo_Document (or child)
		 */
		$classDocument				= $this->getDocumentType($arrDocument);
		
		$docAbstract				= new $classDocument($arrDocument);
		if($this->_Mongo_Connection)
			$docAbstract->setConnection($this->_Mongo_Connection);
		if($this->_strDatabase)
			$docAbstract->setDatabaseName($this->_strDatabase);
		if($this->_strCollection)
			$docAbstract->setCollectionName($this->_strCollection);
		return $docAbstract;
	}
	public  function drop()															{
		/**
		 *	@purpose:	Drops this connection
		 */
		$return 	= $this->raw_mongoCollection()->drop();
		return $return;
	}
	public  function find($query = array(), $fields = array(), $sort = array())		{
		/**
		 *	@purpose:	
		 * 	@param:		$query 	Array array(field => Value)
		 *	@param:		$fields	Array array(A,B,C) - the fields to return
		 *	@param:		$sort	Array array(A => 1, B => -1 ) where A,B are the fields to sort on & 1 = ASC, -1 = DESC
		 *	@return:	returns Mongo_Document_Cursor
		 */
		if(is_null($query) || is_null($fields))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		$cursor				= $this->raw_mongoCollection()->find($query, $fields);
		$iterDocument		= new Mongo_Document_Cursor($cursor, $this->_Mongo_Connection);
		return $iterDocument->sort($sort);
	}
	public  function findOne($query = array(), $fields = array())					{
		/**
		 *	@purpose:	
		 * 	@param:		$query 	Array array(field => Value)
		 *	@param:		$fields	Array array(A,B,C) - the fields to return
		 *	@return:	returns an object of Mongo_Document (or child of this)
		 *
		 */
		if(is_null($query) || is_null($fields))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		$arrDocument	= $this->raw_mongoCollection()->findOne($query, $fields);
		if(is_null($arrDocument))
			return null;
		return $this->createDocument($arrDocument);
	}
	public 	function getCollectionClass()											{
		return get_class($this);
	}
	public  function getCollectionName()											{
		/**
		 *	@purpose:	Returns the name of the collection
		 *	@return:	string - the collection name
		 */
		return $this->raw_mongoCollection()->getName();
	}
	private function getDocumentType($arrDocument = null)							{
		/**
		 *	@purpose:	Works out what type of Document class should be created
		 *				NOTE: 	This checks if an existing document is trying to be "recreated" and if so it the _Type is valid
		 *				SECOND:	This checks if the collection has a default type
		 */
		return Mongo_Document_Abstract::getDocumentClass($this->getDefaultDocumentType(), $arrDocument);
	}
	public  function getDatabaseName()												{
		/**
		 *	@purpose:	Returns the database that this Collection is attached to
		 */
		return $this->_strDatabase;
	}
	public 	function getDefaultDocumentType()										{
		return (class_exists($this->_strClassDocumentType))?$this->_strClassDocumentType:Mongo_Connection::TYPE_MONGO_DOCUMENT;
	}
	private function setCollection(MongoCollection $raw_MongoCollection = null)		{
		/**
		 *	@purpose:	This PRIVATE function does the sanity checking on setting the collection
		 */
		if(is_null($raw_MongoCollection))
			return true;
		
		//Now check that the CollectionNames are the same
		if(!is_null($this->_strCollection) 		&& $raw_MongoCollection->getName() != $this->_strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION);
		//Then check that the DatabaseNames are the same
		if(!is_null($this->_strDatabase) 		&& $raw_MongoCollection->db->__toString() != $this->_strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE);
		
		$this->_rawMongoCollection				= $raw_MongoCollection;
	}
	private function setCollectionName($strCollectionName = null)					{
		if(is_null($strCollectionName) && is_null($this->_strCollection))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		
		if(is_null($strCollectionName))
			return true;
		
		if(!is_null($this->_strCollection) && $this->_strCollection != $strCollectionName)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION);
		
		$this->_strCollection = is_null($this->_strCollection)?$strCollectionName:$this->_strCollection;
		return true;
	}
	public  function setDatabaseName($strDatabaseName)								{
		/**
		 *	@purpose:	Sets the Database Name
		 */
		if(!is_null($this->_strDatabase))
			if($this->_strDatabase != $strDatabaseName)
				throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE);
		$this->_strDatabase	= $strDatabaseName;
	}
	public 	function decodeReference($arrReference)									{
		/**
		 *	@purpose:	decodes a DBReference
		 *	@param:		$arrReference	= array like: array($ref, $id, $database)
		 */
		$arrDocument	= $this->raw_mongoCollection()->getDBRef($arrReference);
		if(!$arrDocument)
			return null;
		return $this->createDocument($arrDocument, true);
	}
	public 	function setDefaultDocumentType($mixedClassDefaultDoc = null)			{
		/**
		 *	@purpose: 	This sets the defaultDocumentType for this collection
		 *	@param:		$classDefautDoc - this can be 
		 *					a) string = the name of the class
		 *					b) object = an instance of the class
		 *					c) null   = reset to default
		 */
		if(is_null($mixedClassDefaultDoc))
			return $this->_strClassDocumentType = Mongo_Connection::TYPE_MONGO_DOCUMENT;
		if(is_object($mixedClassDefaultDoc) && is_a($mixedClassDefaultDoc, Mongo_Connection::TYPE_MONGO_DOCUMENT))
			return $this->_strClassDocumentType = get_class($mixedClassDefaultDoc);
		if(is_string($mixedClassDefaultDoc) && class_exists($mixedClassDefaultDoc))
			return $this->_strClassDocumentType = $mixedClassDefaultDoc;
		throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
	}
	
	public  function insert(Mongo_Document $mongoDocument, 	$bSafe = true)			{
		/**
		 *	@purpose:	Inserts a query into the database
		 *	@param:		$mongoDocument - the document to be inserted
		 *	@param:		$bSafe		- true (default) => it will wait for success before returning
		 *	@return:	array of the data returned (typically this includes a MongoId field)
		 *
		 */
		$options["safe"]	= $bSafe;
		$arrDocument		= $mongoDocument->export();
		$this->raw_mongoCollection()->insert($arrDocument, $options);
		return $mongoDocument;
	}
	public  function save(Mongo_Document $mongoDocument, 	$bSafe = true)			{
		/**
		 *	@purpose:	Performs an Upsert on the Mongo_Document
		 *	@param:		$mongoDocument - the document to be saved
		 */
		$arrDocument		= $mongoDocument->export();
		
		//Now check that the Document and Collection belong to same Collection and same Database
		if( !is_null($this->getDatabaseName()) 
		&& (!is_null($mongoDocument->getDatabaseName()))
		&& ($mongoDocument->getDatabaseName() 	!= $this->getDatabaseName()))
			throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_WRONG_DATABASE);
		if( !is_null($this->getCollectionName()) 
		&& (!is_null($mongoDocument->getCollectionName()))
		&& ($mongoDocument->getCollectionName() 	!= $this->getCollectionName()))
			throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION);
		//Now check that the Required parameters are all valid
				
		$options["safe"]	= $bSafe;
		$this->raw_mongoCollection()->save($arrDocument, $options);
		return $this->createDocument($arrDocument);
	}
	public 	function addToArray(Mongo_Document $mongoDocument, $strProperty, 
								$strItemToAdd, $bUnique)							{
		/**
		 *	@purpose: 	This adds a new element to an array in the Mongo_Document
		 *				The Mongo_Document must be saved first so that we have a _id field
		 *	@param:		$mongoDocument - the document to update
		 *	@param:		
		 */
		if(!$mongoDocument->nameExists(Mongo_Document_Abstract::FIELD_ID))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MUST_SAVE_FIRST);
		if($mongoDocument->isClosed() && !$mongoDocument->isPropertyRequired($strProperty))
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_CLOSED_DOCUMENT, $strProperty));
		
		$modifier			= ($bUnique)?Mongo_Document_Abstract::MODIFIER_ADD_TO_SET:Mongo_Document_Abstract::MODIFIER_PUSH;
		$arrAction			= array($modifier => array($strProperty => $strItemToAdd));
		
		$options['safe']	= true;
		$options['upsert']	= true;
		$options['multiple']= false;
		
		$arrId[Mongo_Document_Abstract::FIELD_ID]
							= $mongoDocument->getByName(Mongo_Document_Abstract::FIELD_MONGO_ID);
		$this->raw_mongoCollection()->update($arrId, $arrAction, $options);
		return true;
								}
	
	private function raw_mongoCollection()											{
		/**
		 *	@purpose: 	Helper function to ensure that the mongoCollection is always valid
		 *			 	It is not expected that this will need to be called very frequently, however....
		 */
		if($this->isConnected())
			return $this->_rawMongoCollection;
		
		if(is_null($this->_Mongo_Connection))										{
			//Ok - we don't have a connection so see if we can load the default one!
			if(is_null($this->_strDatabase))										{
				//Try to load the default
				$this->_strDatabase	= Mongo_Connection::getDefaultDatabase();
				if(is_null($this->_strDatabase))
					throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
			}
			if(is_null(Mongo_Connection::getDefaultConnectionString()))
				throw new Mongo_Exception(Mongo_Exception::ERROR_CONNECTION_NULL);
			
			$this->_Mongo_Connection 		= new Mongo_Connection();
			$this->_Mongo_Connection->setDatabase($this->_strDatabase);
		}
		
		$this->_rawMongoCollection	= $this->_Mongo_Connection->getrawCollection($this->_strCollection);
		return $this->_rawMongoCollection;
	}
	
	//Implements Mongo_Connection_Interface
	public function connect()														{
		/**
		 *	@purpose: 	This attempts to crate the raw_mongoCollection
		 */
		$this->raw_mongoCollection();
	}
	public  function isConnected()													{
		/**
		 *	@purpose:	Is this collection connected to the database
		 */
		return !is_null($this->_rawMongoCollection);
	}
	public	function setConnection(Mongo_Connection $mongoConnection)				{
		/**
		 *	@purpose:	Sets the current Connection
		 */
		
		//Firstly check that this is for the right database
		if(!is_null($this->_strDatabase) 		&& $mongoConnection->getDatabase() != $this->_strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE);
		//Secondly check that this is for the right raw_Collection
		if(!is_null($this->_rawMongoCollection)	
		&& $this->_rawMongoCollection->db->__toString() != $mongoConnection->getDatabase())
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION);
		
		$this->_Mongo_Connection	= $mongoConnection;
		return true;
	}
	
	//Implements Countable
	public 	function count()														{
		return count($this->raw_mongoCollection());
	}
}