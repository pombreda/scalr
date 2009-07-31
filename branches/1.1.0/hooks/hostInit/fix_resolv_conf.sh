#!/bin/bash

[ -f /etc/resolv.conf ] && chmod 644 /etc/resolv.conf

if [ ! -f /etc/dhcp3/dhclient-exit-hooks.d/resolv ]; then
        cat << EOSCR > /etc/dhcp3/dhclient-exit-hooks.d/resolv
#!/bin/bash

[ -f /etc/resolv.conf ] && chmod 644 /etc/resolv.conf
EOSCR
fi
