<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_DocumentSet is a placeholder for an Array of Documents ie: {"Tim":["Document1","Document2", ...]}
 * @copyright  2010-12-09, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class Mongo_DocumentSet extends Mongo_Document_Abstract implements Iterator, Countable	{
	private		$_Mongo_Collection		= null;	//Holds the Mongo_Collection that this Document belongs to
												//However - don't use this directly - use $this->mongoCollection() for safety
	private		$_intIteratorPosition	= 0;
												
	protected 	function mongoCollection()												{
		/**
		 *	@purpose: 	This handles the mongoCollection parameter
		 *	@return:	
		 */
		if(!$this->_Mongo_Collection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);

		return $this->_Mongo_Collection;
	}
	public 		function __construct($arrDocument, Mongo_Collection $mongoCollection)	{
		if(!$mongoCollection)
			throw new Mongo_Exception(Mongo_Exception::ERROR_COLLECTION_NULL);
			
		//NOTE: In a DocumentSet then the mongoCollection CAN'T bt null
		parent::__construct($arrDocument);
		$this->_Mongo_Collection		= $mongoCollection;
	}
	public 		function __get($name)													{
		/**
		 *	@purpose:	In a DocumentSet we can only return a _SpecialKey as a property & only when set!
		 *				(other items get returned as an array or an iterator)
		 */
	
		if(false !== array_search($name, $this->_arrSpecialKeys))
			return $this->getByName($name);
		throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
	}
	//Implements Countable
	public 		function count()														{
		/**
		 *	@purpose:	This counts the number of items in the Array
		 */
		$countSpecialKeys	= 0;
		foreach($this->_arrSpecialKeys AS $strKey)
			if($this->nameExists($strKey))
				$countSpecialKeys++;
				
		return count($this->export()) - $countSpecialKeys;
	}
	//Implements Iterator
	public 		function current()														{
		return $this->offsetGet($this->_intIteratorPosition);
	}
	public 		function key()															{
		return $this->_intIteratorPosition;
	}
	public 		function next()															{
		return ++$this->_intIteratorPosition;
	}
	public 		function rewind()														{
		$this->_intIteratorPosition		= 0;
	}
	public 		function valid()														{
		/**
		 *	@purpose: 	valid is slightly "special" because we have to iterate through the array 
		 *				BUT! we have to skip any of the "_arrSpecialKeys"
		 */
		if(!$this->offsetExists($this->_intIteratorPosition))
			return false;
		if(true == $this->isOffsetSpecial($this->_intIteratorPosition))					{
			$this->next();
			return $this->valid();
		}
		return true;
	}
}