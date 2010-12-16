<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		This base class deals with the _arrDocument which is the array "passed back" from calls to MongoDB
 * @copyright  	2010-12-10, Campaign and Digital Intelligence Ltd
 * @license    	New BSD License
 * @author     	Tim Langley
 */

abstract class Mongo_Document_Abstract implements ArrayAccess						{
	const		FIELD_ID				= "_id";
	const		FIELD_TYPE				= "_Type";
	
	//This holds a cached array of ZendRequirements (either Validators or Filters)
	private		static $_cachedZendReq	= array();
	
	
	private 	$_arrDocument			= null;
	protected	$_arrSpecialKeys		= array ( self::FIELD_ID
												, self::FIELD_TYPE
												);
	protected	$_arrRequirements		= array();
	
	public 		function __construct($arrDocument = null)							{
		$this->setArrDocument($arrDocument);
		//Clean up the requirements
		$this->_arrRequirements			= $this->makeRequirementsTidy($this->_arrRequirements);
	}
	protected 	function setArrDocument($arrDocument)								{
		$this->_arrDocument				= $arrDocument;
		//Set the magic _Type
		$this->_arrDocument[self::FIELD_TYPE]	= get_class($this);
	}
	protected	function getByName($name)											{
		//firstly if the property doesn't exist then leave this
		if(!$this->nameExists($name))
			return null;
		return $this->_getValue($name);
	}
	private 	function _getValue($key)											{
		/**
		 *	@purpose:	getValue - this decodes the values in _arrDocument (ie is Array, value etc...)
		 *	@param:		$key	 - EITHER! the $arrDocument->Name or $arrDocument[(int)offset]
		 */
		$data	= $this->_arrDocument[$key];
		if(!is_array($data))														{
			//It's not an array - lets just return it!
			return $data;
		}
		//Otherwise time to go to work!
		$bIsReference						= Mongo_Reference::isRef($data);
		if ($bIsReference) 															{
			$document						= $this	->mongoCollection()->decodeReference($data);
			if(!$document)
				return $this->__set($key, null);
			return $document;
		}
		
		//Ok - so it's an array (is it an embedded document or an array or embedded documents?)
		
		//At the moment - in lieu of having requirements set-up we're using a horrible hack!
		//If the first element of the array_keys is "0" then this is a Mongo_DocumentSet otherwise it's a Mongo_Document
		$arrArrayKeys	= array_keys($data);
		$classDocument	= (0 === $arrArrayKeys[0])?"Mongo_DocumentSet":"Mongo_Document";	
														//@TODO - actually should draw this from the type
		
//@TODO - there are LOTS of problems here!
//	1. How do we deal with "different Document types - ie Address type shouldn't be a Mongo_Document"
//	2. How do we save these new documents - how do they hold the history of where they came from
//	3. (todo later - how do we deal with requirements)
	
		//For now we make new "Mongo_Documents" - but later we should allow the requriements to specify if these are different
		$mongoDocument						= new $classDocument($data, $this->mongoCollection());
		return $mongoDocument;
	}
	protected	function setByName($name, $value)									{
		/**
		 *	@purpose:	Sets the Array value (by name) ie: $Array->Name = Value
		 */
		if(false !== array_search($name, $this->_arrSpecialKeys))
			throw new Mongo_Exception(Mongo_Exception::ERROR_READ_ONLY);
		return $this->_setValue($name, $value);
	}
	private 	function _setValue($key, $value)									{
		/**
		 *	@purpose: 	This does the actual setting of the value
		 *				DO NOT CALL THIS DIRECTLY - use setByName (for names) or offsetSet (for offsets)
		 *	@key:		The array key or the offset	
		 *	@value:		The value to set it to
		 */
		
		//Check that this is valid
		$validators 	= $this->getValidators($key);
		if (!is_null($value) && !$validators->isValid($value))
			throw new Mongo_Exception(implode($validators->getMessages(), "\n"));
		
		if(is_null($value))															{
			unset($this->_arrDocument[$key]);
			return null;
		}
		return $this->_arrDocument[$key]	= $value;
	}
	
	abstract	protected function mongoCollection();
	
	public  	function export()													{
		/**
		 *	@purpose:	Returns the document as an array 
		 *	@return:	$_arrDocument
		 */
		return $this->_arrDocument;
	}
	
