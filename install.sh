#!/bin/bash

# shellcheck disable=SC1090

# SmartGW: Domain based VPN Gateway/Proxy for all devices
# (c) 2018-2018 SmartGW (https://github.com/mrahmadt/SmartGW)
#  Domain based VPN Gateway/Proxy for all devices .
#
# Installs and Updates SmartGW
#
# This installation script inspired from http://pi-hole.net/
#
# This file is copyright under the latest version of the EUPL.
# Please see LICENSE file for your rights under this license.
# Install with this command (from your Linux machine):
#
# curl -sSL https://raw.githubusercontent.com/mrahmadt/SmartGW/master/install.sh | bash
# http://bit.ly/Install-SmartGW

# -e option instructs bash to immediately exit if any command [1] has a non-zero exit status
# We do not want users to end up with a partially working install, so we exit the script
# instead of continuing the installation with something broken
set -e

######## VARIABLES #########

INSTALL_WEB_SERVER=true
INSTALL_SQUID=true
INSTALL_DNSMASQ=true
INSTALL_SNIPROXY=true
INSTALL_OPENPYN=true


DEFAULT_INTERFACE=$(ip route| grep default| awk '{print $5}')
DEFAULT_IP=$(ip addr show dev  $DEFAULT_INTERFACE | grep 'inet ' | awk '{print $2}' | cut -d'/' -f1)

BUILD_DIR="/root/smartgw-build"

TIMENOW=$(date '+%d%m%Y%H%M%S')

# Check arguments for the undocumented flags
for var in "$@"; do
    case "$var" in
        "--disable-install-webserver" ) INSTALL_WEB_SERVER=false;;
		"--disable-install-SQUID" ) INSTALL_SQUID=false;;
		"--disable-install-DNSMASQ" ) INSTALL_DNSMASQ=false;;
		"--disable-install-SNIPROXY" ) INSTALL_SNIPROXY=false;;
		"--disable-install-OPENPYN" ) INSTALL_OPENPYN=false;;
    esac
done



