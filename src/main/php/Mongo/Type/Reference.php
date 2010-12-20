<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @copyright  	2010-12-13, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     	Tim Langley
 */
class Mongo_Type_Reference 													{
	public static function create(Mongo_Document $mongoDocument)			{
		/**
		 *	@purpose: 	This creates a DB Reference 
		 *	@param:		Mongo_Document to create reference to
		 */
		
		//Is the Document null
		if(is_null($mongoDocument))
			throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_NULL);
		//Has the Document been saved
		if(true == $mongoDocument->isNew())
			throw new Mongo_Exception(Mongo_Exception::ERROR_MUST_SAVE_FIRST);
		
		//Are the Database and Collection valid
		if(is_null($mongoDocument->getCollectionName()) || is_null($mongoDocument->getDatabaseName()))
			throw new Mongo_Exception(Mongo_Exception::ERROR_MISSING_VALUES);
		
		$mongoId		= new MongoId($mongoDocument[Mongo_Document_Abstract::FIELD_ID]);
		
		$arrReference	= MongoDBRef::create(	$mongoDocument->getCollectionName()
											, 	$mongoId
											,	$mongoDocument->getDatabaseName());
		$arrReference[Mongo_Document_Abstract::FIELD_TYPE]	= get_class($mongoDocument);
		return $arrReference;
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

