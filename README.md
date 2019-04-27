# SmartGW

SmartGW is a VPN Gateway/Proxy that allows you to route HTTP/HTTPS traffic for specific internet domains to go through VPN tunnel while keeping the other traffic goes through your ISP gateway.

**Example:**
- HTTP/HTTPS for **netflix.com** (from all devices in your network) --> Will go through your **VPN tunnel**.
- HTTP/HTTPS for **youtube.com** (from all devices in your network) --> Will go through your **ISP gateway**.

**This can help you to:**
- Access Geo-restricted content.
- Access any blocked domains.
- Use it with all your devices (Laptop, Mobiles, SmartTV...etc).
- Utilize your full ISP network speed to access any site that you don't want it to go through the VPN tunnel.
- More browsing privacy?.

## Features
* Works with all devices in local network (PC, Laptop, Mobiles, SmartTV...etc).
* No need to change any settings in your devices, install SmartGW and configure the DNS in your internet router.
* Simple GUI to check SmartGW status and to add/remove any domain.
* Automatically connect to the fastest low latency VPN servers in a given country.
* Auto retry and auto-failover to next best VPN server if the connection dies.

## How difficult it's?
The setup is straightforward, you need a Linux server, and a <a href="http://nordvpn.com">NordVPN</a> VPN subscription.

## What Do I need to start?
1. Linux server (any old laptop or <a href="https://en.wikipedia.org/wiki/Single-board_computer">single-board</a> such us <a href="https://www.raspberrypi.org/">Raspberry Pi</a> will work).
2. <a href="http://nordvpn.com">NordVPN</a> VPN subscription.
3. Static IP in your local network.
4. <a href="https://docker.com/">Docker engine</a> with basic know how.

## Instructions
1. Install Linux.
2. Set a static IP in your server.
3. Install Docker.
4. Download SmartGW source code.
3. Run SmartGW docker-compose
``` bash
docker-compose up
```
4. Open your browser and type your SmartGW IP (port 8080) (http://Your-Server-IP:8080/) and start adding your domains (e.g., yahoo.com).
5. Define SmartGW IP address as the only DNS entry in the router.
```
Login to your router’s configuration page and find the DHCP/DNS settings. 
Note: make sure you adjust this setting under your LAN settings and not the WAN.
```
![6e475c318358d8266052015e28841a72b3cc3b84](https://user-images.githubusercontent.com/957921/44320410-9cccc200-a44a-11e8-88fe-570d01eb2e93.png)

6. Enjoy!.

## Screenshots
![Screen Shot 2019-04-27 at 3 57 54 AM](https://user-images.githubusercontent.com/957921/56842702-fc645280-68a0-11e9-83df-0a9c4089a87e.png)
![Screen Shot 2019-04-27 at 3 58 13 AM](https://user-images.githubusercontent.com/957921/56842707-fff7d980-68a0-11e9-975b-1e81e1bc6133.png)
.
