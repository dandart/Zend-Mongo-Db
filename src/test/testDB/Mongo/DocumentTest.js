/**
 * @category   
 * @package    
 * @copyright  2010-12-18, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
**/

db = db.getSisterDB("testMongo");
db.Accounts.drop();
db.Account_Users.drop();
db.testDocumentTest.drop();

dateCreated = new Date();
accountLCF  = { "AccountName":"LCF Computers Ltd", "AccountURL":"lcfcomputers", "DateCreated":dateCreated, "_Type":"Mongo_Document"};
db.Accounts.save(accountLCF);

accountJPB  = { "AccountName":"Just Paintball", "AccountURL":"justpaintball", "DateCreated":dateCreated, "_Type":"Mongo_Document"};
db.Accounts.save(accountJPB);

refLCF      = new DBRef('Accounts', accountLCF._id);
refJPB      = new DBRef('Accounts', accountJPB._id);
refOther    = new DBRef('Accounts', null);

accountUser = {"FirstName":"Tim", "Accounts":[  refLCF,  refJPB, refOther]}
db.Account_Users.save(accountUser);