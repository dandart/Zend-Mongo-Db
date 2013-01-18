<?php
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010-2012, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 * @author     Dan Dart
**/

class Mongo_Cursor implements OuterIterator, Countable
{
	private	$_cursor 				= null;	//This is the MongoCursor
	private $_strDatabaseName		= null;
	private $_strCollectionName     = null;
	private $_arrQuery              = array();
	private $_arrFields             = array();

	public function __construct(
	    MongoCursor $cursor,
	    Mongo_Collection $mongoCollection,
	    Array $arrQuery,
	    Array $arrFields
	) {
		/**
		 *	@purpose:	A cursor is a wrapper for the MongoCursor therefore it needs a "non-null" MongoCursor to create
		**/
		if (is_null($cursor) ||
		    is_null($mongoCollection->getCollectionName()) ||
		    is_null($mongoCollection->getDatabaseName())) {
			throw new Mongo_Exception(Mongo_Exception::ERROR_NOT_NULL);
		}
		$this->_cursor 				= $cursor;
		$this->_strCollectionName   = $mongoCollection->getCollectionName();
		$this->_strDatabaseName		= $mongoCollection->getDatabaseName();
		$this->_arrQuery            = $arrQuery;
		$this->_arrFields           = $arrFields;
	}
	public function getDatabaseName()
	{
	    return $this->_strDatabaseName;
	}
	public function limit($intLimit = 0)
	{
		/**
		 *	@purpose: 	Limits the a number of results to return
		 *	@param:		$intLimit - the number to return
		**/
		$this->_cursor = $this->_cursor->limit($intLimit);
		return $this;
	}
	public function sort($sort = array())
	{
		/**
		 *	@purpose:	Sorts the results in the Cursor
		 *	@param:		$sort	Array array(A => 1, B => -1 ) where A,B are the fields to sort on & 1 = ASC, -1 = DESC
		 *	@return:	$this (fluent) || Exception
		**/
		if(!empty($sort))
			$this->_cursor->sort($sort);
		return $this;
	}
	public function skip($intNoToSkip = 0)
	{
		/**
		 *	@purpose: 	Skips a number of results
		 *	@param:		$intNoToSkip - the number to count ahead
		**/
		$this->_cursor = $this->_cursor->skip($intNoToSkip);
		return $this;
	}

	//Implements OuterIterator
	public function getInnerIterator()
	{
		/**
		 *	@purpose: Returns the MongoCursor
		**/
		return $this->_cursor;
	}
	public function export()
	{
		/**
		 *	@purpose:	Exports the Iterator as an array
		**/
		$this->rewind();
		return iterator_to_array($this->getInnerIterator());
	}
	public function current()
	{
		/**
		 *	@purpose:	Returns the (array?) of the the the cursor currently represents
		 *
		 *	@todo:		Probably should replace this with returning a Mongo_Document
		**/
		try {
		    return $this->_cursor->current();
		} catch(Exception $e) { // Should happen for everything
		    $this->_rethrow($e);
		}
	}
	public function getNext()
	{
		$this->next();
		return $this->current();
	}
	public function key()
	{
		return $this->getInnerIterator()->key();
	}
	public function next()
	{
	    try {
		    return $this->getInnerIterator()->next();
		} catch(Exception $e) { // Should happen for everything
		    $this->_rethrow($e);
		}
	}
	public function rewind()
	{
	    try {
		    return $this->getInnerIterator()->rewind();
		} catch(Exception $e) { // Should happen for everything
		    $this->_rethrow($e);
		}
	}
	public function valid()
	{
	    try {
		    return $this->getInnerIterator()->valid();
		} catch(Exception $e) { // Should happen for everything
		    $this->_rethrow($e);
		}
	}
	public function info()
	{
		return $this->getInnerIterator()->info();
	}

	/**
	 * Add timeout to the MongoCursor
	 *
	 * @param int $intTimeout
	 * @return $this
	 * @author Dan Dart
	**/
	public function timeout($intTimeout)
	{
        $this->getInnerIterator()->timeout($intTimeout);
        return $this;
	}

	private function _rethrow(Exception $e)
	{
	    throw new Mongo_Exception(
	        sprintf(
	            Mongo_Exception::ERROR_CURSOR_EXCEPTION,
	            $e->getMessage(),
	            $this->getDatabaseName(),
	            $this->_strCollectionName,
	            json_encode($this->_arrQuery),
	            json_encode($this->_arrFields)
	        )
	    );
	}

	//Implements Countable
	/**
	 *	@purpose 	This returns the size of the cursor
	 *	@param:		$bFoundOnly	= false	=> returns the entire size of the cursor
	 *							= true	=> returns jus the size of the dataset to be returned
	**/
	public function count($bFoundOnly = false)
	{
		try {
			return $this->getInnerIterator()->count($bFoundOnly);
		} catch(Exception $e) {
			return -1;
		}
	}
}
