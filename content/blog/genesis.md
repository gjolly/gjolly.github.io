---
date: 2023-06-09T09:10:00Z
title: "A basic CLI tool to build Ubuntu images"
description: "Build Ubuntu images from scratch with a CLI tool"
tags: ["Linux", "Ubuntu"]
---
Genesis a CLI project written in Python. It can build Ubuntu images from scratch.

The tool is named `genesis` (because you start from nothing). And is available as a python package: https://github.com/gjolly/genesis (it's also packaged as a deb in [a PPA](https://launchpad.net/~gjolly/+archive/ubuntu/genesis).

## A basic example

We are going to create a very minimal image of Ubuntu 23.04 (Lunar Lobster) and try to boot from it using `qemu`.

### Creating a base image

First you want to start by bootstrapping a basic filesystem:

```bash
sudo genesis debootstrap \
    --series lunar \
    --mirror 'http://archive.ubuntu.com/ubuntu' \
    --output chroot-lunar
```

Then, with this filesystem, you can create a disk-image:

```bash
sudo genesis create-disk \
    --rootfs-dir ./chroot-lunar \
    --disk-image lunar.img
```

Once this is done, you need to update your system (`debootstrap` only uses the release pocket). While doing this stage, you can install some extra packages. Here we are going to build a very minimalist image of Ubuntu using only

```bash
sudo genesis update-system \
    --disk-image lunar.img \
    --mirror 'http://archive.ubuntu.com/ubuntu' \
    --series lunar \
    --extra-package openssh-server --extra-package ca-certificates --extra-package linux-kvm
```

We still need to install a boot loader and this operation requires its own command:

```bash
sudo genesis install-grub --disk-image lunar.img
```

### Final customizations

The image is almost ready but we can (and here we need) customize it by adding extra files directly on the filesystem. This is done with the `copy-files` command.

#### Configuring networking

Because we did not install cloud-init in our image, we need to pre-configure it with everything it needs. Here we assume that this image will be run with `qemu` and a virtual network card attached. We configure netplan accordingly:

```bash
sudo genesis copy-files \
    --disk-image lunar.img \
    --file $PWD/netplan.yaml:/etc/netplan/image-default.yaml
```

with `netplan.yaml` being the following:

```yaml
network:
    version: 2
    ethernets:
        eth0:
            dhcp4: true
            match:
                driver: virtio_net
            set-name: eth0
```

### Configuring the sources

We want to define the Debian packages source. For now there is just a default source file pointing to `http://archive.ubuntu.com` in the image. Maybe, since I live in France, I want my image to be configured with a local mirror:

```
deb https://fr.archive.ubuntu.com/ubuntu lunar main universe restricted
deb https://fr.archive.ubuntu.com/ubuntu lunar-updates main universe restricted
deb https://fr.archive.ubuntu.com/ubuntu lunar-security main universe restricted
```

This is what my `sources.list` would look like and I can now install it on the live image:

```bash
sudo genesis copy-files \
    --disk-image lunar.img \
    --file $PWD/sources.list:/etc/apt/sources.list
```

### User config

Finally, I need to configure a user. Here I create a user with `create-user` and I copy my public ssh key in `.ssh/authorized_keys` directory for this user.

```bash
sudo genesis create-user \
    --disk-image lunar.img \
    --username ubuntu --sudo
sudo genesis copy-files \
    --disk-image lunar.img \
    --file $HOME/.ssh/id_rsa.pub:/home/ubuntu/.ssh/authorized_keys --mod 600 --owner ubuntu
```

Note that if `.ssh` does not exist under `/home/ubuntu`, it will be automatically created by `copy-files`.

### Running the image

Now let's try to run this image that we have just created. For that we need a bit of `qemu` black magic:

```bash
qemu-system-x86_64 \
    -cpu host -machine type=q35,accel=kvm -m 2048 \
    -nographic -snapshot \
    -netdev id=net00,type=user,hostfwd=tcp::2222-:22 \
    -device virtio-net-pci,netdev=net00 \
    -drive if=virtio,format=raw,file=./lunar.img \
    -drive if=pflash,format=raw,file=/usr/share/OVMF/OVMF_CODE.fd,readonly=on \
    -drive if=pflash,format=raw,file=/usr/share/OVMF/tmp/OVMF_VARS.fd,readonly=on
```

If you don't understand what is going on, this is not very important. But, assuming you have all the right dependencies installed, this should start a virtual machine and boot on the disk we've just created.

Then you should be able to ssh (by opening another terminal):

```bash
ssh ubuntu@0.0.0.0 -p 2222
```

And we can check the boot time:

```bash
ubuntu@ubuntu:~$ sudo systemd-analyze critical-chain
The time when unit became active or started is printed after the "@" character.
The time the unit took to start is printed after the "+" character.

graphical.target @740ms
└─multi-user.target @739ms
  └─systemd-logind.service @681ms +43ms
    └─basic.target @663ms
      └─sockets.target @663ms
        └─ssh.socket @662ms
          └─sysinit.target @636ms
            └─systemd-resolved.service @548ms +86ms
              └─systemd-tmpfiles-setup.service @536ms +9ms
                └─local-fs.target @528ms
                  └─boot-efi.mount @506ms +21ms
                    └─dev-vda15.device @476ms
```

Because the image is so minimal, the system boots in less than a second.

## Is it usable for building production-ready images of Ubuntu?

**No**
