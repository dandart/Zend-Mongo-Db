<?php
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_DocumentArray is a placeholder for an Array of Documents ie: {"Tim":["Document1","Document2", ...]}
 * @copyright  2010-12-09, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
**/

/**
 *	@purpose:	A DocumentArray is formed from the [ ] json structure
 *				Elements within the Array are referenced by offset rather than by associative name
**/
final class Mongo_DocumentArray extends Mongo_Document_Abstract implements Iterator			{
	private		$_intIteratorPosition	= 0;
	
	public 		function __get($name)														{
		/**
		 *	@purpose:	In a DocumentSet we can only return a _SpecialKey as a property & only when set!
		 *				(other items get returned as an array or an iterator)
		**/
	
		if(false !== array_search($name, self::$_arrSpecialKeys))
			return $this->getByName($name);
		throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
	}

	//Implements Iterator
	public 		function current()															{
		return $this->offsetGet($this->_intIteratorPosition);
	}
	public 		function key()																{
		return $this->_intIteratorPosition;
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
		if(!$this->offsetExists($this->_intIteratorPosition))
			return false;
		if(true == $this->isOffsetSpecial($this->_intIteratorPosition))						{
			$this->next();
			return $this->valid();
		}
		return true;
	}
}