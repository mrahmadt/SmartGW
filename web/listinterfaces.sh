#!/bin/bash

availableInterfaces=$(ip --oneline link show up| grep -v 'lo' | grep -v 'tun' | awk '{print $2}' | cut -d':' -f1 | cut -d'@' -f1)
for interface in $availableInterfaces
do
myip=$(ip addr show dev ${interface}| grep 'inet ' | awk '{print $2}' | cut -d'/' -f1)
echo ${interface}','${myip}
done