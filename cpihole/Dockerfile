FROM pihole/pihole:latest


RUN apt-get update && apt-get install -y \
inotify-tools \
&& mkdir -p /etc/dnsmasq.d \
&& touch /etc/dnsmasq.d/smartgw.conf \
&& mkdir -p /etc/services.d/inotifywait \
&& echo -e '#!/usr/bin/with-contenv bash' "\nwhile true\n\
do\n\
    inotifywait -e create -e modify  /etc/dnsmasq.d/smartgw.conf\n\
    pkill pihole-FTL\n\
done\n" > /inotifywait.sh \
&& chmod 755 /inotifywait.sh \
&& echo -e '#!/usr/bin/with-contenv bash' "\ns6-echo 'Starting inotifywait'\n/inotifywait.sh" > /etc/services.d/inotifywait/run \
&& echo -e '#!/usr/bin/with-contenv bash' "\ns6-echo 'Stopping inotifywait'\nkillall -9 inotifywait.sh inotifywait " > /etc/services.d/inotifywait/finish \
&& chmod 755 /etc/services.d/inotifywait/run \
&& chmod 755 /etc/services.d/inotifywait/finish