# Compatibility
distro_check() {
# If apt-get is installed, then we know it's part of the Debian family
if command -v apt-get &> /dev/null; then
	DISTRO="Debian"
	DISTRO_Type="Debian"
    # Set some global variables here
    # We don't set them earlier since the family might be Red Hat, so these values would be different
    PKG_MANAGER="apt-get"
    # A variable to store the command used to update the package cache
    UPDATE_PKG_CACHE="${PKG_MANAGER} update"
    # An array for something...
    PKG_INSTALL=(${PKG_MANAGER} --yes --no-install-recommends install)
    # grep -c will return 1 retVal on 0 matches, block this throwing the set -e with an OR TRUE
    PKG_COUNT="${PKG_MANAGER} -s -o Debug::NoLocking=true upgrade | grep -c ^Inst || true"
	# Some distros vary slightly so these fixes for dependencies may apply
	# Debian 7 doesn't have iproute2 so if the dry run install is successful,
	if ${PKG_MANAGER} install --dry-run iproute2 > /dev/null 2>&1; then
	    # we can install it
	    iproute_pkg="iproute2"
	# Otherwise,
	else
	    # use iproute
	    iproute_pkg="iproute"
	fi


    # Check for and determine version number (major and minor) of current php install
    if command -v php &> /dev/null; then
        phpInsVersion="$(php -v | head -n1 | grep -Po '(?<=PHP )[^ ]+')"
        echo -e "  ${INFO} Existing PHP installation detected : PHP version $phpInsVersion"
        phpInsMajor="$(echo "$phpInsVersion" | cut -d\. -f1)"
        phpInsMinor="$(echo "$phpInsVersion" | cut -d\. -f2)"
        # Is installed php version 7.0 or greater
        if [ "$(echo "$phpInsMajor.$phpInsMinor < 7.0" | bc )" == 0 ]; then
            phpInsNewer=true
        fi
    fi
    # Check if installed php is v 7.0, or newer to determine packages to install
    if [[ "$phpInsNewer" != true ]]; then
        # Prefer the php metapackage if it's there
        if ${PKG_MANAGER} install --dry-run php > /dev/null 2>&1; then
            phpVer="php"
        # fall back on the php5 packages
        else
            phpVer="php5"
        fi
    else
        # Newer php is installed, its common, cgi & sqlite counterparts are deps
        phpVer="php$phpInsMajor.$phpInsMinor"
    fi
    # We also need the correct version for `php-sqlite` (which differs across distros)
    if ${PKG_MANAGER} install --dry-run ${phpVer}-sqlite3 > /dev/null 2>&1; then
        phpSqlite="sqlite3"
    else
        phpSqlite="sqlite"
    fi

    INSTALLER_DEPS=(apt-utils git ${iproute_pkg})
    SMARTGW_DEPS=(curl dnsutils iputils-ping lsof sudo unzip wget idn2 sqlite3 dns-root-data resolvconf)
	SQUID_DEPS=(squid)
	DNSMASQ_DEPS=(dnsmasq)
	SNIPROXY_DEPS=(autotools-dev cdbs debhelper dh-autoreconf dpkg-dev gettext libev-dev libpcre3-dev libudns-dev pkg-config fakeroot devscripts build-essential)
	OPENPYN_DEPS=(openvpn unzip wget python3-setuptools python3-pip)
    SMARTGW_WEB_DEPS=(lighttpd ${phpVer}-common ${phpVer}-cgi ${phpVer}-${phpSqlite})
    
	# The Web server user,
    LIGHTTPD_USER="www-data"
    # group,
    LIGHTTPD_GROUP="www-data"
    # and config file
    LIGHTTPD_CFG="lighttpd.conf.debian"

    # A function to check...
    test_dpkg_lock() {
        # An iterator used for counting loop iterations
        i=0
        # fuser is a program to show which processes use the named files, sockets, or filesystems
        # So while the command is true
        while fuser /var/lib/dpkg/lock >/dev/null 2>&1 ; do
            # Wait half a second
            sleep 0.5
            # and increase the iterator
            ((i=i+1))
        done
        # Always return success, since we only return if there is no
        # lock (anymore)
        return 0
    }

# If apt-get is not found, check for rpm to see if it's a Red Hat family OS
elif command -v rpm &> /dev/null; then
	DISTRO="Redhat"
	
    # Then check if dnf or yum is the package manager
    if command -v dnf &> /dev/null; then
        PKG_MANAGER="dnf"
    else
        PKG_MANAGER="yum"
    fi

    # Fedora and family update cache on every PKG_INSTALL call, no need for a separate update.
    UPDATE_PKG_CACHE=":"
    PKG_INSTALL=(${PKG_MANAGER} install -y)
    PKG_COUNT="${PKG_MANAGER} check-update | egrep '(.i686|.x86|.noarch|.arm|.src)' | wc -l"
		
    INSTALLER_DEPS=(git iproute net-tools newt procps-ng which)
    SMARTGW_DEPS=(bind-utils curl findutils nmap-ncat sudo unzip wget libidn2 psmisc)
    SMARTGW_WEB_DEPS=(lighttpd lighttpd-fastcgi php-common php-cli php-pdo)
	SQUID_DEPS=(squid)
	DNSMASQ_DEPS=(dnsmasq)
	SNIPROXY_DEPS=(autoconf automake curl gettext-devel libev-devel pcre-devel perl pkgconfig rpm-build udns-devel make automake gcc gcc-c++)
	OPENPYN_DEPS=(openvpn unzip wget python3-setuptools python3-pip)
    LIGHTTPD_USER="lighttpd"
    LIGHTTPD_GROUP="lighttpd"
    LIGHTTPD_CFG="lighttpd.conf.fedora"
	
    # If the host OS is Fedora,
    if grep -qi 'fedora' /etc/redhat-release; then
		DISTRO_Type="Fedora"
        # all required packages should be available by default with the latest fedora release
        # ensure 'php-json' is installed on Fedora (installed as dependency on CentOS7 + Remi repository)
        SMARTGW_WEB_DEPS+=('php-json')
		OPENPYN_DEPS+=('python3-setuptools python-pip')
    # or if host OS is CentOS,
    elif grep -qi 'centos' /etc/redhat-release; then
		DISTRO_Type="Centos"
		OPENPYN_DEPS+=('python34-setuptools python34-pip')
        #CentOS 7+ with PHP7+
        SUPPORTED_CENTOS_VERSION=7
        SUPPORTED_CENTOS_PHP_VERSION=7
        # Check current CentOS major release version
        CURRENT_CENTOS_VERSION=$(rpm -q --queryformat '%{VERSION}' centos-release)
        # Check if CentOS version is supported
        if [[ $CURRENT_CENTOS_VERSION -lt $SUPPORTED_CENTOS_VERSION ]]; then
            echo -e "CentOS is not suported."
            echo -e "Please update to CentOS release $SUPPORTED_CENTOS_VERSION or later"
            # exit the installer
            exit
        fi
        # on CentOS we need to add the EPEL repository to gain access to Fedora packages
        EPEL_PKG="epel-release"
        rpm -q ${EPEL_PKG} &> /dev/null || rc=$?
        if [[ $rc -ne 0 ]]; then
            echo -e "Enabling EPEL package repository (https://fedoraproject.org/wiki/EPEL)"
            "${PKG_INSTALL[@]}" ${EPEL_PKG} &> /dev/null
            echo -e "Installed"
        fi

        # The default php on CentOS 7.x is 5.4 which is EOL
        # Check if the version of PHP available via installed repositories is >= to PHP 7
        AVAILABLE_PHP_VERSION=$(${PKG_MANAGER} info php | grep -i version | grep -o '[0-9]\+' | head -1)
        if [[ $AVAILABLE_PHP_VERSION -ge $SUPPORTED_CENTOS_PHP_VERSION ]]; then
            # Since PHP 7 is available by default, install via default PHP package names
            : # do nothing as PHP is current
        else
            REMI_PKG="remi-release"
            REMI_REPO="remi-php72"
            rpm -q ${REMI_PKG} &> /dev/null || rc=$?
        if [[ $rc -ne 0 ]]; then
            echo -e "Enabling Remi's RPM repository (https://rpms.remirepo.net)"
            "${PKG_INSTALL[@]}" "https://rpms.remirepo.net/enterprise/${REMI_PKG}-$(rpm -E '%{rhel}').rpm" &> /dev/null
            # enable the PHP 7 repository via yum-config-manager (provided by yum-utils)
            "${PKG_INSTALL[@]}" "yum-utils" &> /dev/null
            yum-config-manager --enable ${REMI_REPO} &> /dev/null
            echo -e "Remi's RPM repository has been enabled for PHP7"
            # trigger an install/update of PHP to ensure previous version of PHP is updated from REMI
            if "${PKG_INSTALL[@]}" "php-cli" &> /dev/null; then
                echo -e "PHP7 installed/updated via Remi's RPM repository"
            else
                echo -e "There was a problem updating to PHP7 via Remi's RPM repository"
                exit 1
            fi
            
        fi
    fi
    else
        # If not a supported version of Fedora or CentOS,
        echo -e "Unsupported RPM based distribution"
        # exit the installer
        exit
    fi

# If neither apt-get or rmp/dnf are found
else
    # it's not an OS we can support,
    echo -e "OS distribution not supported"
    # so exit the installer
    exit
fi
}

