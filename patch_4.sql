ALTER TABLE servertracker
ADD COLUMN `description` tinytext NULL default '' AFTER `serveraddress`;

ALTER TABLE servertracker
ADD COLUMN `type` set('match','replay','public') NOT NULL default 'match' AFTER `description`;
