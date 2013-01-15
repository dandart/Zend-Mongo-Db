/**
 * @category   
 * @package    
 * @copyright  2013-01-15, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Dan Dart
**/
db = db.getSisterDB("testMongo");
db.CollectionTest.drop();

arrDocuments = [{"FirstName":"A",       "LastName":"Hancock"},
{"FirstName":"Ben",     "LastName":"Hughes"},
{"FirstName":"Bill",    "LastName":"Williams"},
{"FirstName":"Fred",    "LastName":"O'Hanley"},
{"FirstName":"Tim",     "LastName":"Langley"},
{"FirstName":"George",  "LastName":"Turner"},
{"FirstName":"Harry",   "LastName":"Rednapp"},
{"FirstName":"Random",  "LastName":"House"},
{"FirstName":"Ellie",   "LastName":"Golightly"},
{"FirstName":"Emily",   "LastName":"Langley"}];
db.CollectionTest.insert(arrDocuments);