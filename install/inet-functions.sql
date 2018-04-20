alter table interfaces add `ip_addr_inet` varbinary(16) default NULL;
CREATE INDEX domains_name_index ON domains (name);
CREATE INDEX dns_name_index ON dns (name);
CREATE INDEX type_index ON dns (type);
