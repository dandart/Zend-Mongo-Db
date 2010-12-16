<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @copyright  	2010-12-13, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     	Tim Langley
 */
class Mongo_Reference {
/*	public static function create( string $collection , mixed $id [, string $database ] )
	public static function get($mixedValue, $arrReference)	{
		/**
		 *	@purpose: 	Decodes a DBReference
		 *	@param:		$mixedValue - this must be either a Mongo_Collection, Mongo_Db (or must extend from them)
		 * /
		
	}*/
	public static function isRef($ref)						{
		return MongoDBRef::isRef($ref);
	}
}