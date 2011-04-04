<?php
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
**/


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
	
	/**
	 *	@purpose: 	This tries to create a Mongo_Collection
	 *	@param:		$mixedVariable - this is a very very versatile beast ;-)
	 *					= null 			=> This is ONLY possible in child classes where $_strDatabase has been over ridden
	 *									=> in this case we take the default connection and try to connect to $_strDatabase
	 *					= a Collection
	 *					= a Connection
	*/
	public  final function __construct($strDatabaseName = null, $strCollectionName = null)	{
		

		//Some sanity checking - basically we check that the default class exists
		$this->setCollectionName($strCollectionName);
		$this->_strDatabase				= $this->setDatabaseName($strDatabaseName);
		//	This deals with situtations where the $this->_strClassDocumentType has been overridden
		$this->_strClassDocumentType	= $this->setDefaultDocumentType($this->_strClassDocumentType);
		
	}
	/**
	 *	@purpose: 	Returns the name of the collection
	 *	@NOTE:		This does NOT mean that the collection is connected
	*/
	public  function __toString()													{
		
		return $this->_strCollection;
	}
	/**
	 *	@purpose: 	This creates a default document for this collection
	 *	@return:	Mongo_Document (or child)
	**/
	public  function createDocument($arrDocument = array())							{
		
		$classDocument				= $this->getDocumentType($arrDocument);
		
		$docAbstract				= new $classDocument($arrDocument);
		if($docAbstract instanceof Mongo_Document)
		{
		    if($this->_Mongo_Connection)
			    $docAbstract->setConnection($this->_Mongo_Connection);
		    if($this->_strDatabase)
			    $docAbstract->setDatabaseName($this->_strDatabase);
		    if($this->_strCollection)
			    $docAbstract->setCollectionName($this->_strCollection);
		}
		return $docAbstract;
	}
	/**
	 *	@purpose:	Drops this connection
	*/
	public  function drop()															{
		
		$return 	= $this->raw_mongoCollection()->drop();
		return $return;
	}
	/**
	 *	@purpose:	
	 * 	@param:		$query 	Array array(field => Value)
	 *	@param:		$fields	Array array(A,B,C) - the fields to return
	 *	@param:		$sort	Array array(A => 1, B => -1 ) where A,B are the fields to sort on & 1 = ASC, -1 = DESC
	 *	@return:	returns Mongo_Document_Cursor
	*/
	public  function find($query = array(), $fields = array(), $sort = array())		{
		
		if(is_null($query) || is_null($fields))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		$cursor				= $this->raw_mongoCollection()->find($query, $fields);
		$iterDocument		= new Mongo_Document_Cursor($cursor, $this->_Mongo_Connection, $this->getDatabaseName());
		return $iterDocument->sort($sort);
	}
	/**
	 *	@purpose:	
	 * 	@param:		$query 	Array array(field => Value)
	 *	@param:		$fields	Array array(A,B,C) - the fields to return
	 *	@return:	returns an object of Mongo_Document (or child of this)
	 *
	*/
	public  function findOne($query = array(), $fields = array())					{
		
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
	/**
	 *	@purpose:	Returns the name of the collection
	 *	@return:	string - the collection name
	*/
	public  function getCollectionName()											{
		
		return $this->raw_mongoCollection()->getName();
	}
	/**
	 *	@purpose:	Works out what type of Document class should be created
	 *				NOTE: 	This checks if an existing document is trying to be "recreated" and if so it the _Type is valid
	 *				SECOND:	This checks if the collection has a default type
	*/
	private function getDocumentType($arrDocument = array())						{
		
		return Mongo_Document_Abstract::getDocumentClass($this->getDefaultDocumentType(), $arrDocument);
	}
	/**
	 *	@purpose:	Returns the database that this Collection is attached to
	*/
	public  function getDatabaseName()												{
		
		return $this->_strDatabase;
	}
	public 	function getDefaultDocumentType()										{
		return (class_exists($this->_strClassDocumentType))?$this->_strClassDocumentType:Mongo_Connection::TYPE_MONGO_DOCUMENT;
	}
	/**
	 *	@purpose:	This PRIVATE function does the sanity checking on setting the collection
	*/
	private function setCollection(MongoCollection $raw_MongoCollection = null)		{
		
		if(is_null($raw_MongoCollection))
			return true;
		
		//Now check that the CollectionNames are the same
		if(!is_null($this->_strCollection) 		&& $raw_MongoCollection->getName() != $this->_strCollection)
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION,
											$this->_strCollection,$raw_MongoCollection->getName()));
		//Then check that the DatabaseNames are the same
		if(!is_null($this->_strDatabase) 		&& $raw_MongoCollection->db->__toString() != $this->_strDatabase)
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE,
											$this->_strDatabase, $raw_MongoCollection->db->__toString()));
		
		$this->_rawMongoCollection				= $raw_MongoCollection;
	}
	private function setCollectionName($strCollectionName = null)					{
		if(is_null($strCollectionName) && is_null($this->_strCollection))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		
		if(is_null($strCollectionName))
			return true;
		
		if(!is_null($this->_strCollection) && $this->_strCollection != $strCollectionName)
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION,
											$this->_strCollection,$strCollectionName));
		
		$this->_strCollection = is_null($this->_strCollection)?$strCollectionName:$this->_strCollection;
		return true;
	}
	/**
	 *	@purpose:	Sets the Database Name
	**/
	public  function setDatabaseName($strDatabaseName = null)						{
		
		
		if(!is_null($this->_strDatabase))			
			if($this->_strDatabase == $strDatabaseName || is_null($strDatabaseName))
				return $this->_strDatabase;
			else
				throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE,
													$this->_strDatabase, $strDatabaseName));
											
		if(!is_null($strDatabaseName) && is_string($strDatabaseName))
			return $this->_strDatabase	= $strDatabaseName;
		
		throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		
	}
	/**
	 *	@purpose:	decodes a DBReference
	 *	@param:		$arrReference	= array like: array($ref, $id, $database)
	*/
	public 	function decodeReference($arrReference)									{
		
		$arrDocument	= $this->raw_mongoCollection()->getDBRef($arrReference);
		if(!$arrDocument)
			return null;
		return $this->createDocument($arrDocument, true);
	}
	/**
	 *	@purpose: 	This sets the defaultDocumentType for this collection
	 *	@param:		$classDefautDoc - this can be 
	 *					a) string = the name of the class
	 *					b) object = an instance of the class
	 *					c) null   = reset to default
	*/
	public 	function setDefaultDocumentType($mixedClassDefaultDoc = null)			{	
		//it's null - return a default Mongo_Document
		if(is_null($mixedClassDefaultDoc))
			return $this->_strClassDocumentType = Mongo_Connection::TYPE_MONGO_DOCUMENT;
		
		//It's a Mongo_Document (or child of)
		if(is_object($mixedClassDefaultDoc) && is_a($mixedClassDefaultDoc, Mongo_Connection::TYPE_MONGO_DOCUMENT))
			return $this->_strClassDocumentType = get_class($mixedClassDefaultDoc);
		
		//It's a string
		if(is_string($mixedClassDefaultDoc) 
			&& (is_subclass_of($mixedClassDefaultDoc, Mongo_Connection::TYPE_MONGO_DOCUMENT) 
				|| Mongo_Connection::TYPE_MONGO_DOCUMENT == $mixedClassDefaultDoc))
			return $this->_strClassDocumentType = $mixedClassDefaultDoc;
			
		throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
	}
	/**
	 *	@purpose:	Inserts a query into the database
	 *	@param:		$mongoDocument - the document to be inserted
	 *	@param:		$bSafe		- true (default) => it will wait for success before returning
	 *	@return:	array of the data returned (typically this includes a MongoId field)
	 *
	*/
	public  function insert(Mongo_Document $mongoDocument, 	$bSafe = true)			{		
		$options["safe"]	= $bSafe;
		$arrDocument		= $mongoDocument->export();
		$this->raw_mongoCollection()->insert($arrDocument, $options);
		return $mongoDocument;
	}
	/**
	 *  Saves an array (rather than a Document)
	**/
	public function saveArray(Array $arrData, $bSafe = true)
	{
	    $options["safe"]	= $bSafe;
		$this->raw_mongoCollection()->save($arrData, $options);
		return $arrData;
	}
	/**
	 *	@purpose:	Performs an Upsert on the Mongo_Document
	 *	@param:		$mongoDocument - the document to be saved
	*/
	public  function save(Mongo_Document $mongoDocument, 	$bSafe = true)			{
		
		$arrDocument		= $mongoDocument->export();
		
		//Now check that the Document and Collection belong to same Collection and same Database
		if( !is_null($this->getDatabaseName()) 
		&& (!is_null($mongoDocument->getDatabaseName()))
		&& ($mongoDocument->getDatabaseName() 	!= $this->getDatabaseName()))
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_DATABASE
												,$mongoDocument->getDatabaseName(),$this->getDatabaseName()));
		if( !is_null($this->getCollectionName()) 
		&& (!is_null($mongoDocument->getCollectionName()))
		&& ($mongoDocument->getCollectionName() 	!= $this->getCollectionName()))
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION
												,$mongoDocument->getCollectionName(),$this->getCollectionName()));
		//Now check that the Required parameters are all valid
				
		$options["safe"]	= $bSafe;
		$this->raw_mongoCollection()->save($arrDocument, $options);
		return $this->createDocument($arrDocument);
	}
	/**
	 *  Saves an array (rather than a Document)
	**/
	public function updateArray(Array $arrCriteria, Array $arrNewObject, $bSafe = true, $bMultiple = false)
	{
		$options["safe"]		= $bSafe;
		$options["multiple"]	= $bMultiple;
		$options["upsert"]		= true;
		$this->raw_mongoCollection()->update($arrCriteria, $arrNewObject, $options);
		return true;
	}
	/**
	 *	@purpose:	performs an update (upsert)
	*/
	public  function update ( Mongo_Document 	$mongoDocument
							, Array				$arrCriteria
							, Array 			$arrNewObject)						{

		//Now check that the Document and Collection belong to same Collection and same Database
		if( !is_null($this->getDatabaseName()) 
		&& (!is_null($mongoDocument->getDatabaseName()))
		&& ($mongoDocument->getDatabaseName() 	!= $this->getDatabaseName()))
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_DATABASE
												,$mongoDocument->getDatabaseName(),$this->getDatabaseName()));
		if( !is_null($this->getCollectionName()) 
		&& (!is_null($mongoDocument->getCollectionName()))
		&& ($mongoDocument->getCollectionName() 	!= $this->getCollectionName()))
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_DOCUMENT_WRONG_COLLECTION
												,$mongoDocument->getCollectionName(),$this->getCollectionName()));
		//Now check that the Required parameters are all valid
		$options["safe"]		= true;
		$options["multiple"]	= false;
		$options["upsert"]		= true;
		$this->raw_mongoCollection()->update($arrCriteria, $arrNewObject, $options);
		$arrDocument["_id"]		= $mongoDocument->getId();
		return $this->findOne($arrDocument);
							}
	public 	function addToArray(Mongo_Document $mongoDocument, $strProperty, 
								$strItemToAdd, $bUnique)							{
		/**
		 *	@purpose: 	This adds a new element to an array in the Mongo_Document
		 *				The Mongo_Document must be saved first so that we have a _id field
		 *	@param:		$mongoDocument - the document to update
		 *	@param:		
		**/
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
							= $mongoDocument->getId();
		$this->raw_mongoCollection()->update($arrId, $arrAction, $options);
		return true;
								}
	
	private function raw_mongoCollection()											{
		/**
		 *	@purpose: 	Helper function to ensure that the mongoCollection is always valid
		 *			 	It is not expected that this will need to be called very frequently, however....
		**/
		if($this->isConnected())
			return $this->_rawMongoCollection;
		
		if(is_null($this->_Mongo_Connection))
			$this->_Mongo_Connection 		= new Mongo_Connection();

		$this->_rawMongoCollection			= $this->_Mongo_Connection->getrawCollection($this->_strDatabase, $this->_strCollection);
		return $this->_rawMongoCollection;
	}
	
	//Implements Mongo_Connection_Interface
	public function connect()														{
		/**
		 *	@purpose: 	This attempts to crate the raw_mongoCollection
		**/
		$this->raw_mongoCollection();
	}
	public  function isConnected()													{
		/**
		 *	@purpose:	Is this collection connected to the database
		**/
		return !is_null($this->_rawMongoCollection);
	}
	public	function setConnection(Mongo_Connection $mongoConnection)				{
		/**
		 *	@purpose:	Sets the current Connection
		**/
		$this->_Mongo_Connection	= $mongoConnection;
		return true;
	}
	
	//Implements Countable
	public 	function count()														{
		return count($this->raw_mongoCollection());
	}
}