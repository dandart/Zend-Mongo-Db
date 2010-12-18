<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */

class Mongo_Document_Iterator implements OuterIterator, Countable					{
	private	$_cursor 				= null;	//This is the MongoCursor
	private $_Mongo_Connection		= null; //This is the Mongo_Connection
	
	public function __construct(MongoCursor $cursor, Mongo_Connection $mongoConn)	{
		$this->_cursor 				= $cursor;
		$this->_Mongo_Connection	= $mongoConn;
	}
	
	//Implements OuterIterator
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
		$classDocument	= Mongo_Document_Abstract::getDocumentClass($strDefault, $arrDocument);
		$docDocument	= new $classDocument($arrDocument);
		if($this->_Mongo_Connection)
			$docDocument->setConnection($this->_Mongo_Connection);
		return $docDocument;
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
	public function info()															{
		return $this->getInnerIterator()->info();
	}
	
	//Implements Countable
	public function count()															{
		return $this->getInnerIterator()->count();
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
