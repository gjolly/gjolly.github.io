---
date: 2024-10-14T17:10:00Z
title: "Boot Linux with coreboot without bootloader"
description: "Virtual firmware can be as big as you want, so you can fit a entire kernel."
tags: ["Linux", "Kernel", "Boot", "Ubuntu"]
---
## Boot process - context

In general, the boot process looks like this:

```
                     ROM                |          DISK
Pre-EFI initialization -> EFI firwmare -|> shim -> grub -> Linux
```

The pre-efi initialization is about initializing CPU and devices. Especially, it is responsible for initializing the DRAM controller on the CPU. Before this step the system is in a very precurious state and can only use its cache as memory (aka Cache as RAM).

"EFI firmware" is misleading as EFI stands for Extensible Firmware Interface and is a specification not a firwmare in itself. The "EFI firmware" is the part of the firmware implementing the EFI specification.

### Why do we need a firmware?

 1. because on hardware devices, the ROM is usually fairly small (a few megabytes)
 2. because the firmware in the ROM is owned by the hadware vendor and thus needs to transition to the OS world
 3. because Windows 10 requires a EFI firmware to be able to boot

But in the VM world where nothing is real, these points are not valid anymore.

 1. the firwmare can be as big as we want as there is no physical ROM
 2. the VM gets provisioned with a firmware that is provided along with the OS disk, thus OS and firmware are on the same level
 3. if you want to boot windows you can just configure your VM to use another firwmare

## Boot Linux with Coreboot

### Build `coreboot` with a bzimage payload

Instructions are provided here: https://doc.coreboot.org/tutorial/part1.html

Make sure to configure the payload to be a bzimage
 * you can simply copy a generic linux kernel vmlinuz from ubuntu
 * make sure compression is un-selected
 * give it a cmdline (eg `root=PARTUUID=uuid-for-your-disk-part console=ttyS0 earlyprintk=ttyS0`)

Make sure to select a QEMU board:
 * 'Mainboard vendor' should be '(Emulation)'
 * 'Mainboard model' should be 'Qemu q35'

### Run in `qemu`

Here `noble-server-cloudimg-amd64.img` is taken from [cloud-images.ubuntu.com](http://cloud-images.ubuntu.com/noble)

```bash
qemu-system-x86_64 \
    -enable-kvm -nographic \
    -cpu host -m 2048 \
    -snapshot \
    -machine q35,max-fw-size=20000000 \
    -drive if=pflash,format=raw,unit=0,file=./coreboot.rom,readonly=on \
    -drive if=virtio,format=qcow2,file=./noble-server-cloudimg-amd64.img
```
