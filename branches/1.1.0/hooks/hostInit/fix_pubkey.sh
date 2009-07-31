#!/bin/bash

test -d /usr/local/aws/initscripts/first-startup || mkdir -p /usr/local/aws/initscripts/first-startup

cat << EOSCR > /usr/local/aws/initscripts/first-startup/get-public-key.sh
#!/bin/bash

# Wait for network initialization
perl -MIO::Socket::INET -e 'until(new IO::Socket::INET("169.254.169.254:80")){print"Waiting for network...\n";sleep 1}' | logger || echo

# Get root's authorized_keys file
mkdir -p -m 700 /root/.ssh
/usr/bin/curl -L -s http://169.254.169.254/2007-01-19/meta-data/public-keys/0/openssh-key > /root/.ssh/authorized_keys

# In case the http get failed.
if [ ! -s /root/.ssh/authorized_keys ] ; then
  cp /mnt/openssh_id.pub /root/.ssh/authorized_keys
elif [ ! -s /mnt/openssh_id.pub ] ; then
  cp /root/.ssh/authorized_keys /mnt/openssh_id.pub
fi

chmod 600 /root/.ssh/authorized_keys
EOSCR

chmod 755 /usr/local/aws/initscripts/first-startup/get-public-key.sh
