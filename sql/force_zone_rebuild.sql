-- force zone rebuild
--
-- LONG_DESC:
-- This query will force a rebuild of all zones on all nameservers.
-- Normally rebuilds only happen if there is a change to the domain.
-- This is useful for bringing up a new server and populating all the zones.
--
-- USAGE:
-- No options available for this query, just run it
--
-- Your SQL statement would go below this line:

UPDATE `dns_server_domains` SET `rebuild_flag`='1' WHERE 1