	//The Requirements code
	public 		function getPropertiesWithRequirement($requirement)					{
		/**
		 *	@purpose: 	Returns an array of all the requirements which have ONE specific requirement
		 *				Example: find all the requirements which have "Required"
		 */
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
		 */
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
		 */
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
		 */
		return $this->getValidators($property)->isValid($value);
	}
	private  	function makeRequirementsTidy(array $requirements) 					{
		foreach ($requirements as $property => $requirementList) 					{
			if (!is_array($requirementList))
				$requirements[$property] = array($requirementList);
			$newRequirementList = array();
			foreach ($requirements[$property] as $key => $requirement) 				{
				if (is_numeric($key)) $newRequirementList[$requirement] = null;
				else $newRequirementList[$key] = $requirement;
			}
			$requirements[$property] = $newRequirementList;
		}
		return $requirements;
	}
	public 	function setRequirement($strProperty, $requirement, $arrOptions = null)	{
		/**
		 *	@purpose:	This sets the the options for a specific requirement for a specific property
		 *				If the property already has this requirement then it's over-written
		 *	@param:		$strProperty	- the property field to set requirement for
		 *	@param:		$requirement	- the requirement to set this for (example: "Required", "Filter:StringTrim")
		 *	@param:		$arrOptions		- any additional options for this requirement
		 */
		if (!array_key_exists($strProperty, $this->_arrRequirements))
			$this->_arrRequirements[$strProperty] 				= array();
		
		$this->_arrRequirements[$strProperty][$requirement] 	= $arrOptions;
	}
	public 		function unsetRequirement($strProperty, $requirement)				{
		/**
		 *	@purpose:	Unsets a requirement for a specific property
		 *	@param:		$strProperty	- the property field to set requirement for
		 *	@param:		$requirement	- the requirement to unset
		 */
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
	public 		function createZendFilter($name, $options = null)					{
		$instanceClass 	= 'Zend_'.$name;
		if (!class_exists($instanceClass)) 
			return null;
		$validator 		= (is_null($options))?new $instanceClass():new $instanceClass($options);
		if (!($validator instanceof Zend_Filter_Interface)) 
			return null;
		return $validator;
	}
	public 		function createZendValidator($name, $options = null)				{
		$instanceClass 	= 'Zend_'.$name;
		if (!class_exists($instanceClass)) 
			return null;
		$validator 		= (is_null($options))?new $instanceClass():new $instanceClass($options);
		if (!($validator instanceof Zend_Validate_Interface)) 
			return null;
		return $validator;
	}
	public 	 	function retrieveZendReq($name, $options = null)					{
		/**
		 *	@purpose: 	We try to cache requirement items for speed
		 *	@param:		$name		the name of the requirement to cache (ex: Required, StringLower etc...)
		 *	@param:		$options (optional) anything to pass to the requirement constructor
		 */
		if (!array_key_exists($name, self::$_cachedZendReq)) 						{
			//ok - we got this far now to actually create the requirements
			switch($name)															{
				case 'Document':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('Mongo_Document');
					break;
				case 'DocumentSet':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('Mongo_DocumentSet');
					break;
				case 'AsReference':
				case 'Optional':
				case 'Required':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_StubTrue();
					break;
				case 'Validator:Array':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Array();
					break;
				case 'Validator:MongoId':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('MongoId');
					break;
				case 'Validator:MongoDate':
					self::$_cachedZendReq[$name]	= new Mongo_Validate_Class('MongoDate');
					break;
				default:
					//Otherwise! - ideally we've got a "real" validator or "real" filter here
					if("Validate_" == substr($name,0,9))
						self::$_cachedZendReq[$name]= $this->createZendValidator($name,$options);
					elseif("Filter_"== substr($name,0,7))
						self::$_cachedZendReq[$name]= $this->createZendFilter($name,$options);
					else
						self::$_cachedZendReq[$name]= null;
			}
		}
		if(null === self::$_cachedZendReq[$name])
			throw new Mongo_Exception("Requirement doesn't exist for $name");
			
		if (is_null($options))
			return self::$_cachedZendReq[$name];
		$requirementClass 		= get_class(self::$_cachedZendReq[$name]);
		return new $requirementClass($options);
	}
	
	//Implements (for __get requests)
	public 		function nameExists($name)											{
		/**
		 *	@purpose:	Checks if the required name exists in _arrDocument
		 *	@param:		$name - the name of the parameter $_arrDocument->Name
		 *	@return:	true | false
		 */
		if(!$this->_arrDocument)
			return false;
		return isset($this->_arrDocument[$name]);
	}
	public 		function isOffsetSpecial($offset)									{
		/**
		 *	@purpose: 	Returns whether the $offset is in the _arrSpecialKeys 
		 *	@param:		$offset	= integer the offset
		 *	@return:	true | false
		 */
		$arrKeys	= array_keys($this->_arrDocument);
			//This does a "clever" check - since the offset could be a number or could be a name
		$name		= (	($arrKeys && isset($arrKeys[$offset]))
					  ||(false !== array_key_exists($offset, $this->_arrDocument)))?$arrKeys[$offset]:null;
		return (false != array_search($name, $this->_arrSpecialKeys));
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
}