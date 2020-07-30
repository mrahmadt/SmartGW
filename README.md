# SmartGW

SmartGW is a VPN Gateway/Proxy that allows you to route HTTP/HTTPS traffic for specific internet domains to go through VPN tunnel while keeping the other traffic goes through your ISP gateway and in the same time, use [Pi-Hole](https://pi-hole.net/) to perform Network-wide Ad Blocking (you have the choice to disable it)

**Example:**
- HTTP/HTTPS for **netflix.com** (from all devices in your network) --> Will go through your **VPN tunnel**.
- HTTP/HTTPS for **youtube.com** (from all devices in your network) --> Will go through your **ISP gateway**.
- HTTP/HTTPS for **google.com** (from all devices in your network) --> Will go through your **ISP gateway** and will block all Ads
- HTTP/HTTPS for **example.com** (from all devices in your network) --> Will go through your **VPN tunnel** and will block all Ads


**This can help you to:**
- Access Geo-restricted content.
- Access any blocked domains.
- Use it with all your devices (Laptop, Mobiles, SmartTV...etc).
- Utilize your full ISP network speed to access any site that you don't want it to go through the VPN tunnel.
- Work with [Pi-Hole](https://pi-hole.net/) for Network-wide Ad Blocking
- More browsing privacy?.

## Features
* Works with all devices in local network (PC, Laptop, Mobiles, SmartTV...etc).
* No need to change any settings in your devices, install SmartGW and configure the DNS in your internet router.
* Simple GUI to check SmartGW status and to add/remove any domain.
* Automatically connect to the fastest low latency VPN servers in a given country.
* Auto retry and auto-failover to next best VPN server if the connection dies.

## How difficult it's?
The setup is straightforward, you need a Linux server, and a [NordVPN](http://nordvpn.com) VPN subscription.

## What Do I need to start?
1. Linux server (any old PC or [single-board](https://en.wikipedia.org/wiki/Single-board_computer) such as [Raspberry Pi](https://www.raspberrypi.org) will work).
2. [NordVPN](http://nordvpn.com) VPN subscription.
3. Static IP in your local network.
4. [Docker engine](https://docker.com/) with basic know how.

## Instructions
1. Install Linux.
2. Set a static IP in your server.
3. Install Docker.
4. Download SmartGW source code.
5. Rename example.env to .env and change the variables

```

#Your NordVPN Username
VPN_USERNAME=yourUsername@nordvpn.com

#Your NordVPN Password
VPN_PASSWORD=yourNordvpnPassword

#VPN country
VPN_OPTIONS=us

#Local network CIDR network
CIDR_NETWORK=192.168.1.0/24

#No need to change this
VPN_DNS1=103.86.96.100

#No need to change this
VPN_DNS2=103.86.99.100

#Pi-Hole web admin password
PIHOLE_PASSWORD=pihole

#Your server default IP
SERVER_IP=192.168.1.100

```
6. Run SmartGW docker-compose

``` bash

docker-compose up -d

```

7. Open your browser and type your Pi-Hole IP (port 8081) (http://Your-Server-IP:8081/), login, and start adding your domains under "Local DNS Records" tab (IP should be your server IP).
8. Define SmartGW IP address as the only DNS entry in the router.

```

Login to your router’s configuration page and find the DHCP/DNS settings. 

Note: make sure you adjust this setting under your LAN settings and not the WAN.

```

![6e475c318358d8266052015e28841a72b3cc3b84](https://user-images.githubusercontent.com/957921/44320410-9cccc200-a44a-11e8-88fe-570d01eb2e93.png)

Enjoy!.

## Screenshots

![Pi-hole_Screenshot](https://user-images.githubusercontent.com/957921/88934822-beb35e00-d289-11ea-9486-69d61e473124.png)


![Capture](https://user-images.githubusercontent.com/957921/88934645-8e6bbf80-d289-11ea-98c6-b5d8b16a482d.PNG)

.
