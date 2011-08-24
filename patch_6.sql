ALTER TABLE news ADD COLUMN author_id int;
ALTER TABLE spawnlist ADD COLUMN author_id int;
ALTER TABLE bans ADD COLUMN author_id int;

UPDATE spawnlist SET author_id = (SELECT id FROM players where name = author);
UPDATE news SET author_id = (SELECT id FROM players where name = author);
UPDATE bans SET author_id = (SELECT id FROM players where name = author);