update_package_cache() {
    # Running apt-get update/upgrade with minimal output can cause some issues with
    # requiring user input (e.g password for phpmyadmin see #218)

    # Update package cache on apt based OSes. Do this every time since
    # it's quick and packages can be updated at any time.

    # Local, named variables
    local str="Update local cache of available packages"
    echo ""
    echo -ne "${str}..."
    # Create a command from the package cache variable
    if eval "${UPDATE_PKG_CACHE}" &> /dev/null; then
        echo -e "${str}"
    # Otherwise,
    else
        # show an error and exit
        echo -e "${str}"
        echo -ne "Error: Unable to update package cache. Please try \"${UPDATE_PKG_CACHE}\""
        return 1
    fi
}

# Let user know if they have outdated packages on their system and
# advise them to run a package update at soonest possible.
notify_package_updates_available() {
    # Local, named variables
    local str="Checking ${PKG_MANAGER} for upgraded packages"
    echo -ne "\\n${str}..."
    # Store the list of packages in a variable
    updatesToInstall=$(eval "${PKG_COUNT}")

    if [[ -d "/lib/modules/$(uname -r)" ]]; then
        if [[ "${updatesToInstall}" -eq 0 ]]; then
            echo -e "${str}... up to date!"
            echo ""
        else
            echo -e "${str}... ${updatesToInstall} updates available"
            echo -e "It is recommended to update your OS after installing the SmartGW!"
            echo ""
        fi
    else
        echo -e "${str}
        Kernel update detected. If the install fails, please reboot and try again\\n"
    fi
}

