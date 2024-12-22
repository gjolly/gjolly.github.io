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
