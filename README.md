OpenNetAdmin
============

OpenNetAdmin is an IPAM (IP Address Management) tool to track your
network attributes such as DNS names, IP addresses, Subnets, MAC addresses
just to name a few.  Through the use of plugins you can add extended it's
functionality.

---
**Recent Changes**

It's been too many years since the last official version! This update is to fix a few incompatibilities with newer PHP, MYSQL and OS versions. Fundamentally not much has changed.  For those interested I have been playing with a new [ona-core project](https://github.com/opennetadmin/ona-core) that is the beginnings of a true RESTful API interface. These changes should allow ONA to continue working on newer systems while the new core, and GUI is completed.

Thanks for your interest in OpenNetAdmin!

---

Each host or subnet can be tracked via a centralized AJAX enabled web interface
that can help reduce errors. A full [CLI interface](https://github.com/opennetadmin/dcm) is available 
as well to use for scripting and bulk work. We hope to provide a useful 
Network Management application for managing your IP subnets and hosts. 
Stop using spreadsheets to manage your network! Start doing proper IP 
address management!

![desktop image](https://github.com/opennetadmin/ona/wiki/images/desktop.png)

INSTALL
-------

Simply download and untar into `/opt/ona` or other directory of your choosing.  Then configure
your web server to serve out `/opt/ona/www`.  Open it in your web browser and run the install process.

Please refer to the [install page on the Github Wiki for more detail](https://github.com/opennetadmin/ona/wiki/Install)


CONTACT
-------
  * http://opennetadmin.com/		-- Main website
  * http://opennetadmin.com/community	-- Contact information
  * https://github.com/opennetadmin/ona/wiki -- Online documentation
  * http://opennetadmin.com/forum	-- User discussion group

LICENSE
-------
OpenNetAdmin is currently released under the GPLv2.0 license. A copy of the
license is included in docs/LICENSE.

Some additional modules/plugins etc may be provided outside of the GPL
licese. These will be indicated as such. The core of ONA will however be GPL.
