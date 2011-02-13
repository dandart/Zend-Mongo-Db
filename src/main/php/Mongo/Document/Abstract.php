<?php
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		This base class deals with the _arrDocument which is the array "passed back" from calls to MongoDB
 * @copyright  	2010-12-10, Campaign and Digital Intelligence Ltd
 * @license    	New BSD License
 * @author     	Tim Langley
**/

abstract class Mongo_Document_Abstract implements ArrayAccess, Mongo_Connection_Interface, Countable {
	const		FIELD_CLOSED			= "_Closed";	//Holds true | false 
														//(if true then Properties are limited to _requirements)
	const		FIELD_COLLECTION		= "_ref:Collection";
	const		FIELD_DATABASE			= "_ref:Database";
	const		FIELD_ID				= "_id";		//Holds the MongoId
	const		FIELD_MONGO_ID			= "_MongoId";
	const		FIELD_REFERENCE			= "_ref:Name";
	const		FIELD_TYPE				= "_Type";		//Holds the "class" for this document
	
	
	const		MODIFIER_ADD_TO_SET		= '$addToSet';
	const		MODIFIER_PUSH			= '$push';
	
	const		REQ_OPTIONAL			= "Optional";
	const		REQ_REQUIRED			= "Required";
	
	/****
	 **	These are the parameters that can (optionally be overridden in the child classes)
	****/
	protected 	$_strDatabase 			= null;
	protected 	$_strCollection			= null;
	protected	$_classCollectionType	= Mongo_Connection::TYPE_DEFAULT_COLLECTION;
	/****
	 **	END
	****/
	
	//This holds a cached array of ZendRequirements (either Validators or Filters)
	//This is static so that we only load these once per request and they're live for all documents
	private		static $_cachedZendReq	= array();
	//This holds the array of "special" (ie system private) keys
	//This is static because it's really a constant
	protected 	static $_arrSpecialKeys	= array ( self::FIELD_CLOSED
												, self::FIELD_COLLECTION
												, self::FIELD_ID
												, self::FIELD_REFERENCE
												, self::FIELD_TYPE
												);
	
	protected 	$_Mongo_Connection;
	private 	$_Mongo_Collection		= null;	//Holds the Mongo_Collection object
												//Recommend accessing this through $this->mongoCollection() through
	
	protected 	$_arrDocument			= null;
	protected	$_arrRequirements		= array();
	protected	$_bClosed				= false;
	protected	$_bIsNew				= false;
	
