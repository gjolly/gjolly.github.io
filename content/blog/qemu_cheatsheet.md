+++
date = 2021-11-19T11:10:00Z
title = "QEMU cheatsheet"
description = "How to use QEMU and a few tricks"
slug = ""
tags = ["Linux", "QEMU"]
categories = []
externalLink = ""
series = []
+++

# QEMU cheatsheet

## The basics

https://powersj.io/posts/ubuntu-qemu-cli/

## Mount disk images

It is ofter very convenient to mount a FS locally to be able to debug and/or fix a problem with a broken disk.

Pre-requisite to everything: `mkdir /tmp/rootfs`

To know the format of your disk: `qemu-img info disk.img` (note that `qemu-img` can output JSON to automate your stuff)

### Raw disk images

```
losetup -f -P disk.img
losetup -l | grep -v snap # to find the loop device you just created and yeah those snaps....
mount /dev/loopXpX /tmp/rootfs
```

### For anything else (QCOW2, VHD/VPC, etc...)

```
modprobe nbd
qemu-nbd --connect=/dev/nbd0 disk.img
fdisk /dev/nbd0 -l # to find your partition
mount /dev/nbd0pX /tmp/rootfs
```
