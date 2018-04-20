OpenNetAdmin - dpd's BIND-DLZ w/ v6 and INET function compatibility
============

ONA allowed for BIND-DLZ extension to perform dynamic lookups via your 
ONA MySQL database.  However, this didn't support PTR (very well) or V6 
and required a really convoluted view that made it very difficult to fix
the v6 et al issues that arrose.

So, I have design a very small delta patch, that should allow for v6 and 
and both v4 and v6 PTR, NS, MX and SOA records to be severed correctly, 
with a horrible long, but easier to use query.

The PHP code is changes, but two install scripts need to be run manually.

---
**Changes**

 * `install/inet-functions.sql`
   Very minimal schema changes, just adding one column to interfaces, for the VARBINARY(16), so the the inet6_atoi/inet6_ntoi and php's inet_pton/inet_ntop can be used.  Also adds three indexes for fields that DLZ uses to search by.
 * `install/inet-functions.php` base script copied from 2-to-3.php. This will do a few things 
   * Finds/Creates all domains and creates a meta "SOA" type row in the DNS table.  Because of how DLZ queries (without a "type", just the string of lookup) - it is much easier to have this via one table, and use cases in the sql to change up the data formating.   So a fake SOA record is here, pretty much just linking the domain ID and type together. All other fields are ignored.  Doesn't break backwards compat, ONA seems to ignore the SOA type.  Code changes provides the creation of SOAs upon domain creation.
   * Updates the dns.name of all PTR records.  Again, how Bind DLZ does queries, there was no good way to do reverse lookups.  ONA already ignored the dns.name field, for v4 PTRs, so, in this case overloading  dns.name with the last two octets.  Again, a strange things with BIND-DLZ ... is it will not lookup the first octet + in-arpa for the domain name ($zone$, ex 10.in-addr.arpa, but it will search 2.10.in-addr.arpa ) and DLZ will only successfully search the first octet of .ip6.arpa), with the $record$ slowly building out in reverse octets. 
   * add in all two-octet in-addr.arpa domains, as encounter by IP. Because of the above, will auto create all the needed in-addr.arpa for v4. Should also handle v6 as well.
   * Populate the ip_addr_inet field.  Will scan all interfaces, and update the IP address for both v4 and v6.  ip_addr_inet is not used by ONA, other than to updated durning edit or insert (code changes provided).

**Notable Code Changes**
   * Fixed undefined PHP warnings using isset() checks, especially running in CLI.  It was hard to see my own debugging output. By far, not extensive fixes, but proper PHP syntax/usage/style.
   * Allow AAAA to be AAAA.  Removed a few stops where v6 quad-a records are remapped to A. Was required for some reason.
   * random PHP 7.x fix. (functions_gui.inc.php) (Testing & developed using PHP 7.2.3 on FreeBSD, w/ file based sessions)
   * modules/ona/domain.inc.php - adds the SOA dns record.
   * modules/ona/interface.inc.php - populates and updates `ip_addr_inet` base on ip_addr & ip_mangle()
   * modules/ona/dns_record.inc.php - various tweaks for v6, as well as setting dns.name for PTR records.



---
**named.conf for BIND-DLZ**
The machine running bind-dlz, if you change /etc/resolv.conf to
point to localhost, you may want to use an IP address for the host.

And yes, this format and syntax below works with Bind 9.12.0 and FreeBSD 11.1-stable.

NOTE: Zone Transfers haven't be tested yet.

```
dlz "ONA Default" {
	database "mysql
	{host=a.b.c.d dbname=ona_ixsystems user=ona pass=xxx ssl=true}
	{select name as zone from domains where name = '$zone$' limit 1}
{select
 case
    when dns.ttl = 0
      then domains.default_ttl
    end as ttl,
  dns.type as type,
  CASE
    when lower(dns.type)='mx'
      then
        dns.mx_preference
      else ''
  end as mx_priority,
  case
    when lower(dns.type)='a' or lower(dns.type)='aaaa'
      then inet6_ntoa(interfaces.ip_addr_inet)
    when lower(dns.type) in ( 'ptr', 'cname')
        then (select concat(dns2.name, '.', domains.name, '.') from dns as dns2 inner join domains on domains.id = dns2.domain_id where dns.dns_id = dns2.id)
    when lower(dns.type)='txt'
      then concat('\"', dns.txt, '\"')
    when lower(dns.type)='srv'
      then concat('\"',
                  srv_pri ,' ', srv_weight,' ', srv_port, ' ',
                  concat ( dns.name, '.' , domains.name),
                  '\"')
  when lower(dns.type) in ('mx', 'ns')
      then (select concat(dns2.name, '.', domains.name, '.') from dns as dns2 inner join domains on domains.id = dns2.domain_id where dns.dns_id = dns2.id)
  else concat(dns.name, '.' , domains.name)
  end as data
  from interfaces, dns, domains
  where
    dns.name =  if ('$record$' like '@', '', '$record$')
    and domains.name = '$zone$'
    and dns.interface_id = interfaces.id
    and dns.domain_id = domains.id
    and upper(dns.type) not in ('SOA', 'NS')}
	{select
 case
    when dns.ttl = 0
      then domains.default_ttl
    end as ttl,
  dns.type as type,
  CASE
    when lower(dns.type)='mx'
      then
        dns.mx_preference
      else ''
  end as mx_priority,
  case
    when lower(dns.type) in ('mx', 'ns')
      then (select concat(dns2.name, '.', domains.name, '.') from dns as dns2 inner join domains on domains.id = dns2.domain_id where dns.dns_id = dns2.id)
    when lower(dns.type) in ('soa')
      then concat(
          domains.primary_master,  '. ',
          domains.admin_email, '. ',
          domains.serial,   ' ',
          domains.refresh,  ' ',
          domains.retry,  ' ',
          domains.expiry,  ' ',
          domains.minimum )
  else concat(dns.name, '.' , domains.name)
  end as data
  from interfaces, dns, domains
  where
    domains.name = '$zone$'
    and dns.interface_id = interfaces.id
    and dns.domain_id = domains.id
    and upper(dns.type) in ('SOA', 'NS') order by type DESC}
	{}
	{select name as zone from domains where name = '$zone$' and '$client$' like '10.%'}
	{}";
};
```