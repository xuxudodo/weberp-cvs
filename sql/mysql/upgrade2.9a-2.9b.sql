use weberp;

BEGIN;
DROP TABLE WORequirements;
DROP TABLE WOIssues;
ALTER TABLE `WorksOrders` ADD `UnitsRecd` DOUBLE DEFAULT '0' NOT NULL AFTER `UnitsReqd` ;
ALTER TABLE `WWW_Users` CHANGE `Language` `Language` VARCHAR( 5 ) DEFAULT 'en_GB' NOT NULL;
COMMIT;