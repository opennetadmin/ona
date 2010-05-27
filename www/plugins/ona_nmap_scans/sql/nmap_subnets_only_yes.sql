-- Lists only Y flagged subnets with CIDR mask, use for nmap_ona_audit.php
--
-- LONG_DESC:
-- 	This query returns ONLY subnets from the database that have
--      the "nmap_scan" custom attribute set to "Y". The output will be in
--	network-ip/cidr notation.  It will NOT return subnets that
--	have the "nmap_scan" custom attribute set to "N".
--
--	The intent is that this list will be used as a seed for
--	doing nmap scans of only specified subnets in the database.
--      If you wish to find ANY subnet that is not flagged as "N"
--      then use the nmap_subnets.sql file instead.
--

select CONCAT(INET_NTOA(ip_addr),'/',32-log2((4294967296-ip_mask))) net
from subnets 
where id in (
        SELECT table_id_ref
        FROM custom_attributes
        WHERE table_name_ref = 'subnets'
        AND value = 'Y'
        AND custom_attribute_type_id = (
                SELECT id
                FROM custom_attribute_types
                WHERE name LIKE 'nmap_scan' )
  )
order by ip_addr

