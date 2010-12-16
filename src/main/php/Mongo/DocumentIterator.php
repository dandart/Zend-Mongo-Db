<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class Mongo_DocumentIterator implements OuterIterator, Countable					{
	private	$_cursor 				= null;	//This is the MongoCursor
	private	$_Mongo_Collection		= null;	//This is the Mongo_Collection
	
	public function __construct(MongoCursor $cursor, Mongo_Collection $mongoCollection = null) {
		$this->_cursor 				= $cursor;
		$this->_Mongo_Collection 	= $mongoCollection;
	}
	private function getDocumentClass($arrDocument = null)							{
		/**
		 *	@purpose:	Works out what type of Document class should be created
		 *				NOTE: 	This checks if an existing document is trying to be "recreated" and if so it the _Type is valid
		 *				SECOND:	This checks if the collection has a default type
		 */
		if(isset($arrDocument) && isset($arrDocument[Mongo_Document::FIELD_TYPE]))
			return class_exists($arrDocument[Mongo_Document::FIELD_TYPE])
						?$arrDocument[Mongo_Document::FIELD_TYPE]:Mongo_Mongo_Collection::DEFAULT_DOCUMENT_TYPE;
		if(isset($this->_Mongo_Collection))
			return $this->_Mongo_Collection->getDefaultDocumentType();
		return Mongo_Mongo_Collection::DEFAULT_DOCUMENT_TYPE;
	}
	public function getInnerIterator()												{
		/**
		 *	@purpose: Returns the MongoCursor
		 */
		return $this->_cursor;
	}
	public function export()														{
		/**
		 *	@purpose:	Exports the Iterator as an array
		 */
		$this->rewind();
		return iterator_to_array($this->getInnerIterator());
	}
	public function current()														{
		/**
		 *	@purpose:	Returns the (array?) of the the the cursor currently represents
		 *	
		 *	@todo:		Probably should replace this with returning a Mongo_Document
		 */
		$arrDocument	= $this->getInnerIterator()->current();
		$classDocument	= $this->getDocumentClass($arrDocument);
		return new $classDocument($arrDocument, $this->_Mongo_Collection);
	}	
	public function getNext()														{
		$this->next();
		return $this->current();
	}
	public function key()															{
		return $this->getInnerIterator()->key();
	}
	public function next()															{
		return $this->getInnerIterator()->next();
	}
	public function rewind()														{
		return $this->getInnerIterator()->rewind();
	}
	public function valid()															{
		return $this->getInnerIterator()->valid();
	}
	public function count()															{
		return $this->getInnerIterator()->count();
	}
	public function info()															{
		return $this->getInnerIterator()->info();
	}
	public function __call($method, $arguments)										{
		/**
		 *	@purpose:	To save implementing every method ;-) this forwards to the raw Cursor
		 *	@param:		$method 	(string the method to call)
		 *	@param:		$arguments	array of the arguments to call
		 */
		$res = call_user_func_array(array($this->getInnerIterator(), $method), $arguments);
		
		if ($res instanceof MongoCursor)
			return $this;
		
		return $res;
	}
}