	protected 	function mongoConnection()											{
		if(!is_null($this->_Mongo_Connection))
			return $this->_Mongo_Connection;
		
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
		return $this->_Mongo_Connection;
	}	
	protected	function mongoCollection()											{
		/**
		 *	@purpose: 	This handles the mongoCollection parameter (if it's null then this tries to create from the connection...)
		 *	@return:	class Mongo_Collection (or more specifically the class in $_classCollectionType)
		**/
		if(!is_null($this->_Mongo_Collection))
			return $this->_Mongo_Collection;
		
		//If there's no existing collection then lets see if we can create one!
		if(is_null($this->_strCollection))
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
		
		$mongoConnection			= $this->mongoConnection();
		$this->_Mongo_Collection	= $mongoConnection->getCollection(	$this->_strDatabase
																	 , 	$this->_strCollection
			 														 ,	$this->_classCollectionType);
		return $this->_Mongo_Collection;
	}
	public 		final function __construct($arrDocument = null)						{
		$this->setArrDocument($arrDocument);
		//Clean up the requirements
		$this->_arrRequirements			= $this->makeRequirementsTidy($this->_arrRequirements);
	}
	private		function createDocument($classDocument, $arrData, $strDatabase, $bSameColn = true){
		$docAbstract		= new $classDocument($arrData);
		if($this->_Mongo_Connection)
			$docAbstract->setConnection($this->_Mongo_Connection);
		
		if($strDatabase)
			$docAbstract->setDatabaseName($strDatabase);
		
		if($bSameColn && $this->_strCollection)
			$docAbstract->setCollectionName($this->_strCollection);
		return $docAbstract;
	}
	protected 	function setArrDocument($arrDocument)								{
		$this->_bIsNew								= is_null($arrDocument)?true:false;
		if(!$arrDocument)
			$arrDocument							= array();
		$this->_arrDocument							= $arrDocument;
		
		if(		isset($arrDocument[self::FIELD_TYPE]) 
			&& 	$arrDocument[self::FIELD_TYPE] 		!= get_class($this))			{
			throw new Mongo_Exception(Mongo_Exception::ERROR_ARRAY_WRONG_TYPE);
		}
		$this->_arrDocument[self::FIELD_TYPE]		= get_class($this);
		if($this->_bClosed)
			$this->_arrDocument[self::FIELD_CLOSED]	= $this->_bClosed;
	}
	public		function getByName($name)											{
		//firstly if the property doesn't exist then leave this
		if(!$this->nameExists($name) && !$this->requirementExists($name))
			return null;
		return $this->_getValue($name);
	}
	private 	function _getValue($key)											{
		/**
		 *	@purpose:	getValue - this decodes the values in _arrDocument (ie is Array, value etc...)
		 *	@param:		$key	 - EITHER! the $arrDocument->Name or $arrDocument[(int)offset]
		**/
		$data	= (isset($this->_arrDocument[$key]))?$this->_arrDocument[$key]:null;
		if(!is_array($data))														{
			if(self::FIELD_ID 		== $key)
				return (string)$data;
			if(self::FIELD_MONGO_ID == $key)										{
				if(is_null($this->_arrDocument[self::FIELD_ID]))
					return null;
				return new MongoId($this->_arrDocument[self::FIELD_ID]);
			}
			
			$arrRequirements 	= (isset($this->_arrRequirements[$key]))?$this->_arrRequirements[$key]:null;
			$classDocument		= self::getDocumentClass(null, null, $arrRequirements);
			
			if(is_null($classDocument))
				return $data;
			return $this->createDocument($classDocument, $data, $this->getDatabaseName());
		}
		//Otherwise time to go to work!
		$bIsReference						= Mongo_Type_Reference::isRef($data);
		if ($bIsReference) 															{
			$mongoConnection				= $this->mongoConnection();
			$arrDocument					= $mongoConnection->decodeReference($data, $this->getDatabaseName());
			if(!$arrDocument)
				return $this->_setValue($key, null);
				
			$strDefault						= Mongo_Connection::TYPE_MONGO_DOCUMENT;
			$strDatabase					= isset($data['$db'])?$data['$db']:$this->getDatabaseName();
			$classDocument					= self::getDocumentClass($strDefault, $data, null);
			return 							$this->createDocument($classDocument, $arrDocument, $strDatabase, false);
		}
		//We try to determine the type of document
		$arrArrayKeys	= array_keys($data);
		$strDefault		= (0 === $arrArrayKeys[0])?Mongo_Connection::TYPE_MONGO_DOCUMENT_SET:Mongo_Connection::TYPE_MONGO_DOCUMENT;
		$arrRequirements= (isset($this->_arrRequirements[$key]))?$this->_arrRequirements[$key]:null;
		$classDocument	= self::getDocumentClass($strDefault, $data, $arrRequirements);
		
		return $this->createDocument($classDocument, $data, $this->getDatabaseName());
	}
	protected	function setByName($name, $value)									{
		/**
		 *	@purpose:	Sets the Array value (by name) ie: $Array->Name = Value
		**/
		if(false !== array_search($name, self::$_arrSpecialKeys) 
			&& (self::FIELD_ID == $name 
				&& isset($this->_arrDocument[self::FIELD_ID]) 
				&& !is_null($this->_arrDocument[self::FIELD_ID])))					{
			throw new Mongo_Exception(Mongo_Exception::ERROR_READ_ONLY);
		}
		return $this->_setValue($name, $value);
	}
	private 	function _setValue($key, $value)									{
		/**
		 *	@purpose: 	This does the actual setting of the value
		 *				DO NOT CALL THIS DIRECTLY - use setByName (for names) or offsetSet (for offsets)
		 *	@key:		The array key or the offset	
		 *	@value:		The value to set it to
		**/
		
		//If this is a Closed Document then validate that Key is in Requirements
		if(		isset($this->_arrDocument[self::FIELD_CLOSED])
			&& 	true 	==  $this->_arrDocument[self::FIELD_CLOSED]
			&&  false 	==  array_search($key, self::$_arrSpecialKeys)
			&& 	false 	=== array_key_exists($key, $this->_arrRequirements))
				throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_CLOSED_DOCUMENT, $key));
		
		//Check that this is valid
		$validators 	= $this->getValidators($key);
		if (!is_null($value) && !$validators->isValid($value))
			throw new Mongo_Exception(implode($validators->getMessages(), "\n"));
		
		if(is_null($value))															{
			unset($this->_arrDocument[$key]);
			return null;
		}
		if(Mongo_Connection::TYPE_MONGO_DOCUMENT	== gettype($value) 
		|| Mongo_Connection::TYPE_MONGO_DOCUMENT_SET == gettype($value))
			//If we're trying to save (set) a Document or Document set then we should export to get the raw data
			//Note: This will do an integrity check at this stage
			return $this->_arrDocument[$key]	= $value->export();
		else
			return $this->_arrDocument[$key]	= $value;
	}
	
	public 		function getId()													{
		return isset($this->_arrDocument[self::FIELD_ID])?$this->_arrDocument[self::FIELD_ID]:null;
	}
	public		function getType()													{
		return isset($this->_arrDocument[self::FIELD_TYPE])?$this->_arrDocument[self::FIELD_TYPE]:null;
	}
	public  	function getCollectionName()										{
		/**
		 *	@purpose: Returns the name of the Collection that this document belongs to
		**/
		return $this->_strCollection;
	}
	public  	function getDatabaseName()											{
		/**
		 *	@purpose:	Returns the name of the database that this document belongs to
		**/
		return $this->_strDatabase;
	}
	public  	function setDatabaseName($strDatabase)								{
		if(!$strDatabase)
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_DATABASE);

		$this->_strDatabase = $strDatabase; 
		return true;
	}
	public  	function setCollectionName($strCollection)							{
		if(!$strCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);

		$this->_strCollection = $strCollection;
		return true;
	}
	
	public  	function export()													{
		/**
		 *	@purpose:	Returns the document as an array 
		 *				NOTE: This does an integrity check to ensure that all require options are included
		 *	@return:	$_arrDocument
		**/
		// make sure required properties are not empty
		$requiredProperties = $this->getPropertiesWithRequirement(self::REQ_REQUIRED);
		foreach ($requiredProperties as $property)
			if (   !isset($this->_arrDocument[$property]) 
				|| (is_array($this->_arrDocument[$property]) 
					&& empty($this->_arrDocument[$property])))
				throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_PROPERTY_REQUIRED, $property));
						
		//If this is a Closed Document then validate that all the Keys are in Requirements
		if(		isset($this->_arrDocument[self::FIELD_CLOSED])
			&& 	true 	== $this->_arrDocument[self::FIELD_CLOSED]
			)
			foreach ($this->_arrDocument as $key => $property)	{
				if(		false ==  array_key_exists($key, $this->_arrRequirements)
					&&  false === array_search($key, self::$_arrSpecialKeys))
					throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_CLOSED_DOCUMENT, $key));
			}
		$this->_arrDocument[self::FIELD_TYPE]	= get_class($this);
		return $this->_arrDocument;
	}
	
	//The Requirements code
	public 		function getPropertiesWithRequirement($requirement)					{
		/**
		 *	@purpose: 	Returns an array of all the requirements which have ONE specific requirement
		 *				Example: find all the requirements which have "Required"
		**/
		$properties = array();
		foreach ($this->_arrRequirements as $property => $requirementList) 			{
			if (strpos($property, '.') > 0) 
				continue;
			if (array_key_exists($requirement, $requirementList)) {
				$properties[] = $property;
			}
		}
		return $properties;
	}
	public 		function getFilters($strProperty)									{
		/**
		 *	@purpose:	Get all the filters attached to a specific property
		 *	@param:		$strProperty
		 *	@return:	class of Zend_Filter
		**/
		$filters = new Zend_Filter();
		if (!array_key_exists($strProperty, $this->_arrRequirements)) 
			return $filters;
		
		foreach ($this->_arrRequirements[$strProperty] as $requirement => $options)	{
			$filter 	 = $this->retrieveZendReq($requirement, $options);
			if (!$filter || !($filter instanceof Zend_Filter_Interface)) 
				continue;
			$filters->addFilter($filter);
		}
		return $filters;
	}
	public 		function getValidators($strProperty)								{
		/**
		 *	@purpose:	Get all the validators attached to a specific property
		 *	@param:		$strProperty
		 *	@return:	class of Zend_Validate
		**/
		$validators 		= new Zend_Validate();
		if (!array_key_exists($strProperty, $this->_arrRequirements)) 
			return $validators;
		foreach ($this->_arrRequirements[$strProperty] as $requirement => $options) {
			$validator 		= $this->retrieveZendReq($requirement, $options);
			if (!$validator || !($validator instanceof Zend_Validate_Interface)) 
				continue;
			$validators->addValidator($validator);
		}
		return $validators;
	}
	public 		function isValid($property, $value)									{
		/**
		 *	@purpose: 	Checks if the $value is valid for the $property
		 *	@return:	true | false
		**/
		return $this->getValidators($property)->isValid($value);
	}
	private  	function makeRequirementsTidy(array $requirements) 					{
		/**
		 *	@purpose:	This "tidies up" the requirements into a "neat list"
		**/
		
		//Firstly lets check if we are dealing with a "pull out of existing object" situtation
		if(		isset($requirements[self::FIELD_COLLECTION]) 
			|| 	isset($requirements[self::FIELD_REFERENCE]))						{
				
				//WE STILL HAVE TO IMPLEMENT THIS
					throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
				
			}
		
		foreach ($requirements as $property => $requirementList) 					{
			if (!is_array($requirementList))
				$requirements[$property] 				= array($requirementList);
			$newRequirementList = array();
			foreach ($requirements[$property] as $key => $requirement) 				{
				if (is_numeric($key))
					$newRequirementList[$requirement] 	= null;
				else 
					$newRequirementList[$key] 			= $requirement;
			}
			$requirements[$property] 					= $newRequirementList;
		}
		return $requirements;
	}
	public		function requirementExists($name)									{
		/**
		 *	@purpose:	Checks if the required name exists in _arrRequirement 
		 *	@param:		$name - the name of the parameter $_arrRequirement->Name
		 *	@return:	true | false
		**/
		if(is_null($this->_arrRequirements))
			return false;
		return isset($this->_arrRequirements[$name]);
	}
	public 	function setRequirement($strProperty, $requirement, $arrOptions = null)	{
		/**
		 *	@purpose:	This sets the the options for a specific requirement for a specific property
		 *				If the property already has this requirement then it's over-written
		 *	@param:		$strProperty	- the property field to set requirement for
		 *	@param:		$requirement	- the requirement to set this for (example: "Required", "Filter:StringTrim")
		 *	@param:		$arrOptions		- any additional options for this requirement
		**/
		if (false === array_key_exists($strProperty, $this->_arrRequirements))
			$this->_arrRequirements[$strProperty] 				= array();
		
		$this->_arrRequirements[$strProperty][$requirement] 	= $arrOptions;
	}
	public 		function unsetRequirement($strProperty, $requirement)				{
		/**
		 *	@purpose:	Unsets a requirement for a specific property
		 *	@param:		$strProperty	- the property field to set requirement for
		 *	@param:		$requirement	- the requirement to unset
		**/
		if (!array_key_exists($strProperty, $this->_arrRequirements)) 
			return true;
		
		foreach ($this->_arrRequirements[$strProperty] as $requirementItem => $options)
			if ($requirement === $requirementItem) 									{
				unset($this->_arrRequirements[$strProperty][$requirementItem]);
				return true;
			}
		return true;
	}
		//Caching the Zend Requirements
	public 		function createZendObject($name, $options = null)					{
		$arrName		= explode("_", $name);
		$instanceClass 	= 'Zend_'.$name;
		$interfaceClass	= 'Zend_'.$arrName[0].'_Interface';
		if (!class_exists($instanceClass)) 
			return null;
		$validator 		= (is_null($options))?new $instanceClass():new $instanceClass($options);
		if (!($validator instanceof $interfaceClass)) 
			return null;
		return $validator;
	}
	public 	 	function retrieveZendReq($name, $options = null)					{
		/**
		 *	@purpose: 	We try to cache requirement items for speed
		 *	@param:		$name		the name of the requirement to cache (ex: Required, StringLower etc...)
		 *	@param:		$options (optional) anything to pass to the requirement constructor
		**/
		if (!array_key_exists($name, self::$_cachedZendReq)) 						{
			//ok - we got this far now to actually create the requirements
			switch($name)															{
				case 'Mongo_Document':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('Mongo_Document');
					break;
				case 'Mongo_DocumentArray':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('Mongo_DocumentArray');
					break;
				case 'AsReference':
				case 'Optional':
				case 'Required':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_StubTrue();
					break;
				case 'Validate_MongoId':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('MongoId');
					break;
				case 'Validate_MongoDate':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('MongoDate');
					break;
				default:
					//Otherwise! - ideally we've got a "real" validator or "real" filter here
					if("Validate_" == substr($name,0,9))
						self::$_cachedZendReq[$name]= $this->createZendObject($name,$options);
					elseif("Filter_"== substr($name,0,7))
						self::$_cachedZendReq[$name]= $this->createZendObject($name,$options);
		//@TODO -
		//	We should introduce Types for the Document & DocumentSet
		//		ie: Document:CANDDi_Mongo_Admin_Person which would create a Doc of class ...
		//		NOTE we need to validate that it exists here AND then Decode properly below on the GET
					else
						self::$_cachedZendReq[$name]= null;
			}
		}
		if(null === self::$_cachedZendReq[$name])
			throw new Mongo_Exception(sprintf(Mongo_Exception::ERROR_REQUIREMENT_NOT_EXIST, $name));
			
		if (is_null($options))
			return self::$_cachedZendReq[$name];
		$requirementClass 		= get_class(self::$_cachedZendReq[$name]);
		return new $requirementClass($options);
	}
	
	//For Closed Documents
	public		function isClosed()													{
		/**
		 *	@purpose:	Is this a closed Document
		 *	@return:	true | false
		**/
		return $this->_bClosed;
	}
	public 		function isPropertyRequired($strRequirement)						{
		/**
		 *	@purpose:	Does this element have a required or optional flag set
		 *	@param:		strRequirement	- the property to search on
		 *	@return:	true | false
		**/
		//Firstly check if it's a special element
		if(true == array_search($strRequirement, self::$_arrSpecialKeys))
			return true;
			
		//Secondly check if it's in the requirements (and if so if it's required | optional)
		if(	true == array_key_exists($strRequirement, $this->_arrRequirements)
		&&( 	true == array_key_exists(self::REQ_REQUIRED, $this->_arrRequirements[$strRequirement])
			|| 	true == array_key_exists(self::REQ_OPTIONAL, $this->_arrRequirements[$strRequirement])))
			return true;
			
		//Then return false
		return false;
	}
	
	//DocumentClass
	public static function getDocumentClass($strDefault, $arrDocument = null, $arrRequirements = null)		{
		/**
		 *	@purpose: 	This is a "global" helper function - it's used in collections, documentIterators, 
		 *					documents and documentsets
		 *				This returns the CLASS to use to create a new document
		 *				The following order is followed:
		 *					1. Is the _Type key set in the Document's data 
		 *					2. Is the key defined in the Document's requirements (and is it a document | document set)
		 *					3. Is there a default document type
		 *					4. If not then return Mongo_Document | Mongo_DocumentArray (depending on whether an array|value input)
		 *	@param:		$strDefault 	- the default class to use in absence of any others
		 *	@param:		$arrDocument	- the document to decode
		**/
		
		if(isset($arrDocument) && isset($arrDocument[Mongo_Document::FIELD_TYPE]))
			return class_exists($arrDocument[Mongo_Document::FIELD_TYPE])
						?$arrDocument[Mongo_Document::FIELD_TYPE]:Mongo_Connection::TYPE_MONGO_DOCUMENT;
		if(	is_array($arrRequirements) && array_key_exists(Mongo_Connection::TYPE_MONGO_DOCUMENT_SET, $arrRequirements))
			return Mongo_Connection::TYPE_MONGO_DOCUMENT_SET;
			
		if(	is_array($arrRequirements) && array_key_exists(Mongo_Connection::TYPE_MONGO_DOCUMENT, $arrRequirements))
			return Mongo_Connection::TYPE_MONGO_DOCUMENT;

		if(is_null($strDefault))
			return null;
			
		return (class_exists($strDefault))?$strDefault:Mongo_Connection::TYPE_MONGO_DOCUMENT;
	}
	
	//Implements (for __get requests)
	public 		function nameExists($name)											{
		/**
		 *	@purpose:	Checks if the required name exists in _arrDocument
		 *	@param:		$name - the name of the parameter $_arrDocument->Name
		 *	@return:	true | false
		**/
		if(is_null($this->_arrDocument))
			return false;
		if(self::FIELD_MONGO_ID == $name)
			$name	= self::FIELD_ID;
		return isset($this->_arrDocument[$name]);
	}
	public 		function isOffsetSpecial($offset)									{
		/**
		 *	@purpose: 	Returns whether the $offset is in the _arrSpecialKeys 
		 *	@param:		$offset	= integer the offset
		 *	@return:	true | false
		**/
		$arrKeys	= array_keys($this->_arrDocument);
			//This does a "clever" check - since the offset could be a number or could be a name
		$name		= (	($arrKeys && isset($arrKeys[$offset]))
					  ||(false !== array_key_exists($offset, $this->_arrDocument)))?$arrKeys[$offset]:null;
		return (false != array_search($name, self::$_arrSpecialKeys));
	}
	
	//Implements Mongo_Connection_Interface
	public 		function connect()													{
		//NOTE - this is handled differently in each class - hence we're throwing error here
		if(!$this->isConnected())
			$this->mongoCollection();
		return true;
	}
	public		function isConnected()												{
		//NOTE - this is handled differently in each class - hence we're throwing error here
		return is_null($this->_Mongo_Collection);
	}
	public 		function setConnection(Mongo_Connection $mongoConnection)			{
		/**
		 *	@purpose:	Sets the Connection for this document / document set
		**/
		$this->_Mongo_Connection	= $mongoConnection;
	}
	//Implements ArrayAccess
	public    	function offsetExists($offset)										{
		if(!$this->_arrDocument)
			return false;
		return isset($this->_arrDocument[$offset]);
	}
	public    	function offsetGet($offset)											{
		if(!$this->offsetExists($offset))
			return null;
		return $this->_getValue($offset);
	}
	public		function offsetSet($offset, $value)									{
		if(!$this->offsetExists($offset))
			return null;
		if(true == $this->isOffsetSpecial($offset))
			throw new Mongo_Exception(Mongo_Exception::ERROR_READ_ONLY);
		return $this->_setValue($offset, $value); 
	}
	public  	function offsetUnset($offset)										{
		if(!$this->_arrDocument)
			return false;
		unset($this->_arrDocument[$offset]);
	}
	
	//Implements Countable
	public 		function count()															{
		/**
		 *	@purpose:	This counts the number of items in the Array
		**/
		$countSpecialKeys	= 0;
		foreach(self::$_arrSpecialKeys AS $strKey)
			if($this->nameExists($strKey))
				$countSpecialKeys++;			
		return count($this->export()) - $countSpecialKeys;
	}
	
}