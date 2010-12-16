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
		$classMixedVariable		= get_class($mixedVariable);
		
		if(		"MongoCollection" 	== $classMixedVariable	
			|| 	is_subclass_of($mixedVariable, "MongoCollection"))
					return $this->_constructFromCollection($mixedVariable);
		if(		null == $mixedVariable
			||	"Mongo_Connection" 	== $classMixedVariable 	
			|| 	is_subclass_of($mixedVariable, "Mongo_Connection"))
					return $this->_constructFromConnection($mixedVariable);
		if("Mongo_Db" 			== $classMixedVariable 	|| is_subclass_of($mixedVariable, "Mongo_Db"))
			return $this->_constructFromDatabase($mixedVariable);
		//Otherwise ....
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
		return $this->raw_mongoCollection()->toString();
	}
	
	public  function createDefaultDocument()										{
		$classDocument	= $this->getDocumentClass();
		return new $classDocument();
	}
	public 	function decodeReference($arrReference)									{
		/**
		 *	@purpose:	decodes a DBReference
		 *	@param:		$arrReference	= array like: array($ref, $id, $database)
		 */
		$data	= $this->raw_mongoCollection()->getDBRef($arrReference);
		if(!$data)
			return null;
		$strDocumentClass					= $this->getDocumentClass($data);
		return new $strDocumentClass($data);
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
		 *	@return:	returns Mongo_DocumentIterator
		 */
		if(null === $query || null === $fields)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		return new Mongo_DocumentIterator($this->raw_mongoCollection()->find($query, $fields), $this);
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
		$classType		= $this->getDocumentClass($arrDocument);
		return new $classType($arrDocument, $this);
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
	private function getDocumentClass($arrDocument = null)							{
		/**
		 *	@purpose:	Works out what type of Document class should be created
		 *				NOTE: 	This checks if an existing document is trying to be "recreated" and if so it the _Type is valid
		 *				SECOND:	This checks if the collection has a default type
		 */
		if(isset($arrDocument) && isset($arrDocument[Mongo_Document::FIELD_TYPE]))
			return class_exists($arrDocument[Mongo_Document::FIELD_TYPE])
						?$arrDocument[Mongo_Document::FIELD_TYPE]:self::DEFAULT_DOCUMENT_TYPE;
		return $this->getDefaultDocumentType();
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
	public 	function mongoDatabase()												{
		/**
		 *	@purpose: Returns the Mongo_Db object that this collection is connected to
		 */
		return new Mongo_Db($this->getDatabaseName());
	}
//@todo:		Maybe we should restrict this to being a Mongo_Document ?
	public  function insert($arrInsert, $bSafe = true)								{
		/**
		 *	@purpose:	Inserts a query into the database
		 *	@param:		$arrInsert	- php array to serialize
		 *	@param:		$bSafe		- true (default) => it will wait for success before returning
		 *	@return:	array of the data returned (typically this includes a MongoId field)
		 *
* 	@todo:		Maybe we should restrict this to being a Mongo_Document ?
		 */
		$options["safe"]	= $bSafe;
		$this->raw_mongoCollection()->insert($arrInsert, $options);
		return $arrInsert;
	}
	public  function save(Mongo_Document $mongoDocument, $bSafe = true)				{
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
		
		// make sure required properties are not empty
		$requiredProperties = $mongoDocument->getPropertiesWithRequirement('Required');
		foreach ($requiredProperties as $property)									{
			if (   !isset($arrDocument[$property]) 
				|| (is_array($arrDocument[$property]) 
					&& empty($arrDocument[$property]))) 							{
				throw new Mongo_Exception("Property '{$property}' is required and must not be null.");
			}
		}
				
		$options["safe"]	= $bSafe;
		$this->raw_mongoCollection()->save($arrDocument, $options);
		$classType			= $this->getDocumentClass($arrDocument);
		return new $classType($arrDocument, $this);
	}
	
	public 	function raw_mongoCollection()											{
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