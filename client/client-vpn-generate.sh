#!/bin/bash
clientname=$1
clientip=$2 

if [ -z "$clientname" ] || [ -z "$clientip" ]; then
    echo "нет данных"
    exit 1
fi

cd /etc/openvpn/server/easy-rsa/
./easyrsa --batch build-client-full "$clientname" nopass

mkdir -p /etc/openvpn/ccd
echo "ifconfig-push 10.8.0.$clientip 255.255.255.0" > /etc/openvpn/ccd/"$clientname"

cp /etc/openvpn/client/client-vpn-template.txt /etc/openvpn/client/"$clientname".ovpn

echo -e "\n<cert>" >> /etc/openvpn/client/"$clientname".ovpn
sed -ne '/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p' /etc/openvpn/server/easy-rsa/pki/issued/"$clientname".crt >> /etc/openvpn/client/"$clientname".ovpn
echo -e "</cert>\n" >> /etc/openvpn/client/"$clientname".ovpn

echo "<key>" >> /etc/openvpn/client/"$clientname".ovpn
cat /etc/openvpn/server/easy-rsa/pki/private/"$clientname".key >> /etc/openvpn/client/"$clientname".ovpn
echo -e "</key>\n" >> /etc/openvpn/client/"$clientname".ovpn

cat /etc/openvpn/client/client-vpn-tlsca.txt >> /etc/openvpn/client/"$clientname".ovpn

echo "$clientname $clientip"
echo "/etc/openvpn/client/$clientname.ovpn"