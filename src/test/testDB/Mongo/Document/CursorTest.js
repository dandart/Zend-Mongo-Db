/**
 * @category   
 * @package    
 * @copyright  2010-12-21, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */
db = db.getSisterDB("testMongo");
db.CursorTest.drop();

var docA  = {"FirstName":"A",       "LastName":"Hancock"};
var docB  = {"FirstName":"Ben",     "LastName":"Hughes"};
var docC  = {"FirstName":"Bill",    "LastName":"Williams"};
var docD  = {"FirstName":"Fred",    "LastName":"O'Hanley"};
var docE  = {"FirstName":"Tim",     "LastName":"Langley"};
var docF  = {"FirstName":"George",  "LastName":"Turner"};
var docG  = {"FirstName":"Harry",   "LastName":"Rednapp"};
var docH  = {"FirstName":"Random",  "LastName":"House"};
var docI  = {"FirstName":"Ellie",   "LastName":"Golightly"};
var docJ  = {"FirstName":"Emily",   "LastName":"Langley"};

db.CursorTest.save(docA);
db.CursorTest.save(docB);
db.CursorTest.save(docC);
db.CursorTest.save(docD);
db.CursorTest.save(docE);
db.CursorTest.save(docF);
db.CursorTest.save(docG);
db.CursorTest.save(docH);
db.CursorTest.save(docI);
db.CursorTest.save(docJ);

