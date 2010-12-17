<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class Mongo_Collection implements Countable											{
	const	  	DEFAULT_DOCUMENT_TYPE	= "Mongo_Document";
	private   	$_rawMongoCollection	= null;	//Holds the MongoCollection object 
												//NOTE: Don't access this directly use $this->raw_mongoCollection() instead
	
	/****
	 **	These are the parameters that can (optionally be overridden in the child classes)
	 ****/
	protected 	$_strClassDocumentType 	= self::DEFAULT_DOCUMENT_TYPE;
	protected 	$_strDatabase 			= null;
	protected 	$_strCollection			= null;
	/****
	 **	END
	 ****/
	
	public  function __construct(	$mixedVariable = null)							{
		/**
		 *	@purpose: 	This tries to create a Mongo_Collection
		 *	@param:		$mixedVariable - this is a very very versatile beast ;-)
		 *					= null 			=> This is ONLY possible in child classes where $_strDatabase has been over ridden
		 *									=> in this case we take the default connection and try to connect to $_strDatabase
		 *					= a Collection
		 *					= a Connection
		 *					= a Mongo_Db
		 */
		
		//Some sanity checking - basically we check that the default class exists
		$this->_strClassDocumentType	= $this->setDefaultDocumentType($this->_strClassDocumentType);
		
		if(is_null($mixedVariable))
			return $this->_constructFromConnection(null);
		if(is_object($mixedVariable))												{
			if(	is_a($mixedVariable,"MongoCollection"))
				return $this->_constructFromCollection($mixedVariable);
			if(	is_a($mixedVariable,"Mongo_Connection"))
				return $this->_constructFromConnection($mixedVariable);
			if( is_a($mixedVariable,"Mongo_Db"))
				return $this->_constructFromDatabase($mixedVariable);
		}
		
		throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
	}
	private function _constructFromCollection($mixedVariable)						{
		if($this->_strDatabase && ($mixedVariable->db->__toString() != $this->_strDatabase))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_DATABASE);
		if($this->_strCollection && ($mixedVariable->getName() 		!= $this->_strCollection))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_WRONG_COLLECTION);
		$this->_rawMongoCollection	= $mixedVariable;
		$this->_strDatabase		= $mixedVariable->db->__toString();
		$this->_strCollection	= $mixedVariable->getName();
	}
	private function _constructFromConnection($mixedVariable)						{
		if(!$this->_strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);
		if(!$this->_strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		$mongoDb					= new Mongo_Db($this->_strDatabase, $mixedVariable);
		$this->_rawMongoCollection	= $mongoDb->getRawCollection($this->_strCollection);
	}
	private function _constructFromDatabase($mixedVariable)							{
		if(!$this->_strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		$this->_rawMongoCollection	= $mixedVariable->getRawCollection($this->_strCollection);
	}
	public  function __toString()													{
		/**
		 *	@purpose: Returns the FULL name of the collection ie: Database.Collection
		 */
		return $this->raw_mongoCollection()->__toString();
	}
	
	public  function createDocument($arrDocument = null, $bDisconnect = false)		{
		/**
		 *	@purpose: 	This creates a default document for this collection
		 *	@return:	Mongo_Document (or child)
		 */
		$classDocument	= $this->getDocumentType($arrDocument);
		$mongoCollection= ($bDisconnect)?null:$this;
		return new $classDocument($arrDocument, $mongoCollection);
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
	public  function drop()															{
		/**
		 *	@purpose:	Drops this connection
		 */
		$return 	= $this->raw_mongoCollection()->drop();
		return $return;
	}
	public  function find($query = array(), $fields = array())						{
		/**
		 *	@purpose:	
		 * 	@param:		$query 	Array array(field => Value)
		 *	@param:		$fields	Array array(A,B,C) - the fields to return
		 *	@return:	returns Mongo_Document_Iterator
		 */
		if(null === $query || null === $fields)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		return new Mongo_Document_Iterator($this->raw_mongoCollection()->find($query, $fields), $this);
	}
	public  function findOne($query = array(), $fields = array())					{
		/**
		 *	@purpose:	
		 * 	@param:		$query 	Array array(field => Value)
		 *	@param:		$fields	Array array(A,B,C) - the fields to return
		 *	@return:	returns an object of Mongo_Document (or child of this)
		 *
		 */
		if(null === $query || null === $fields)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		$arrDocument	= $this->raw_mongoCollection()->findOne($query, $fields);
		if(!$arrDocument)
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
		return (class_exists($this->_strClassDocumentType))?$this->_strClassDocumentType:self::DEFAULT_DOCUMENT_TYPE;
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
			return $this->_strClassDocumentType = self::DEFAULT_DOCUMENT_TYPE;
		if(is_object($mixedClassDefaultDoc) && is_a($mixedClassDefaultDoc, self::DEFAULT_DOCUMENT_TYPE))
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
		if($mongoDocument->getDatabaseName() 	!= $this->getDatabaseName())
			throw new Mongo_Exception(Mongo_Exception::ERROR_DOCUMENT_WRONG_DATABASE);
		if($mongoDocument->getCollectionName() 	!= $this->getCollectionName())
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
							= $mongoDocument->getByName(Mongo_Document_Abstract::FIELD_ID);
		$this->raw_mongoCollection()->update($arrId, $arrAction, $options);
		
		return $this->findOne($arrId)->export();
								}
	
	private function raw_mongoCollection()											{
		/**
		 *	@purpose: 	Helper function to ensure that the mongoCollection is always valid
		 *			 	It is not expected that this will need to be called very frequently, however....
		 */
		if(!$this->_rawMongoCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		return $this->_rawMongoCollection;
	}
	
	//Implements Countable
	public 	function count()														{
		return count($this->raw_mongoCollection());
	}
}