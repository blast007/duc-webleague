ALTER TABLE wtagshoutbox
ADD COLUMN `published`	set('yes','no') NOT NULL default 'yes' AFTER `message`;

ALTER TABLE visits
ADD COLUMN `login_failed`	set('yes','no') NOT NULL default 'no';