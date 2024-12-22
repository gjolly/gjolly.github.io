---
date: 2024-12-22T12:10:00Z
title: "Build an Ubuntu Server live image with mkosi"
description: "Let's see how to use mkosi to build an Ubuntu image"
tags: ["Linux", "mkosi", "Ubuntu"]
---
## Basic Config

```
[Distribution]
Distribution=ubuntu

[Output]
Format=disk

[Content]
Packages=
    apt
    cloud-init
    dbus
    grub-efi-amd64-signed
    iproute2
    linux-virtual
    netplan.io
    openssh-server
    openssl
    shim-signed
    ssh-import-id
    sudo
    systemd
    systemd-resolved
    udev
    vim
Bootloader=grub
ShimBootloader=signed
BiosBootloader=none
Bootable=true
RootPassword=ubuntu
KernelCommandLine=console=ttyS0
Hostname=ubuntu
```

Then simply run `mkosi`.

## Boot the image

Use [this script](https://github.com/gjolly/qemu-utils/blob/main/start-vm.sh) (use --no-snapshot to make the changes persist):

```bash
./start-vm.sh ./image.raw
```

## To go further

At the moment, `mkosi` only supports producing raw disk images. To convert the image to `qcow2`:

```bash
qemu-img convert -f raw -O qcow2 /tmp/image.raw /tmp/ubuntu.img
```

And to make it (virtually) bigger:

```bash
qemu-img resize /tmp/ubuntu-24.04.img +50G
```
