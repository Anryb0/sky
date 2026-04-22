#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1

if ! virsh dominfo "$NAME" >/dev/null 2>&1; then
    exit 1
fi
virsh shutdown "$NAME" 2>/dev/null
for i in {1..60}; do
    STATE=$(virsh domstate "$NAME" 2>/dev/null)
    
    if [ "$STATE" == "shut off" ]; then
        echo -e "\nВМ '$NAME' успешно выключена."
        exit 0
    fi
    
    echo -n "."
    sleep 1
done
virsh destroy "$NAME"