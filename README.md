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

DEVELOPMENT
-----------
You can interact with either Docker or Vagrant. Docker is is the preferred
method at this time.

## Docker

Once you have cloned the repo you can issue
The Dockerfile is intended for development and testing purposes only. It is not recommended for production use.

First: Build an image with a specific version of Ubuntu and tag it as such.
```
docker build --build-arg UBUNTU_VERSION=23.04 -t ona-dev:23.04 .
```

Second: Start the container for general use. Point your browser to http://localhost/ona
```
docker run -p 80:80 -it ona-dev:23.04
```

OR

Start the container for development. Mount the current directory as a volume. This will allow you to edit the files on your host and have them be hosted in the container
```
docker run -p 80:80 -it -v $(pwd):/opt/ona ona-dev:23.04
```

This assumes you are in the directory you cloned the ONA repo into.
Also, if you have already installed this prior, you may need to remove `www/local/config/database_settings.conf.php` to get the install to run again.


## Vagrant

Simply clone this repo and issue a vagrant up to get a basic working system to develop with.
You will need to have git and [vagrant](https://vagrantup.com) installed on your system

   git clone https://github.com/opennetadmin/ona.git
   cd ona
   vagrant up


CONTACT
-------
  * http://opennetadmin.com/		-- Main website
  * http://opennetadmin.com/community	-- Contact information
  * https://github.com/opennetadmin/ona/wiki -- Online documentation
  * https://github.com/opennetadmin/ona/issues -- Discussion and issues

LICENSE
-------
OpenNetAdmin is currently released under the GPLv2.0 license. A copy of the
license is included in docs/LICENSE.

Some additional modules/plugins etc may be provided outside of the GPL
licese. These will be indicated as such. The core of ONA will however be GPL.
