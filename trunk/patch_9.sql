INSERT INTO players (external_playerid, teamid, name, last_teamid,status) VALUES ('',0,'autoreport',0,'active');
UPDATE players SET id = 0 WHERE  name LIKE 'autoreport';