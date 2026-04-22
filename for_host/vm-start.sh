#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1
STATE=$(virsh domstate "$NAME" 2>/dev/null)
if [ "$STATE" != "running" ]; then
	virsh start "$NAME"
else
    virsh reboot "$NAME" --mode agent
fi