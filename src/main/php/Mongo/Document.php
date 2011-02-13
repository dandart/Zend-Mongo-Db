<?php
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_Document is the base class for everything todo with a Document in Mongo
 *				(with the exception of an Array of Documents which are held in a DocumentSet)
 * @copyright  	2010, Campaign and Digital Intelligence Ltd
 * @license    	New BSD License
 * @author     	Tim Langley
**/
class Mongo_Document	extends Mongo_Document_Abstract implements Iterator			{
	private		$_intIteratorPosition	= 0;
	
	public  	function __get($name)												{
		/**
		 *	@purpose: Get the Document Properties
		**/
		return $this->getByName($name);
	}
	public  	function __set($name, $value)										{
		/**
		 *	@purpose:	Set the Document Properties
		**/
		return $this->setByName($name, $value);
	}
	
	public		function addItemToArray($strElementName, $strItemToAdd, $bUnique)	{
		/**
		 *	@purpose:	This performs an update $push or $addToSet (depending on $bUnique)
		 *				NOTE: This matches on the _Id
		 *	@param:		$strElementName	- the array to add this element to
		 *	@param:		$strElementToAdd- the item to add
		 *	@param:		$bUnique	= true 	(perform a $addToSet)
		 *							= false	(perform a $push)
		**/
		$this->mongoCollection()->addToArray($this, $strElementName, $strItemToAdd, $bUnique);
		$arrId				= array(Mongo_Document_Abstract::FIELD_ID => $this->getId());
		$mongoDocument		= $this->mongoCollection()->findOne($arrId);
		$arrDocument		= $mongoDocument->export();
		$this->setArrDocument($arrDocument);
		return true;
	}
	public		function createToken()												{
		/**
		 *	@purpose:	Ok this a little naughty having this here (but it's a useful helper function)
		 *				This creates a random md5 string
		**/
		$chars 				= "abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@£$%^&*()"; 
		srand((double)microtime()*1000000); 
		$strToken			= "";
		$lenChars			= strlen($chars);
		for($i = 0; $i < 8; $i++)
			$strToken 		.= substr($chars, rand() % $lenChars, 1);
		return md5($strToken);
	}
	/**
	 *	@purpose:	This executes the find function from the collection
	 *	@return:	cursor
	**/
	public		function find($arrQuery, $arrFields)								{
		return $this->mongoCollection()->find($arrQuery, $arrFields);
	}
	/**
	 *	@purpose:	Returns whether this document has been saved before
	 *				defined by whether the _id element exists
	 *	@return:	true | false
	**/
	public 		function isNew()													{
		return $this->_bIsNew;
	}
	/**
	 *	@purpose:	Saves the document (actually it does an upsert)
	 *	@return:	fluent interface - returns $this
	 *
	 *	@todo:		Probably this should accept a query so that we can save a document at different positions
	**/
	public  	function save()														{
		$bNewDocument	= $this->isNew();
		($bNewDocument)?$this->_PreInsert():$this->_PreUpdate();
		$this->_PreSave();
		$arrDocument	= $this->mongoCollection()->save($this)->export();
		$this->setArrDocument($arrDocument);
		$this->_PostSave();
		($bNewDocument)?$this->_PostInsert():$this->_PostUpdate();
		return $this;
	}
	public 		function update(Array $arrCriteria, Array $arrNewObject)			{
		if($this->isNew())
			throw new Mongo_Exception(Mongo_Exception::ERROR_MUST_SAVE_FIRST);
		$this->_PreUpdate();
		$arrDocument	= $this->mongoCollection()->update($this, $arrCriteria, $arrNewObject)->export();
		$this->setArrDocument($arrDocument);
		$this->_PostUpdate();
		return $this;
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
	//Implements Iterator
	public 		function current()															{
		//Now for a Document where the array is an associative array we need to get the Key Name a the offset X
		$indexes 		= array_keys($this->_arrDocument);
		return $this->getByName($indexes[$this->_intIteratorPosition]);
	}
	public 		function key()																{
		$indexes 		= array_keys($this->_arrDocument);
		return $indexes[$this->_intIteratorPosition];
	}
	public 		function next()																{
		return ++$this->_intIteratorPosition;
	}
	public 		function rewind()															{
		$this->_intIteratorPosition		= 0;
	}
	public 		function valid()															{
		/**
		 *	@purpose: 	valid is slightly "special" because we have to iterate through the array 
		 *				BUT! we have to skip any of the "_arrSpecialKeys"
		**/
		$indexes 		= array_keys($this->_arrDocument);
		if(!isset($indexes[$this->_intIteratorPosition]))
			return false;
		
		$value			= $indexes[$this->_intIteratorPosition];
		if(array_search($value, self::$_arrSpecialKeys))									{
			$this->next();
			return $this->valid();
		}
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