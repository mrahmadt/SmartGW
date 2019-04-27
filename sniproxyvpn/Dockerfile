FROM alpine:edge
MAINTAINER Ahmad <ahmadt@gmail.com> https://github.com/mrahmadt/

EXPOSE 80
EXPOSE 443

ENV VPN_USERNAME="username"
ENV VPN_PASSWORD="password"
ENV VPN_OPTIONS="options"
ENV CIDR_NETWORK="192.168.1.0/24"
ENV VPN_DNS1="103.86.96.100"
ENV VPN_DNS2="103.86.99.100"


RUN apk --update upgrade &&\
    apk add runit &&\
    rm -rf /var/cache/apk/* &&\
    mkdir -p /etc/service &&\
    mkdir /initser

WORKDIR  /initser


#################
#### dnsmasq ####
#################
RUN apk add --update dnsmasq &&\
    rm -rf /var/cache/apk/* &&\
    echo -e "localise-queries\n\
no-resolv\n\
cache-size=10000\n\
local-ttl=2\n\
server=${VPN_DNS1}\n\
server=${VPN_DNS2}\n\
domain-needed\n\
bogus-priv\n\
local-service\n\
bind-interfaces\n\
user=root\n\
conf-dir=/etc/dnsmasq.d/,*.conf\n\
" > /etc/dnsmasq.conf && \
    mkdir /etc/service/dnsmasq &&\
    echo -e '#!/bin/sh' "\nexec dnsmasq -C /etc/dnsmasq.conf --keep-in-foreground" > /etc/service/dnsmasq/run &&\
    chmod 755 /etc/service/dnsmasq/run

COPY start.sh /initser
RUN chmod 755 /initser/start.sh


####################
##### SNIPROXY #####
####################
RUN apk add --update sniproxy && \
	rm -rf /var/cache/apk/* && \
    echo -e "user daemon\n\
pidfile /var/run/sniproxy.pid\n\
listen 80 {\n\
proto http\n\
}\n\
listen 443 {\n\
proto tls\n\
}\n\
table {\n\
.* *\n\
}\n\
#error_log {\n\
#filename /var/log/sniproxy-errors.log\n\
#priority debug\n\
#}\n\
#access_log {\n\
#filename /var/log/sniproxy-access.log\n\
#}\n\
resolver {\n\
nameserver 127.0.0.1\n\
mode ipv4_only\n\
}\n\
" > /etc/sniproxy/sniproxy.conf &&\
mkdir /etc/service/sniproxy &&\
touch /var/log/sniproxy-errors.log &&\
touch /var/log/sniproxy-access.log &&\
chmod 777 /var/log/sniproxy-errors.log &&\
chmod 777 /var/log/sniproxy-access.log &&\
echo -e '#!/bin/sh' "\nexec sniproxy -f -c /etc/sniproxy/sniproxy.conf" > /etc/service/sniproxy/run &&\
chmod 755 /etc/service/sniproxy/run

####################
##### PYTHON3 ######
####################
RUN set -x && apk add --no-cache python3 && \
    python3 -m ensurepip && \
    rm -r /usr/lib/python*/ensurepip && \
    pip3 install --upgrade pip setuptools && \
    if [ ! -e /usr/bin/pip ]; then ln -s pip3 /usr/bin/pip ; fi && \
    if [[ ! -e /usr/bin/python ]]; then ln -sf /usr/bin/python3 /usr/bin/python; fi && \
	rm -r /root/.cache


####################
##### openpyn ######
####################

RUN apk add --update openvpn unzip wget sudo iputils expect && \
    rm -rf /var/cache/apk/* && \
	python3 -m pip install --upgrade openpyn && \
    echo -e '#!/usr/bin/expect -f' > /initser/setup_openpyn.sh &&\
    echo -e "\n\n\
set username [lindex \$argv 0]\n\
set password [lindex \$argv 1]\n\
\n\
set timeout -1\n\
spawn openpyn --init\n\
match_max 100000\n\
expect \"*\"\n\
expect \"Enter your username\"\n" >> /initser/setup_openpyn.sh &&\
echo -e 'send -- "$username\\r"' "\n" >> /initser/setup_openpyn.sh &&\
echo -e 'expect "Enter the password"' "\n" >> /initser/setup_openpyn.sh &&\
echo -e 'send -- "$password\\r"' "\n" >> /initser/setup_openpyn.sh &&\
echo -e 'expect "*"' "\n" >> /initser/setup_openpyn.sh &&\
echo -e 'send -- "\\r"' "\n" >> /initser/setup_openpyn.sh &&\
echo -e "expect eof\n\
#OK\n\
"  >> /initser/setup_openpyn.sh &&\
    chmod 755 /initser/setup_openpyn.sh &&\
    mkdir /etc/service/openpyn &&\
    echo -e '#!/bin/sh' "\nexec openpyn \$VPN_OPTIONS" > /etc/service/openpyn/run &&\
    chmod 755 /etc/service/openpyn/run

# Final cleanup
RUN rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

CMD ["/initser/start.sh"]
