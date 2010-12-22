<?
/**
 * @category   MongoDB
 * @package    Mongo
 * @copyright  2010, Campaign and Digital Intelligence Ltd
 * @license    New BSD License
 * @author     Tim Langley
 */
class Mongo_Exception extends Exception	{
	const ERROR_ALREADY_SAVED				= "Can't create this document has already been saved";
	const ERROR_ARRAY_WRONG_TYPE			= "The saved document is of the wrong class";
	const ERROR_CLOSED_DOCUMENT				= "This is a closed document. Property '{%s}' isn't in the requirements";
	const ERROR_COLLECTION_NULL				= "Collection parameter is empty";
	const ERROR_COLLECTION_WRONG_COLLECTION	= "Integrity error - this collection '{%s}' doesn't belong in this collection '{%s}'";
	const ERROR_COLLECTION_WRONG_DATABASE	= "Integrity error - this database '{%s}' doesn't belong in this database '{%s}'";
	const ERROR_CONNECTION_NULL				= "Connection empty";
	const ERROR_CURSOR_NULL					= "Cursor null";
	const ERROR_DOCUMENT_ALREADY_CONNECTED	= "Integrity error - this document already has a connection";
	const ERROR_DOCUMENT_WRONG_COLLECTION	= "Integrity error - Document (Collection) '{%s}' doesn't belong in this collection '{%s}'";
	const ERROR_DOCUMENT_WRONG_DATABASE		= "Integrity error - Document (Database) '{%s}' doesn't belong in this database '{%s}'";
	const ERROR_FILE_NOT_FOUND				= "File not found";
	const ERROR_MISSING_DATABASE			= "Database name parameter is empty";
	const ERROR_MISSING_VALUES				= "Missing values";
	const ERROR_MUST_SAVE_FIRST				= "Must save the document first";
	const ERROR_NOT_IMPLEMENTED				= "Not implemented";
	const ERROR_NOT_NULL					= "Value can't be empty";
	const ERROR_PROPERTY_REQUIRED			= "Property '{%s}' is required and must not be null.";
	const ERROR_READ_ONLY					= "Read Only";
	const ERROR_REQUIREMENT_NOT_EXIST		= "Requirement doesn't exist for '{%s}'";
	const ERROR_UNKNOWN						= "An unknown error has occurred";
}
