/* bz - new database 
league - old database */


UPDATE bz.players_profile pp
LEFT JOIN bz.players p ON (p.id = pp.playerid)
INNER JOIN league.l_player op ON (p.name = op.callsign)
LEFT JOIN league.bzl_countries oc ON (oc.numcode = op.country)
INNER JOIN bz.countries c ON (c.name = oc.name)
SET pp.location = c.id
WHERE pp.location = 1