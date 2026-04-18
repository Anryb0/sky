#!/bin/bash
export LIBVIRT_DEFAULT_URI='qemu:///system'
NAME=$1

sudo virt destroy "$NAME"
 