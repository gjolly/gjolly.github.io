+++
date = 2021-11-19T11:10:00Z
title = "Boot Linux without Grub"
description = "How to take advantage of the kernel's EFI stub to boot without any bootloader"
slug = ""
tags = ["Linux", "Kernel", "Boot"]
categories = []
externalLink = ""
series = []
+++

# Boot Linux without Grub

To boot the Linux kernel, most disto use a bootloader and one of the most popular is Grub. But did you know you can directly boot the kernel without using a bootloader?

**DISCLAIMER**: This is only for fun and learning, I do not advise anyone to do that on their main system. Be safe, use a VM.

To use UEFI with QEMU, just read [powersj's excelent blog post](https://powersj.io/posts/ubuntu-qemu-cli/#booting-with-uefi).

First, check if your kernel config allows this:

```
$ cat /boot/config-${uname -r} | grep EFI_STUB
CONFIG_EFI_STUB=y
```

Then, copy the kernel and initrd to the EFI partition:

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

On this system, the root filesystem is `/dev/vda1` and the `EFI` partition is on the same device `/dev/vda` on partition number 15.

Now, let's add a new boot entry in the UEFI boot manager

```
efibootmgr --create --disk /dev/vda --part 15 --label grub-less --loader "\EFI\vmlinuz-$(uname -r).efi" -u "root=/dev/vda1 initrd=\\EFI\\initrd.img-$(uname -r) ro console=ttyS0"
```

 - `efibootmgr` is a CLI tool to manipulate the UEFI boot manager (no one could have guessed it :D)
 - `--create` indicates we want to create a new boot entry
 - `--disk` specifies the disk containing the bootloader (here the kernel)
 - `--part` is the partition where the boot loader (here the kernel) is
 - `--label` is simply the name we want to give to this new boot entry
 - `--loader` is the actual bootloader we want to call, here it is the kernel we previously copied
 - `-u` is used to pass arguments to the boot loader. Here we pass the location of `initrd` and the other usual Kernel command line arguments (check `/proc/cmdline` to find out which cmdline arguments are currently in use)

The current boot entries can then be checked with: `efibootmgr` (no args). The new boot entry we just created should already be the first one in the bootorder list.

Reboot!! The system should start directly without going through the Grub menu.

## Troubleshooting

If something goes really wrong and the system doesn't boot, use [this post](./qemu_cheatsheet.md) to mount the EFI partition locally and simply delete the Kernel's EFI from it. The new entry will just fail to find the EFI stub and fallback to the old boot entry.

To delete a boot entry: `efibootmgr -b NUM -B`

## Refs

https://askubuntu.com/a/511019 The main source for this blog post
https://www.kubuntuforums.net/showthread.php?60193-Going-GRUB-less-with-UEFI&p=309923&viewfull=1#post309923 An undocumented issue
https://powersj.io/posts/ubuntu-qemu-cli/ to know more about how to use QEMU
https://docs.kernel.org/admin-guide/efi-stub.html The official kernel doc about this kernel feature
https://www.kernel.org/doc/html/v4.14/admin-guide/kernel-parameters.html more about Kernel command line
