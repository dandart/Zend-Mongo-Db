<?
/**
 * @category   	MongoDB
 * @package    	Mongo
 * @purpose		A Mongo_Document is the base class for everything todo with a Document in Mongo
 *				(with the exception of an Array of Documents which are held in a DocumentSet)
 * @copyright  	2010, Campaign and Digital Intelligence Ltd
 * @license    	New BSD License
 * @author     	Tim Langley
 */
class Mongo_Document extends Mongo_Document_Abstract 								{
	
	public  	function __get($name)												{
		/**
		 *	@purpose: Get the Document Properties
		 */
		return $this->getByName($name);
	}
	public  	function __set($name, $value)										{
		/**
		 *	@purpose:	Set the Document Properties
		 */
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
		 */
		$this->mongoCollection()->addToArray($this, $strElementName, $strItemToAdd, $bUnique);
		$arrId[Mongo_Document_Abstract::FIELD_ID]
							= $this->getByName(Mongo_Document_Abstract::FIELD_MONGO_ID);
		$mongoDocument		= $this->mongoCollection()->findOne($arrId);
		$arrDocument		= $mongoDocument->export();
		$this->setArrDocument($arrDocument);
		return true;
	}
	public		function createToken()												{
		/**
		 *	@purpose:	Ok this a little naughty having this here (but it's a useful helper function)
		 *				This creates a random md5 string
		 */
		$chars 				= "abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@£$%^&*()"; 
		srand((double)microtime()*1000000); 
		$strToken			= "";
		$lenChars			= strlen($chars);
		for($i = 0; $i < 8; $i++)
			$strToken 		.= substr($chars, rand() % $lenChars, 1);
		return md5($strToken);
	}
	public 		function isNew()													{
		/**
		 *	@purpose:	Returns whether this document has been saved before
		 *				defined by whether the _id element exists
		 *	@return:	true | false
		 */
		return !$this->nameExists(Mongo_Document_Abstract::FIELD_ID);
	}
	public  	function save()														{
		/**
		 *	@purpose:	Saves the document (actually it does an upsert)
		 *	@return:	fluent interface - returns $this
		 *
		 *	@todo:		Probably this should accept a query so that we can save a document at different positions
		 */
		$bNewDocument	= $this->isNew();
		($bNewDocument)?$this->_PreInsert():$this->_PreUpdate();
		$this->_PreSave();
		$this->setArrDocument($this->mongoCollection()->save($this)->export());
		$this->_PostSave();
		($bNewDocument)?$this->_PostInsert():$this->_PostUpdate();
		return $this;
	}
	
	//Overload these functions in children to gain functionaliaty
	protected 	function _PreInsert()												{}
	protected 	function _PostInsert()												{}
	protected	function _PreSave()													{}
	protected	function _PostSave()												{}
	protected	function _PreUpdate()												{}
	protected	function _PostUpdate()												{}
}