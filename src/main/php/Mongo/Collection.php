<?php
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010-2012, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 * @author     Dan Dart
**/


class Mongo_Collection implements Countable
{	
	private 	$_Mongo_Connection		= null;	//This holds the Connection variable
		
	/****
	 **	These are the parameters that can (optionally be overridden in the child classes)
	****/
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
    **/
	public function __construct($strDatabaseName = null, $strCollectionName = null)	
	{
		//Some sanity checking - basically we check that the default class exists
		$this->setCollectionName($strCollectionName);
		$this->_strDatabase				= $this->setDatabaseName($strDatabaseName);
	}
	/**
	 *	@purpose:	Drops this connection
    **/
	public  function drop()
	{
		return $this->raw_mongoCollection()->drop();
	}
	
	public function ensureIndex($keys, Array $options = array())
	{
	    return $this->raw_mongoCollection()->ensureIndex($keys, $options);
	}
	
	/**
	 *	@purpose:	returns a cursor of data from querying the collection -
	 *      allow a read-only slave to do this
	 * 	@param:		$query 	Array array(field => Value)
	 *	@param:		$fields	Array array(A,B,C) - the fields to return
	 *	@return:	returns Mongo_Cursor
    **/
	public  function find($query = array(), $fields = array())
	{
		if(is_null($query) || is_null($fields)) {
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		}
		$cursor				= $this->raw_mongoCollection()->find($query, $fields)->slaveOkay();
		return new Mongo_Cursor($cursor, $this, $query, $fields);
	}
	/**
	 *	@purpose:	Find one document from a collection -
	 *      allow a read-only slave to do this
	 * 	@param:		$query 	Array array(field => Value)
	 *	@param:		$fields	Array array(A,B,C) - the fields to return
	 *	@return:	array
	 *
    **/
	public  function findOne($query = array(), $fields = array())
	{
		if(is_null($query) || is_null($fields)) {
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		}
        $arrReturn = $this->raw_mongoCollection()->findOne($query, $fields);
        return $arrReturn;
	}
	/**
	 *	@purpose:	Returns the name of the collection
	 *	@return:	string - the collection name
    **/
	public  function getCollectionName()
	{
		return $this->raw_mongoCollection()->getName();
	}
	/**
	 *	@purpose:	Returns the database that this Collection is attached to
    **/
	public  function getDatabaseName()
	{
		return $this->_strDatabase;
	}
	/**
	 *	@purpose:	This PRIVATE function does the sanity checking on setting the collection
    **/
	private function setCollection(MongoCollection $raw_MongoCollection = null)
	{
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
	private function setCollectionName($strCollectionName = null)
	{
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
	public  function setDatabaseName($strDatabaseName = null)
	{
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
	 * Is the array $ref a MongoDB reference?
	 *
	 * @param Array $ref 
	 * @return boolean
	 * @author Tim Langley
	**/
	public function isReference(Array $ref)
	{
    	return MongoDBRef::isRef($ref);
    }
	/**
	 *	@purpose:	decodes a DBReference
	 *	@param:		$arrReference	= array like: array($ref, $id, $database)
    **/
	public 	function decodeReference($arrReference)
	{	
		return $this->raw_mongoCollection()->getDBRef($arrReference);
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
	 *  Saves an array (rather than a Document)
	**/
	public function updateArray(Array $arrCriteria, Array $arrNewObject, $bSafe = true, $bMultiple = false, $bUpsert = true, $intTimeout = 30000)
	{
		$options["safe"]		= $bSafe;
		$options["multiple"]	= $bMultiple;
		$options["upsert"]		= $bUpsert;
		$options["timeout"]     = $intTimeout;
		$this->raw_mongoCollection()->update($arrCriteria, $arrNewObject, $options);
		return true;
	}
	public function removeArray(Array $arrCriteria, $bSafe = true)
	{
	    $options["safe"]		= $bSafe;
	    return $this->raw_mongoCollection()->remove($arrCriteria, $options);
}
	/**
	 * Batch inserts raw documents
	 *
	 * @param Array $arrDocuments 
	 * @param string $bSafe 
	 * @return void
	 * @author Dan Dart
	**/
	public function insertArrays(
	    Array $arrDocuments,
	    $bSafe = true,
	    $intTimeout = 30000,
	    $bContinueOnError = false
	) {
	    $arrOptions = array(
	        'safe' => $bSafe,
	        'timeout' => $intTimeout,
	        'continueOnError' => $bContinueOnError
	    );
        try {
            return $this->raw_mongoCollection()->batchInsert($arrDocuments, $arrOptions);
        } catch(MongoCursorException $e) {
            if (!$bContinueOnError) {
                throw $e;
            }
        }
	}
	/**
	 *	@purpose: 	Helper function to ensure that the mongoCollection is always valid
	 *			 	It is not expected that this will need to be called very frequently, however....
	**/
	private   	$_rawMongoCollection	= null;
	private function raw_mongoCollection()
	{
		if($this->isConnected())
			return $this->_rawMongoCollection;
		
		if(is_null($this->_Mongo_Connection))
			$this->_Mongo_Connection 		= new Mongo_Connection();

		$this->_rawMongoCollection			= $this->_Mongo_Connection->getrawCollection($this->_strDatabase, $this->_strCollection);
		return $this->_rawMongoCollection;
	}
	
	//Implements Mongo_Connection_Interface
	public function connect()
	{
		$this->raw_mongoCollection();
	}
	public  function isConnected()
	{
		return !is_null($this->_rawMongoCollection);
	}
	public	function setConnection(Mongo_Connection $mongoConnection)
	{
		$this->_Mongo_Connection	= $mongoConnection;
		return true;
	}
	
	//Implements Countable
	public 	function count()
	{
		return count($this->raw_mongoCollection());
	}
}