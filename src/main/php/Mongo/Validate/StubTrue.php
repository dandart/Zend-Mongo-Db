<?
/**
 * @category   
 * @package    
 * @copyright  2010-12-14, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */
class Mongo_Validate_StubTrue extends Zend_Validate_Abstract	{
	public function isValid($value)								{
		return true;
	}
}