---
date: 2025-11-18T12:10:00Z
title: "Build an Ubuntu Destkop image with genesis"
description: "How to use my image build tool to easily build Desktop images"
tags: ["Linux", "genesis", "Ubuntu"]
---
## Intro

A few years ago [I built a tool](./genesis.md) called `genesis` to build images of Ubuntu. The tool has a very basic CLI interface and is written in Python. I was asked recently if I had ever built desktop images of Ubuntu from scratch and had to admit that I had never tried. I decided to give it a go and found the process rather straight forward.

## Steps

As usual we start by building the initial root filesystem using debootstrap:

```bash
sudo .venv/bin/genesis debootstrap \
    --output ./noble-rootfs \
    --series noble
```

Then, we build the image. We need to make it big enough to hold a lot more stuff than server images usually contains:

```bash
sudo .venv/bin/genesis create-disk \
    --size 11G \
    --disk-image noble-disk.img \
    --rootfs-dir ./noble-rootfs
```

Then, we install the `ubuntu-desktop-minimal` package while updating the system:

```bash
sudo .venv/bin/genesis update-system \
    --disk-image noble-disk.img \
    --mirror "http://fr.archive.ubuntu.com/ubuntu" \
    --series "noble" \
    --extra-package ubuntu-desktop-minimal \
    --extra-package linux-generic
```

And finally, we install Grub:

```bash
sudo .venv/bin/genesis install-grub --disk-image noble-disk.img
```

I was able to verify that the system booted fine using qemu and gnome works as expected:

```bash
#!/bin/bash -eu

qemu-system-x86_64 \
        -snapshot \
        -cpu host -machine type=q35,accel=kvm \
        -m 2048 -smp 4 \
        -netdev id=net00,type=user,hostfwd=tcp::2222-:22 \
        -device virtio-net-pci,netdev=net00 \
        -drive if=pflash,format=raw,unit=0,file=/usr/share/OVMF/x64/OVMF_CODE.4m.fd,readonly=on \
        -drive if=pflash,format=raw,unit=1,file=/usr/share/OVMF/x64/OVMF_VARS.4m.fd \
        -drive if=virtio,format=raw,file=./noble-disk.img
```

## Going further

Users who might be interested in building these kind of images will probably want to customize the image further: set the right timezone, configure the language of their system, automatically join an AD domain on boot, etc...

### Disable initial setup

When launching the image for the first time, the user is prompted to enter their timezone and setup an initial user. To prevent this from happening it is enough to delete this package: `gnome-initial-setup`. `genesis` doesn't support removing packages yet but this can be done manually this way:

```bash
sudo losetup --partscan --show --find ./noble-disk.img
sudo mount /dev/loopXp1 /mnt

sudo chroot /mnt apt purge -y gnome-initial-setup
# APPLY OTHER SYSTEM CHANGES HERE

sudo umount /mnt
sudo losetup -d /dev/loopX
```
