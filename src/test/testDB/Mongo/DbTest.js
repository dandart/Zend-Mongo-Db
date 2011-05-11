/**
 * @category   
 * @package    
 * @copyright  2010-12-15, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
**/
db = db.getSisterDB("testMongo");
db.dropDatabase();

db = db.getSisterDB("testMongo");

dateCreated = new Date();
accountLCF  = { "AccountName":"LCF Computers Ltd", "AccountURL":"lcfcomputers", "DateCreated":dateCreated};
db.Accounts.save(accountLCF);