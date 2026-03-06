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

cp /network/client-vpn-template.txt /network/configs/"$clientname".ovpn

echo -e "\n<cert>" >> /network/configs/"$clientname".ovpn
sed -ne '/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p' /etc/openvpn/server/easy-rsa/pki/issued/"$clientname".crt >> /network/configs/"$clientname".ovpn
echo -e "</cert>\n" >> /network/configs/"$clientname".ovpn

echo "<key>" >> /network/configs/"$clientname".ovpn
cat /etc/openvpn/server/easy-rsa/pki/private/"$clientname".key >> /network/configs/"$clientname".ovpn
echo -e "</key>\n" >> /network/configs/"$clientname".ovpn

cat /network/client-vpn-tlsca.txt >> /network/configs/"$clientname".ovpn

echo "$clientname $clientip"
echo "/etc/openvpn/client/$clientname.ovpn"