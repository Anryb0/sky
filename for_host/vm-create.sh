#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1
CPUS=$2
RAM=$(($3 * 1024))
DRIVE=$4
FILENAME=$5
ROOT_PASS=$6

IMG_DIR="/vms"
OVPN_FILE="/network/${NAME}.ovpn"
VM_PATH="$IMG_DIR/$NAME.qcow2"

# Создаём временный файл notify_script.sh
cat > /tmp/notify_script.sh << 'EOF'
#!/bin/bash
curl -X POST -d "name=$(hostname)" https://anryb0.ru/sky/api/vmready.php
EOF

# Делаем его исполняемым
chmod +x /tmp/notify_script.sh

# Проверяем существование обязательных файлов
if [ ! -f "$OVPN_FILE" ]; then
    echo "Ошибка: файл $OVPN_FILE не найден"
    exit 1
fi
if [ ! -f "/tmp/notify_script.sh" ]; then
    echo "Ошибка: не удалось создать /tmp/notify_script.sh"
    exit 1
fi

# Создаём диск и изменяем размер
qemu-img create -f qcow2 -b "$IMG_DIR/$FILENAME" -F qcow2 "$VM_PATH"
qemu-img resize "$VM_PATH" "${DRIVE}G"

# Кастомизируем образ
sudo virt-customize -v -x -a "$VM_PATH" \
  --run-command "echo 'root:$ROOT_PASS' | chpasswd" \
  --run-command "sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config" \
  --run-command "systemctl restart sshd" \
  --run-command "echo '$NAME' > /etc/hostname" \
  --run-command "apt -y update" \
  --run-command "apt install curl" \
  --run-command "if grep -q '^127.0.1.1' /etc/hosts; then sed -i 's/127.0.1.1.*/127.0.1.1\t$NAME/' /etc/hosts; else echo '127.0.1.1 $NAME' >> /etc/hosts; fi" \
  --run-command "echo '127.0.0.1 $NAME' >> /etc/hosts" \
  --upload "$OVPN_FILE:/etc/openvpn/client/vpn_sky.conf" \
  --upload "/tmp/notify_script.sh:/etc/openvpn/client/on_up.sh" \
  --chmod "0600:/etc/openvpn/client/vpn_sky.conf" \
  --chmod "0755:/etc/openvpn/client/on_up.sh" \
  --run-command "echo 'script-security 2' >> /etc/openvpn/client/vpn_sky.conf" \
  --run-command "echo 'up /etc/openvpn/client/on_up.sh' >> /etc/openvpn/client/vpn_sky.conf" \
  --run-command "systemctl enable openvpn-client@vpn_sky" \
  --run-command "echo 'nameserver 8.8.8.8' > /etc/resolv.conf" \

# Устанавливаем владельца диска
chown qemu:qemu "$VM_PATH" 2>/dev/null || true

# Запускаем виртуальную машину
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