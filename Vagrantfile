# -*- mode: ruby -*-
# vi: set ft=ruby :

# Allow user to pass in a different web port forward value.
# Use when you are running multiple vms on one system
PORTNUM = ENV["PORTNUM"] || "10000"

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/focal64"

  # Assign this VM to a bridged network, allowing you to connect directly to a
  # network using the host's network device. This makes the VM appear as another
  # physical device on your network.
  # Uncomment the following if you want this VM to have its own IP
  #config.vm.network :bridged

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:10000" will access port 80 on the guest machine.
  # NOTE: This will enable public access to the opened port
  config.vm.network "forwarded_port", guest: 80, host: PORTNUM , auto_correct: false

  # Set up our repo as a synced folder
  config.vm.synced_folder ".", "/opt/ona"

  # The basic install script
  $script = <<-SCRIPT
#------- Start provision --------
PORTNUM=$1

# Tell apt to quit its whining
export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y \
  curl \
  apache2 \
  mariadb-server \
  php \
  php-mysql \
  php-mbstring \
  php-gmp \
  php-pear \
  libapache2-mod-php \
  libyaml-dev \
  libio-socket-ssl-perl

# Link in our ona code
[ ! -d /var/www/html/ona ] && ln -fs /opt/ona/www /var/www/html/ona

# Allow others to see apache logs (and probably other stuff)
#usermod -a -G adm www-data
#usermod -a -G adm vagrant

# Set up application log file
if [ ! -f /var/log/ona.log ]
then
  touch /var/log/ona.log
  chmod 666 /var/log/ona.log
fi

# restart apache so it picks up changes
systemctl restart apache2.service

# Clean out any old configs
# Re-running the provisioner will wipe out any previous configs
rm -f /opt/ona/www/local/config/database_settings.inc.php

# Run the CLI installer with all default options
echo "\n\n\n"|php /opt/ona/install/installcli.php

echo "


Please point your browser to: http://localhost:${PORTNUM}/ona

"
#------- End provision --------
  SCRIPT

  # Run our shell provisioner
  config.vm.provision "shell", inline: $script, :args => [ PORTNUM ]

end
