-- List hosts with old last_response values
--
-- LONG_DESC: 
-- This query will return a list of FQDN names for all hosts that have IP addresses
-- that have a last_response check date OLDER than the date provided.  It will
-- not return information for IP addresses that do not have a last_response value 
-- set.
--
-- USAGE:
-- Pass a date to check for all hosts that have not responded since that date.
--
--	VARIABLES	DESC
--	1=YYYY-MM-DD	Return hosts older than date provided here
--
-- Your SQL statement would go below this line:

select concat(d.name,".",z.name) fqdn,
       INET_NTOA(i.ip_addr) ip,
       i.last_response
from   interfaces i,
       dns d,
       domains z,
       hosts h
where  i.last_response < ?
and    i.host_id = h.id
and    d.domain_id = z.id
and    h.primary_dns_id = d.id
order by i.last_response asc