install_dependent_packages() {
    # Local, named variables should be used here, especially for an iterator
    # Add one to the counter
    counter=$((counter+1))
    # If it equals 1,
    if [[ "${counter}" == 1 ]]; then
        #
        echo -e "Installer Dependency checks..."
    else
        #
        echo -e "Main Dependency checks..."
    fi

    # Install packages passed in via argument array
    # No spinner - conflicts with set -e
    declare -a argArray1=("${!1}")
    declare -a installArray

    # Debian based package install - debconf will download the entire package list
    # so we just create an array of packages not currently installed to cut down on the
    # amount of download traffic.
    # NOTE: We may be able to use this installArray in the future to create a list of package that were
    # installed by us, and remove only the installed packages, and not the entire list.
    if command -v debconf-apt-progress &> /dev/null; then
        # For each package,
        for i in "${argArray1[@]}"; do
            echo -ne "Checking for $i..."
            if dpkg-query -W -f='${Status}' "${i}" 2>/dev/null | grep "ok installed" &> /dev/null; then
                echo -e "Checking for $i"
            else
                echo -e "Checking for $i (will be installed)"
                installArray+=("${i}")
            fi
        done
        if [[ "${#installArray[@]}" -gt 0 ]]; then
            test_dpkg_lock
            #debconf-apt-progress -- "${PKG_INSTALL[@]}" "${installArray[@]}"
			"${PKG_INSTALL[@]}" "${installArray[@]}"
            return
        fi
        echo ""
        return 0
    fi

    # Install Fedora/CentOS packages
    for i in "${argArray1[@]}"; do
        echo -ne "Checking for $i..."
        if ${PKG_MANAGER} -q list installed "${i}" &> /dev/null; then
            echo -e "Checking for $i"
        else
            echo -e "Checking for $i (will be installed)"
            installArray+=("${i}")
        fi
    done
    if [[ "${#installArray[@]}" -gt 0 ]]; then
        "${PKG_INSTALL[@]}" "${installArray[@]}" &> /dev/null
        return
    fi
    echo ""
    return 0
}
# SELinux
checkSelinux() {
    # If the getenforce command exists,
    if command -v getenforce &> /dev/null; then
        # Store the current mode in a variable
        enforceMode=$(getenforce)
        echo -e "\\nSELinux mode detected: ${enforceMode}"
        # If it's enforcing,
        if [[ "${enforceMode}" == "Enforcing" ]]; then
			echo "SELinux is being ENFORCED on your system! \\n\\nSmartGW currently does not support SELinux.\\nexiting installer"
			exit 1

        fi
    fi
}


stop_service() {
    # Stop service passed in as argument.
    # Can softfail, as process may not be installed when this is called
    local str="Stopping ${1} service"
    echo -ne "${str}..."
    if command -v systemctl &> /dev/null; then
        systemctl stop "${1}" &> /dev/null || true
    else
        service "${1}" stop &> /dev/null || true
    fi
    echo -e "${str}..."
}

# Start/Restart service passed in as argument
start_service() {
    # Local, named variables
    local str="Starting ${1} service"
    echo -ne " ${str}..."
    # If systemctl exists,
    if command -v systemctl &> /dev/null; then
        # use that to restart the service
        systemctl restart "${1}" &> /dev/null
    # Otherwise,
    else
        # fall back to the service command
        service "${1}" restart &> /dev/null
    fi
    echo -e "${str}"
}

# Enable service so that it will start with next reboot
enable_service() {
    # Local, named variables
    local str="Enabling ${1} service to start on reboot"
    echo -ne "${str}..."
    # If systemctl exists,
    if command -v systemctl &> /dev/null; then
        # use that to enable the service
        systemctl enable "${1}" &> /dev/null
    # Otherwise,
    else
        # use update-rc.d to accomplish this
        update-rc.d "${1}" defaults &> /dev/null
    fi
    echo -e "${str}"
}

# Disable service so that it will not with next reboot
disable_service() {
    # Local, named variables
    local str="Disabling ${1} service"
    echo -ne "${str}..."
    # If systemctl exists,
    if command -v systemctl &> /dev/null; then
        # use that to disable the service
        systemctl disable "${1}" &> /dev/null
    # Otherwise,
    else
        # use update-rc.d to accomplish this
        update-rc.d "${1}" disable &> /dev/null
    fi
    echo -e "${str}"
}

