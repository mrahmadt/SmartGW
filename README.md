# SmartGW

SmartGW is a VPN Gateway/Proxy that allows you to redirect specific internet domains to go through a VPN tunnel while keeping other internet domains to go through your ISP gateway.

**Example:**
- Your request to netflix.com (from all devices in your network) --> Traffic to netflix.com will go through the VPN tunnel.
- Your request to youtube.com (from all devices in your network) --> Traffic to youtube.com will go through your ISP.

**This can help you to:**
- Access Geo-restricted content.
- Access any blocked domains.
- Use it with all your devices (Laptop, Mobiles, SmartTV...etc).
- Utilize your full ISP network speed to access any site that you don't want it to go through the VPN tunnel.
- More browsing privacy?.

## Features
* Can work with all your devices in your local network (PC, Laptop, Mobiles, SmartTV...etc).
* No need to change any settings in your devices, install SmartGW and configure the DNS in your internet router.
* Simple GUI to check SmartGW status and to add/remove any domain.
* Can be configured as http proxy to redirect all data to go through the VPN tunnel.
* Automatically connect to least busy, low latency VPN servers in a given country.
* Auto retry and auto-failover to next best VPN server if the connection dies.

## How difficult it's?
The setup is straightforward, you just need a Linux server in your network with a VPN subscription (below instructions support <a href="http://nordvpn.com">NordVPN</a> for now).

## What Do I need to start?
1. You must have a VPN subscription from NordVPN (The method below can work with any VPN provider, but you need to modify the installation script).
2. A Linux server with a static IP in your local network.


## Instructions
1. Install Linux (the instructions below tested with a fresh installation of Ubuntu Server 18.04).
2. Set a static IP in your server.
3. Update Ubuntu repository
``` bash
add-apt-repository main
add-apt-repository universe
apt update
```
4. Install SNIProxy from <a href="https://github.com/dlundquist/sniproxy">https://github.com/dlundquist/sniproxy</a> or using apt-get (Works with Ubuntu Server 18.04)
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
systemctl stop lighttpd.service
systemctl start lighttpd.service
systemctl enable lighttpd.service
```
9. Clone SmartGW and copy the content of web folder
``` bash
git clone https://github.com/mrahmadt/SmartGW.git
cd SmartGW/
mkdir -p /var/www/html/smartgw
cp -r web/* /var/www/html/smartgw
cat conf/lighttpd.conf.debian >> /etc/lighttpd/lighttpd.conf
cp conf/redirect-index.html /var/www/html/index.html
chown -R www-data:www-data /var/www/html/smartgw/
systemctl restart lighttpd.service
```
10. Setup sudo command for to control our setup from the web gui.
``` bash
mkdir -p /etc/sudoers.d/
echo '' > /etc/sudoers.d/smartgw
echo 'www-data ALL=NOPASSWD: /usr/sbin/service' >> /etc/sudoers.d/smartgw
echo 'www-data ALL=NOPASSWD: /usr/local/bin/openpyn' >> /etc/sudoers.d/smartgw
chmod 0440 /etc/sudoers.d/smartgw
```
11. Install and configure <a href="https://github.com/jotyGill/openpyn-nordvpn">openpyn-nordvpn</a>
``` bash
apt install openvpn unzip wget python3-setuptools python3-pip
python3 -m pip install --upgrade openpyn
openpyn --init
openpyn de  -d
systemctl enable openpyn
```
12. Open your browser and type your SmartGW ip (port 8081) (http://Your-Server-IP:8081/.
13. Define SmartGW IP address as the only DNS entry in the router.
```
Log into your router’s configuration page and find the DHCP/DNS settings. Note: make sure you adjust this setting under your LAN settings and not the WAN.
```
![6e475c318358d8266052015e28841a72b3cc3b84](https://user-images.githubusercontent.com/957921/44320410-9cccc200-a44a-11e8-88fe-570d01eb2e93.png)

14. Enjoy!.

## Screenshots
![screenshot1](https://user-images.githubusercontent.com/957921/44305203-cfa78500-a37a-11e8-961c-cddea95773d2.png)
![screenshot2](https://user-images.githubusercontent.com/957921/44305204-d2a27580-a37a-11e8-881e-120f065df056.png)
![screenshot3](https://user-images.githubusercontent.com/957921/44305205-d46c3900-a37a-11e8-9c87-01e1b24ec4f1.png)
