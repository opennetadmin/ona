-- List all hosts, including other attributes 
--
-- LONG_DESC: 
-- This query will return a list of FQDN names for all hosts in the database.
-- It includes the device type, IP, MAC, Subnet name, and location
--
-- USAGE:
-- No options available for this query, just run it
--
-- Your SQL statement would go below this line:

select concat(dns.name,".",z.name) fqdn,
       concat(m.name," ",models.name," (",roles.name,")") device_type,
       INET_NTOA(i.ip_addr) ip,
       i.mac_addr mac,
       s.name netname,
       l.reference location
from   interfaces i,
       dns,
       domains z,
       subnets s,
       manufacturers m,
       models,
       roles,
       device_types,
       hosts,
       devices
       left join locations l on (devices.location_id = l.id)
where  i.host_id = hosts.id
and    dns.domain_id = z.id
and    hosts.primary_dns_id = dns.id
and    s.id = i.subnet_id
and    devices.id = hosts.device_id
and    models.manufacturer_id = m.id
and    device_types.model_id = models.id
and    device_types.role_id = roles.id
and    devices.device_type_id = device_types.id
order by i.ip_addr
