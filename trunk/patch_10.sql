ALTER TABLE `seasons` CHANGE COLUMN `startdate` `startdate` datetime;
ALTER TABLE `seasons` CHANGE COLUMN `enddate` `enddate` datetime;
ALTER TABLE `seasons` ADD COLUMN `is_special` boolean default 0;
ALTER TABLE `seasons` ADD COLUMN `name` varchar(150) default null;
ALTER TABLE `seasons` ADD COLUMN `is_rewarded` boolean default 1;