check_service_active() {
    # If systemctl exists,
    if command -v systemctl &> /dev/null; then
        # use that to check the status of the service
        systemctl is-enabled "${1}" &> /dev/null
    # Otherwise,
    else
        # fall back to service command
        service "${1}" status &> /dev/null
    fi
}

install_squid() {
	if [[ "${INSTALL_SQUID}" == true ]]; then
		echo -e "Installing Squid"
		perl -pi -e 's/^http_access allow localhost$/http_access allow localnet/g' /etc/squid/squid.conf
		perl -pi -e 's/^#acl localnet src/acl localnet src/g' /etc/squid/squid.conf
		
		if grep -q "shutdown_lifetime 2 seconds" /etc/squid/squid.conf; then
			echo ''
	  	else
			echo 'shutdown_lifetime 2 seconds' >> /etc/squid/squid.conf
		fi
		
		if grep -q smartgw.conf "/etc/squid/squid.conf"; then
			echo ''
	  	else
			echo 'include /etc/squid/smartgw.conf' >> /etc/squid/squid.conf
		fi
		
		echo '' > /etc/squid/smartgw.conf
		
		chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /etc/squid/smartgw.conf
		chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /etc/squid/squid.conf
		
		stop_service squid
        start_service squid
        enable_service squid
	fi
}

install_sniproxy() {
	if [[ "${INSTALL_SNIPROXY}" == true ]]; then
		echo -e "Clone SNIProxy git repository"
		cd "${BUILD_DIR}"
		git clone -q --depth 1 https://github.com/dlundquist/sniproxy &> /dev/null
		echo -e "Building and Installing SNIProxy"
		if [[ "${DISTRO}" == "Debian" ]]; then
			cd sniproxy
			./autogen.sh &>/dev/null
			dpkg-buildpackage &>/dev/null
			dpkg -i ../sniproxy_*_*.deb
		elif [[ "${DISTRO}" == "Redhat" ]]; then
			cd sniproxy
			./autogen.sh &>/dev/null
			./configure &>/dev/null
			make dist &>/dev/null
			rpmbuild --define "_sourcedir `pwd`" -ba redhat/sniproxy.spec
			yum install ../sniproxy-*.*.rpm
		fi
		touch /var/log/sniproxy-access.log
		chown daemon:daemon /var/log/sniproxy-access.log
		cp /etc/sniproxy.conf /etc/sniproxy.conf.${TIMENOW}
		cp "${BUILD_DIR}"/SmartGW/conf/sniproxy.conf /etc/sniproxy.conf
		chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /etc/sniproxy.conf
		perl -pi -e 's/^ENABLED=0$/ENABLED=1/g' /etc/default/sniproxy
		perl -pi -e 's/^#DAEMON_ARGS/DAEMON_ARGS/g' /etc/default/sniproxy
		stop_service sniproxy
        start_service sniproxy
        enable_service sniproxy
	fi
}
# Systemd-resolved's DNSStubListener and dnsmasq can't share port 53.
disable_resolved_stublistener() {
    echo -en "  Testing if systemd-resolved is enabled"
    # Check if Systemd-resolved's DNSStubListener is enabled and active on port 53
    if check_service_active "systemd-resolved"; then
        # Check if DNSStubListener is enabled
        echo -en "Testing if systemd-resolved DNSStub-Listener is active"
        if ( grep -E '#?DNSStubListener=yes' /etc/systemd/resolved.conf &> /dev/null ); then
            # Disable the DNSStubListener to unbind it from port 53
            # Note that this breaks dns functionality on host until dnsmasq/ftl are up and running
            echo -en "Disabling systemd-resolved DNSStubListener"
            # Make a backup of the original /etc/systemd/resolved.conf
            # (This will need to be restored on uninstallation)
            sed -r -i.orig 's/#?DNSStubListener=yes/DNSStubListener=no/g' /etc/systemd/resolved.conf
            echo -e " and restarting systemd-resolved"
            systemctl reload-or-restart systemd-resolved
        else
            echo -e "Systemd-resolved does not need to be restarted"
        fi
    else
        echo -e "Systemd-resolved is not enabled"
    fi
}

