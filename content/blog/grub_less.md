+++
date = 2021-11-19T11:10:00Z
title = "Boot Linux without GRUB"
description = "How to take advantage of the Kernel's EFI stub to boot without any bootloader"
slug = ""
tags = ["Linux", "Kernel", "Boot"]
categories = []
externalLink = ""
series = []
+++

# Boot Linux without GRUB

To boot the Linux Kernel, most distro use a bootloader and one of the most popular is GRUB. But did you know you can directly boot the Kernel without using a bootloader?

**DISCLAIMER**: This is only for fun and learning, I do not advise anyone to do that on their main system. Be safe, use a VM.

## VM setup

Just a quick recap of what is needed (mostely stolen from [powersj's excelent blog post](https://powersj.io/posts/ubuntu-qemu-cli/#booting-with-uefi)).

Setup the user-data (for cloud-init) to be able to SSH into the VM:

```
cat > user-data.yaml <<EOF
#cloud-config
ssh_authorized_keys:
  - ssh-rsa AAAAB3NzaC1yc2EAAAABIwJJJQEA3I7VUf3l5gSn5uavROsc5HRDpZ ...
ssh_import_id:
  - gh:<github user>
  - lp:<launchpad user>
EOF

# cloud-localds is shipped in [cloud image utils](cloud-image-utils)
cloud-localds seed.img user-data.yaml
```

Copy the EFI vars to a temp place (they will get modified)

```
cp /usr/share/OVMF/OVMF_VARS.fd /tmp/
```

Download an Ubuntu cloud-image and launch the VM with the cloud-init metadata and the EFI firemware.

```
curl -O http://cloud-images.ubuntu.com/releases/21.10/release/ubuntu-21.10-server-cloudimg-amd64.img
qemu-system-x86_64 \
  -nographic \
  -cpu host \
  -enable-kvm \
  -smp 4 \
  -m 4G \
  -drive if=virtio,format=qcow2,file=ubuntu-21.10-server-cloudimg-amd64.img \
  -drive if=virtio,format=raw,file=./seed.img \
  -device virtio-net-pci,netdev=net0 --netdev user,id=net0,hostfwd=tcp::2222-:22 \
  -drive if=pflash,format=raw,readonly=on,file=/usr/share/OVMF/OVMF_CODE.fd \
  -drive if=pflash,format=raw,file=/tmp/OVMF_VARS.fd
```

To SSH into the VM: `ssh ubuntu@0.0.0.0 -p 2222`

## In practice

First, check if your Kernel config allows this:

```
$ cat /boot/config-$(uname -r) [|](|) grep EFI_STUB
CONFIG_EFI_STUB=y
```

Then, copy the Kernel and initrd to the EFI partition:

```
cp -v /boot/initrd.img-* /boot/efi/EFI/
cp -v /boot/vmlinuz-$(uname -r) /boot/efi/EFI/vmlinuz-$(uname -r).efi
```

> In theory, you can put those files wherever you want on the EFI partition (Ubuntu uses `/EFI/ubuntu` for example). Just be carefull about the length of the EFI stub path, [see this thread](https://www.kubuntuforums.net/showthread.php?60193-Going-GRUB-less-with-UEFI).

Now we need to find out some information about the system:
 - On which device (and partition) is located the root filesystem?
 - On which device and which partition is the EFI partition?

Example:

```
$ lsblk -o NAME,MOUNTPOINT,LABEL
NAME    MOUNTPOINT        LABEL
fd0
loop0   /snap/core20/1169
loop1   /snap/lxd/21780
loop2   /snap/snapd/13640
sr0
vda
├─vda1  /                 cloudimg-rootfs
├─vda14
└─vda15 /boot/efi         UEFI
vdb                       cidata
```

On this system, the root filesystem is in `/dev/vda1` and the `EFI` partition is on the same device `/dev/vda` on partition number 15.

Now, let's add a new boot entry in the UEFI boot manager

```
efibootmgr --create --disk /dev/vda --part 15 --label grub-less --loader "\EFI\vmlinuz-$(uname -r).efi" -u "root=/dev/vda1 initrd=\\EFI\\initrd.img-$(uname -r) ro console=ttyS0"
```

 - `efibootmgr` is a CLI tool to manipulate the UEFI boot manager (no one could have guessed it :D)
 - `--create` indicates we want to create a new boot entry
 - `--disk` specifies the disk containing the bootloader (here the Kernel)
 - `--part` is the partition where the boot loader (here the Kernel) is
 - `--label` is simply the name we want to give to this new boot entry
 - `--loader` is the actual bootloader we want to call, here it is the Kernel we previously copied
 - `-u` is used to pass arguments to the boot loader. Here we pass the location of `initrd` and the other usual Kernel command line arguments (check `/proc/cmdline` to find out which cmdline arguments are currently in use)

The current boot entries can then be checked with: `efibootmgr` (no arg). The new boot entry we just created should already be the first one in the bootorder list.

Reboot!! The system should start directly without going through the GRUB.

At the very beginning of the serial console, we can find:

```
BdsDxe: loading Boot0008 "grub-less" from HD(15,GPT,CB5D0560-825B-4575-A9E3-F3263C410054,0x2800,0x35000)/\EFI\vmlinuz-5.13.0-20-generic.efi
BdsDxe: starting Boot0008 "grub-less" from HD(15,GPT,CB5D0560-825B-4575-A9E3-F3263C410054,0x2800,0x35000)/\EFI\vmlinuz-5.13.0-20-generic.efi
EFI stub: Loaded initrd from command line option
```

## Troubleshooting

If something goes really wrong and the system doesn't boot, use [this post](./qemu_cheatsheet.md) to mount the EFI partition locally and simply delete the Kernel's EFI from it. The new entry will just fail to find the EFI stub and fallback to the old boot entry.

To delete a boot entry: `efibootmgr -b NUM -B`

## Refs

https://askubuntu.com/a/511019 The main source for this blog post
https://www.kubuntuforums.net/showthread.php?60193-Going-GRUB-less-with-UEFI&p=309923&viewfull=1#post309923 An undocumented issue
https://powersj.io/posts/ubuntu-qemu-cli/ to know more about how to use QEMU
https://docs.kernel.org/admin-guide/efi-stub.html The official Kernel doc about this Kernel feature
https://www.kernel.org/doc/html/v4.14/admin-guide/kernel-parameters.html more about Kernel command line
