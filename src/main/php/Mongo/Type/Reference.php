<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @copyright  	2010-12-13, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     	Tim Langley
 */
class Mongo_Type_Reference 													{
	public static function create( string $collection , $id, $strDatabase)	{
		
	}
	public static function get($mixedValue, $arrReference)					{
		/**
		 *	@purpose: 	Decodes a DBReference
		 *	@param:		
		 */
		
	}
	public static function isRef($ref)										{
		return MongoDBRef::isRef($ref);
	}
}