install_dnsmasq() {
	if [[ "${INSTALL_DNSMASQ}" == true ]]; then
		echo -e "Installing DNSMasq"
		disable_resolved_stublistener
		
		perl -pi -e 's/^#conf-dir=\/etc\/dnsmasq.d\/,\*.conf$/conf-dir=\/etc\/dnsmasq.d\/,\*.conf/g' /etc/dnsmasq.conf

	    if [[ ! -d "/etc/dnsmasq.d"  ]];then
	        mkdir "/etc/dnsmasq.d"
	    fi
		
		#if [[ -f "/etc/dnsmasq.d/smartgw.conf" ]]; then
		#	cp /etc/dnsmasq.d/smartgw.conf /etc/dnsmasq.d/smartgw.conf.${TIMENOW}
		#fi
		#if [[ -f "/etc/dnsmasq.d/smartgw-global.conf" ]]; then
		#	cp /etc/dnsmasq.d/smartgw-global.conf /etc/dnsmasq.d/smartgw-global.conf.${TIMENOW}
		#fi
		
		echo '' > /etc/dnsmasq.d/smartgw.conf
		echo '' > /etc/dnsmasq.d/smartgw-global.conf
		
		chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP}  /etc/dnsmasq.d/smartgw.conf
		chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP}  /etc/dnsmasq.d/smartgw-global.conf
		
		#PI HOLE?
		if [[ ! -f /etc/dnsmasq.d/01-pihole.conf ]]; then
			echo 'server=103.86.96.100' >> /etc/dnsmasq.d/smartgw-global.conf
			echo 'server=103.86.99.100' >> /etc/dnsmasq.d/smartgw-global.conf
			stop_service dnsmasq
	        start_service dnsmasq
	        enable_service dnsmasq
		fi		
	fi
}
install_lighttpd() {
    if [[ "${INSTALL_WEB_SERVER}" == true ]]; then
		echo -e "Installing Lighttpd"
        # and if the Web server conf directory does not exist,
        #if [[ ! -d "/etc/lighttpd" ]]; then
        #    mkdir /etc/lighttpd
		#	echo '' > /etc/lighttpd/lighttpd.conf
        #elif [[ -f "/etc/lighttpd/lighttpd.conf" ]]; then
        #    cp /etc/lighttpd/lighttpd.conf /etc/lighttpd/lighttpd.conf.${TIMENOW}
        #fi
				
        # Make the directories if they do not exist and set the owners
        mkdir -p /var/run/lighttpd
        chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /var/run/lighttpd
        mkdir -p /var/cache/lighttpd/compress
        chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /var/cache/lighttpd/compress
        mkdir -p /var/cache/lighttpd/uploads
        chown ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /var/cache/lighttpd/uploads

		perl -pi -e 's/^server.port\s+=\s+80$/server.port = 8081/g' /etc/lighttpd/lighttpd.conf
		#cat ${BUILD_DIR}/SmartGW/conf/{$LIGHTTPD_CFG} >> /etc/lighttpd/lighttpd.conf

		lighttpd-enable-mod fastcgi | echo ''
		lighttpd-enable-mod fastcgi-php | echo ''
		lighttpd-enable-mod rewrite | echo ''
		
		
		stop_service lighttpd
        start_service lighttpd
        enable_service lighttpd
		
    fi
}

