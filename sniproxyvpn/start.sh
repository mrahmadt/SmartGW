#!/bin/sh


#copied from https://github.com/dperson/openvpn-client/blob/master/openvpn.sh
### vpnportforward: setup vpn port forwarding
# Arguments:
#   port) forwarded port
# Return: configured NAT rule
vpnportforward() { local port="$1" protocol="${2:-tcp}"
	ip6tables -t nat -A OUTPUT -p $protocol --dport $port -j DNAT \
		                    --to-destination ::11:$port 2>/dev/null
	iptables -t nat -A OUTPUT -p $protocol --dport $port -j DNAT \
			                --to-destination 127.0.0.11:$port
	echo "Setup forwarded port: $port $protocol"
}

### return_route: add a route back to your network, so that return traffic works
# Arguments:
#   network) a CIDR specified network range
# Return: configured return route
add_route6() { 
    local network="$1" gw="$(ip -6 route | awk '/default/{print $3}')"
    ip -6 route | grep -q "$network" ||  ip -6 route add to $network via $gw dev eth0
    ip6tables -A OUTPUT --destination $network -j ACCEPT 2>/dev/null
}

### return_route: add a route back to your network, so that return traffic works
# Arguments:
#   network) a CIDR specified network range
# Return: configured return route
add_route() { 
    local network="$1" gw="$(ip route |awk '/default/ {print $3}')"
    ip route | grep -q "$network" || ip route add to $network via $gw dev eth0
    iptables -A OUTPUT --destination $network -j ACCEPT
}


[[ -z "$VPN_USERNAME" ]] && { echo "nordvpn username is empty" ; exit 1; }
[[ -z "$VPN_PASSWORD" ]] && { echo "nordvpn password is empty" ; exit 1; }
[[ -z "$VPN_OPTIONS" ]] && { echo "openpyn options is empty" ; exit 1; }
[[ -z "$CIDR_NETWORK" ]] && { echo "my_network is empty CIDR network (IE 192.168.1.0/24) to allow replies once the VPN is up" ; exit 1; }

[[ "${CIDR6_NETWORK:-""}" ]] && add_route6 "$CIDR6_NETWORK"
[[ "${CIDR_NETWORK:-""}" ]] && add_route "$CIDR_NETWORK"



CONTAINER_ALREADY_STARTED="CONTAINER_ALREADY_STARTED"
if [ ! -e $CONTAINER_ALREADY_STARTED ]; then
    touch $CONTAINER_ALREADY_STARTED
    echo "-- First container startup --"
    /initser/setup_openpyn.sh "$VPN_USERNAME" "$VPN_PASSWORD"
else
    echo "-- Not first container startup --"
fi

exec /sbin/runsvdir -P /etc/service

# --skip-dns-patch Skips DNS patching, leaves /etc/resolv.conf untouched.
# -f, --force-fw-rules  Enforce firewall rules to drop traffic when tunnel breaks , force disable DNS traffic going to any other interface
# --allow INTERNALLY_ALLOWED [INTERNALLY_ALLOWED ...] To be used with "f" to allow ports but ONLY to INTERNAL IP RANGE. for example: you can use your PC as SSH, HTTP server for local devices (i.e. 192.168.1.* range) by "openpyn us --allow 22 80"
