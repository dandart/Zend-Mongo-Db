<?
/**
 * @category   
 * @package    
 * @copyright  2010-12-18, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
**/

interface Mongo_Connection_Interface {
	public function connect();
	public function isConnected();
	public function setConnection(Mongo_Connection $mongoConnection);
}