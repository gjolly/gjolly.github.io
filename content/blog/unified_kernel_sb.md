+++
date = 2022-11-13T09:10:00Z
title = "FDE, Secureboot and unified kernel image"
description = "Full Disk Encryption on most Linux distro has a major security flow. Why? How to fix it?"
slug = ""
tags = ["Linux", "Kernel", "Boot", "Ubuntu"]
categories = []
externalLink = ""
series = []
+++

# Full Disk Encryption, Secureboot and Unified Kernel Image

FDE protect your data at rest and Secureboot makes sure what you boot is trusted. But there is a flow.

## The flow

In order to decrypt the root filesystem, the kernel uses a initial ram disk (initramfs). The initramfs provides an temporary filesystem from which extra kernel modules can be loaded, it also contains a set of scripts used to boot the system including scripts to decrypt the user's root filesystem.
This initramfs image is a file stored un-encrypted next to the kernel image. However, unlike the kernel image, it is not signed by the kernel publisher as the iniramfs is generated locally and can be modified by the user. Thus, anyone with physical access to the user's drive can inject a malicious initramfs that would log the user's passphrase and thus make FDE useless.

## How to fix it

We can bundle the kernel and initramfs together in a single binary and sign this binary locally. Thus, modifying the initramfs would prevent the system from booting.

## In practice

On systems using `mkinitcpio` or `dracut` see this article: https://wiki.archlinux.org/title/Unified_kernel_image#Preparing_a_unified_kernel_image.

### On Ubuntu

To create the unified EFI binary:

```
sudo add-apt-repository ppa:snappy-dev/image
sudo apt-get -y install ubuntu-core-initramfs
sudo ubuntu-core-initramfs create-efi --unsigned --output "kernel.efi.unsigned" \
        --cmdline "$(cut -f 2- -d' ' /proc/cmdline)" \
        --kernel "/boot/vmlinuz" \
        --kernelver "$(uname -r)" \
        --initrd "/boot/initrd.img"
```

Create and enroll a new MOK:

```
# You can let everything as default, or customize the fields, it doesn't matter
openssl req -new -x509 -newkey rsa:2048 \
        -nodes -days 36500 -outform DER \
        -keyout "MOK.priv" \
        -out "MOK.der"
openssl x509 -in MOK.der -inform DER -outform PEM -out MOK.pem
sudo mokutil --import MOK.der
```

Restart your system and follow the instructions to enroll the key.

Sign the Kernel EFI:

```
sudo sbsign --key MOK.priv --cert MOK.pem kernel.efi.unsigned --output kernel.efi
```

Move it do the ESP (or to the /boot partition), example:

```
sudo mv kernel.efi /boot/efi/EFI/ubuntu
```

Add a new boot entry to boot on this kernel, example (make sure to point change `--disk` and `--part` to your ESP):

```
sudo efibootmgr --create --disk /dev/vda --part 15 --label 'Ubuntu 5.15.0-52-generic' --loader "\EFI\ubuntu\shimx64.efi" -u "\EFI\ubuntu\kernel.efi"
```

## Limitations/Next

What will happen when a new kernel will be released? With this example, we would stay blocked on the old kernel. To make it production ready, we need to automated those steps to run them everytime a new kernel is installed.
