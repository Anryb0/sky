#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1
CPUS=$2
RAM=$(($3 * 1024))
DRIVE=$4
FILENAME=$5

IMG_DIR="/vms"
OVPN_FILE="/network/${NAME}.ovpn"
VM_PATH="$IMG_DIR/$NAME.qcow2"

if [ ! -f "$OVPN_FILE" ]; then echo "нет файла"; exit 1; fi

qemu-img create -f qcow2 -b "$IMG_DIR/$FILENAME" -F qcow2 "$VM_PATH"
qemu-img resize "$VM_PATH" "${DRIVE}G"

cat <<EOF > /tmp/notify_script.sh
#!/bin/bash
sleep 2
curl -X POST -d "name=$NAME" https://anryb0.ru/sky/api/vmready.php
EOF

sudo virt-customize -v -x -a "$VM_PATH" \
  --upload "$OVPN_FILE:/etc/openvpn/client/vpn_sky.conf" \
  --upload "/tmp/notify_script.sh:/etc/openvpn/client/on_up.sh" \
  --chmod "0600:/etc/openvpn/client/vpn_sky.conf" \
  --chmod "0755:/etc/openvpn/client/on_up.sh" \
  --run-command "echo 'script-security 2' >> /etc/openvpn/client/vpn_sky.conf" \
  --run-command "echo 'up /etc/openvpn/client/on_up.sh' >> /etc/openvpn/client/vpn_sky.conf" \
  --run-command "systemctl enable openvpn-client@vpn_sky"

rm /tmp/notify_script.sh
  

chown qemu:qemu "$VM_PATH" 2>/dev/null || true

sudo virt-install \
  --name "$NAME" \
  --vcpus "$CPUS" \
  --memory "$RAM" \
  --disk path="$VM_PATH",format=qcow2,bus=virtio \
  --import \
  --os-variant ubuntu24.04 \
  --network network=default \
  --graphics vnc,listen=0.0.0.0 \
  --noautoconsole