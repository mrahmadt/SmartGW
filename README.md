# SmartGW (**BETA**)

SmartGW is a way that allows you to redirect some of your home/office internet web traffic (based on the domain) through a VPN connection. For example, you can set up your youtube traffic to go over your VPN, and all other traffic (e.g., yahoo.com) goes through your local ISP.

## How Does it Work?
You will install a DNS Server, SNI Proxy and a VPN client in your network, The DNS server (based on the domain) forward your request to the SNI Proxy the traffic will go over your VPN connection.

## How difficult it's?
The setup is straightforward, you just need a Linux server in your network with a VPN subscription (below instructions support <a href="http://nordvpn.com">NordVPN</a> for now).

## Features
* Can work with all your devices in your local network (PC, Laptop, Mobiles, SmartTV...etc).
* No need to change any settings in your devices, install SmartGW and configure the DNS in your internet router.
* Simple GUI to check SmartGW status and to add/remove any domain.
* Automatically connect to least busy, low latency VPN servers in a given country.
* Auto retry and auto-failover to next best VPN server if the connection dies.

## What Do I need to start?
1. You must have a VPN subscription from NordVPN (The method below can work with any VPN provider, but you need to modify the installation script).
2. A Linux server with a static IP in your local network.


## Instructions
1. Install Linux (the instructions below tested with a fresh installation of Ubuntu Server 18.04).
2. Set a static IP for your server.
3. Update Ubuntu repository
``` bash
add-apt-repository main
add-apt-repository universe
apt update
```
4. Install SNIProxy from <a href="https://github.com/dlundquist/sniproxy">https://github.com/dlundquist/sniproxy</a> or using below using apt-get if you are using Ubuntu Server 18.04
``` bash
apt-get install sniproxy
```
5. Run below commands (as root) to configure sniproxy.
``` bash
curl https://raw.githubusercontent.com/mrahmadt/SmartGW/master/conf/sniproxy.conf -o /etc/sniproxy.conf
perl -pi -e 's/^ENABLED=0$/ENABLED=1/g' /etc/default/sniproxy
perl -pi -e 's/^#DAEMON_ARGS/DAEMON_ARGS/g' /etc/default/sniproxy
systemctl restart sniproxy
systemctl enable sniproxy
```
6. (Optional) Install squid if you would like to use it a a proxy in any device.
``` bash
apt-get install squid
perl -pi -e 's/^http_access allow localhost$/http_access allow localnet/g' /etc/squid/squid.conf
perl -pi -e 's/^#acl localnet src/acl localnet src/g' /etc/squid/squid.conf
echo "shutdown_lifetime 5 seconds" >> /etc/squid/squid.conf
echo 'dns_nameservers 8.8.8.8 8.8.4.4' >> /etc/squid/squid.conf
systemctl restart squid
systemctl enable squid
```
7. Install and configure DNSMasq.
``` bash
apt install dnsmasq
perl -pi -e 's/^#conf-dir=\/etc\/dnsmasq.d\/,\*.conf$/conf-dir=\/etc\/dnsmasq.d\/,\*.conf/g' /etc/dnsmasq.conf
echo '' > /etc/dnsmasq.d/smartgw.conf
DEFAULTIP=$(ip route| grep default| awk '{print $3}')
echo "address=/smartgw/${DEFAULTIP}" >> /etc/dnsmasq.d/smartgw.conf
echo "address=/nordvpn.com/${DEFAULTIP}" >> /etc/dnsmasq.d/smartgw.conf
sudo chown www-data:www-data  /etc/dnsmasq.d/smartgw.conf
systemctl restart dnsmasq
systemctl enable dnsmasq
```
8. Install and configure lighttpd with php
``` bash
apt-get install lighttpd php7.2-common php7.2-cgi php7.2-sqlite3
lighttpd-enable-mod fastcgi
lighttpd-enable-mod fastcgi-php
lighttpd-enable-mod rewrite              
perl -pi -e 's/^server.port\s+=\s+80$/server.port = 8081/g' /etc/lighttpd/lighttpd.conf
curl https://raw.githubusercontent.com/mrahmadt/SmartGW/master/conf/lighttpd.conf.debian -o /tmp/lighttpd.conf.debian
cat /tmp/lighttpd.conf.debian >> /etc/lighttpd/lighttpd.conf
curl https://raw.githubusercontent.com/mrahmadt/SmartGW/master/conf/redirect-index.html -o /var/www/html/index.html
systemctl stop lighttpd.service
systemctl start lighttpd.service
systemctl enable lighttpd.service
```
9. Setup sudo command for to control our setup from the web gui.
``` bash
apt install openvpn unzip wget python3-setuptools python3-pip
python3 -m pip install --upgrade openpyn
openpyn --init
openpyn de  -d
mkdir -p /etc/sudoers.d/
echo '' > /etc/sudoers.d/smartgw
echo 'www-data ALL=NOPASSWD: /usr/sbin/service' >> /etc/sudoers.d/smartgw
echo 'www-data ALL=NOPASSWD: /usr/local/bin/openpyn' >> /etc/sudoers.d/smartgw
chmod 0440 /etc/sudoers.d/smartgw
```
10. Install and configure <a href="https://github.com/jotyGill/openpyn-nordvpn">openpyn-nordvpn</a>
``` bash
apt install openvpn unzip wget python3-setuptools python3-pip
python3 -m pip install --upgrade openpyn
openpyn --init
openpyn de  -d
systemctl enable openpyn
```
11. Clone SmartGW and copy the content of web folder to /var/www/html/smartgw
``` bash
git clone https://github.com/mrahmadt/SmartGW.git
cd SmartGW/
mkdir -p /var/www/html/smartgw
cp -r web/* /var/www/html/smartgw
chown -R www-data:www-data /var/www/html/smartgw/
systemctl restart lighttpd.service
```
12. Open your Internet browser and type your server ip with port 8081 (http://Your-Server-IP:8081/ to start adding your domains
13. Open www.nordvpn.com and you should see your connection status "Protected".
14. Enjoy!.
