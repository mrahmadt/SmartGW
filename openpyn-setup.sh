#!/usr/bin/expect -f

# Expect script to automate Initialisation of openpyn. It runs "openpyn --init" and sets the nordvpn_username,
# nordvpn_password. As well as openpyn_options to be stored in the openpyn.service systemd service file.
# Make sure expect is installed:      apt install expect or dnf/yum install expect
# Needs to be run as root or with sudo.
# $0 is nordvpn_username
# $1 is nordvpn_password
# $2 is openpyn_options     use quotes for this, definately needed if more than 1 option     e.g : "au -f"

# example usage:   sudo expect openpyn-setup.sh user@youremail.com p@ss1 "au -t -3 -f"


set nordvpn_username [lindex $argv 0]
set nordvpn_password  [lindex $argv 1]
set openpyn_options [lindex $argv 2]

set timeout -1
spawn openpyn --init
match_max 100000

expect "*"

expect "Enter your username"
send -- "$nordvpn_username\r"
expect "Enter the password"
send -- "$nordvpn_password\r"
expect "options to be stored in systemd"
send -- "$openpyn_options"
expect "*"
send -- "\r"
expect eof
