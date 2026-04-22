#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1

if ! virsh dominfo "$NAME" >/dev/null 2>&1; then
    exit 1
fi
virsh destroy "$NAME" 2>/dev/null
virsh undefine "$NAME" --remove-all-storage --nvram
