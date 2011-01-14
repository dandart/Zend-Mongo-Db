<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_DocumentArray is a placeholder for an Array of Documents ie: {"Tim":["Document1","Document2", ...]}
 * @copyright  2010-12-09, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
**/
final class Mongo_DocumentArray extends Mongo_Document_Abstract {
	
	public 		function __get($name)														{
		/**
		 *	@purpose:	In a DocumentSet we can only return a _SpecialKey as a property & only when set!
		 *				(other items get returned as an array or an iterator)
		**/
	
		if(false !== array_search($name, self::$_arrSpecialKeys))
			return $this->getByName($name);
		throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_IMPLEMENTED);
	}

}