install_smartgw() {
	echo -e "Installing SmartGW"
	if [[ ! -d "/var/www/html/smartgw" ]]; then
		mkdir -p /var/www/html/smartgw
	else
		mv /var/www/html/smartgw /var/www/html/smartgw.${TIMENOW}
		mkdir -p /var/www/html/smartgw
		if [[ -f "/var/www/html/smartgw.${TIMENOW}/.database.db" ]]; then
			cp  /var/www/html/smartgw.${TIMENOW}/.database.db /var/www/html/smartgw/
		fi
	fi
	
	#if [[ -f "/var/www/html/index.html" ]]; then
	#	cp /var/www/html/index.html /var/www/html/index.html.${TIMENOW}
	#}
	#cp "${BUILD_DIR}"/SmartGW/conf/redirect-index.html /var/www/html/index.html

	cp -r "${BUILD_DIR}"/SmartGW/web/* /var/www/html/smartgw
	chown -R ${LIGHTTPD_USER}:${LIGHTTPD_GROUP} /var/www/html/smartgw/
}

configure_sudo() {
	echo -e "Configure sudo for ${LIGHTTPD_USER}"
	mkdir -p /etc/sudoers.d/
	echo '' > /etc/sudoers.d/smartgw
	echo "${LIGHTTPD_USER} ALL=NOPASSWD: /usr/sbin/service" >> /etc/sudoers.d/smartgw
	echo "${LIGHTTPD_USER} ALL=NOPASSWD: /usr/local/bin/openpyn" >> /etc/sudoers.d/smartgw
	echo "${LIGHTTPD_USER} ALL=NOPASSWD: /usr/bin/tail" >> /etc/sudoers.d/smartgw
	chmod 0440 /etc/sudoers.d/smartgw
}
install_openpyn() {
	if [[ "${INSTALL_OPENPYN}" == true ]]; then
		echo -e "Installing openpyn"
		python3 -m pip install --upgrade pip
		python3 -m pip install --upgrade openpyn
		#openpyn --init
		#openpyn de  -d
		#enable_service openpyn
	fi
}

main() {
    ######## FIRST CHECK ########
    # Must be root to install
    echo ""

    # If the user's id is zero,
    if [[ "${EUID}" -eq 0 ]]; then
        # they are root and all is good
        echo -e "Root user check"
    # Otherwise,
    else
        # They do not have enough privileges, so let the user know
        echo -e "Root user check"
        echo -e "Script called with non-root privileges"
        echo -e "The SmartGW requires elevated privileges to install and run"
        echo -e "Please re-run this installer as root"
        exit 1
    fi

    # Check for supported distribution
    distro_check
	
	if [[ "${DISTRO}" == "Debian" ]]; then
		add-apt-repository -y main
		add-apt-repository -y universe
		apt-get update
	fi
	
	
    # Update package cache
    update_package_cache || exit 1
	
    # Notify user of package availability
    notify_package_updates_available
	
    # Install packages used by this installation script
    install_dependent_packages INSTALLER_DEPS[@]

	
    # Check if SELinux is Enforcing
    checkSelinux

    local dep_install_list=("${SMARTGW_DEPS[@]}")
    if [[ "${INSTALL_WEB_SERVER}" == true ]]; then
        # Install the Web dependencies
        dep_install_list+=("${SMARTGW_WEB_DEPS[@]}")
    fi
	
    if [[ "${INSTALL_SQUID}" == true ]]; then
        # Install the Web dependencies
        dep_install_list+=("${SQUID_DEPS[@]}")
    fi

    if [[ "${INSTALL_DNSMASQ}" == true ]]; then
        # Install the Web dependencies
        dep_install_list+=("${DNSMASQ_DEPS[@]}")
    fi

    if [[ "${INSTALL_SNIPROXY}" == true ]]; then
        # Install the Web dependencies
        dep_install_list+=("${SNIPROXY_DEPS[@]}")
    fi
	
    if [[ "${INSTALL_OPENPYN}" == true ]]; then
        # Install the Web dependencies
        dep_install_list+=("${OPENPYN_DEPS[@]}")
    fi
	
    install_dependent_packages dep_install_list[@]
    unset dep_install_list

	echo -e "Creating build directory ${BUILD_DIR}"
	rm -rf "${BUILD_DIR}"
	mkdir "${BUILD_DIR}"
	cd "${BUILD_DIR}"
	
	
	echo -e "Clone SmartGW git repository"
	cd "${BUILD_DIR}"
	git clone https://github.com/mrahmadt/SmartGW.git
	
	install_sniproxy

	install_squid
	
	install_dnsmasq
	
	install_lighttpd
	
	install_smartgw
	
	configure_sudo
	
	install_openpyn
	
	echo ""
	echo ""
	echo ""
	echo ""
	echo -e "*************************************************************************************"
	echo -e "*** Installation completed Successfully"
	
	if [[ "${INSTALL_OPENPYN}" == true ]]; then	
		echo -e "--- IMPORTANT PLEASE RUN \"openpyn --init\" to complete the VPN setup"
	fi
    echo -e "--- View the web interface at http://${DEFAULT_IP%% }:8081/smartgw"
	echo -e "--- Configure your devices to use the SmartGW (${DEFAULT_IP%% }) as their DNS server"
	echo -e "**************************************************************************************"

}

main "$@"
