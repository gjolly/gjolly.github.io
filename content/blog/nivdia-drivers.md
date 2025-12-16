---
date: 2025-02-12T20:30:00+01:00
title: "How to install NVIDIA drivers on Ubuntu"
description: "Stop installing the NVIDIA drivers from NVIDIA repos on Ubuntu"
tags: ["Linux", "Ubuntu", "NVIDIA"]
---
## Make sure the system is up-to-date

This section is important to avoid pulling DKMS NVIDIA drivers during the installation.

First make sure your server is up-to-date:

```bash
sudo apt update
sudo apt full-upgrade -y
```

If your system needs reboot, reboot it before running:

```bash
sudo apt autoremove -y
```

> **Note**: You can check if your system needs to be rebooted by checking if this file exists: `/var/run/reboot-required`.

Rebooting before running `apt autoremove` allows `apt` to remove the kernel that was running before the reboot, which might be old and thus trigger the installation of DKMS NVIDIA drivers.

## If you trust the automated way

Then the simplest way to install the NVIDIA drivers on Ubuntu is to use the built-in tool:

```bash
sudo apt update
sudo apt install -y ubuntu-drivers-common
sudo ubuntu-drivers install
```

That's it. Don't install NVIDIA's Debian repositories, no need to re-compile everytime a new kernel is released and it works with secure boot.

## If you want to do it manually

First, identify the kernel flavor you are using:

 * If you are running on a cloud, `${FLAVOR}` should match the kernel you are running.
For example, on AWS, users will probably be running `linux-aws`, the package to install should
thus be `...-server-aws` (same for Azure, GCP...). If you are unsure about which kernel you are
running, simply run `uname -r`.
 * If you are running the HWE kernel, `${FLAVOR}` should be `generic-hwe-24.04` (for
Ubuntu 24.04). If you are unsure, check the output of `apt list --installed | grep linux-image`,
if you see `-hwe-` in the name, you are probably running the HWE kernel.
 * If you are in none of these cases, `${FLAVOR}` is probably `generic`.

Then, simply install these two packages: the first contains the drivers and the second contains utilities like `nvidia-smi`.

```bash
apt install -y \
    linux-modules-nvidia-${DRIVER_VERSION}-server-${FLAVOR} \
    nvidia-utils-${DRIVER_VERSION}-server
```

> **Note**: `server` vs non-`server` packages: the server packages are for GPGPU (General Purpose GPU) usage, which is what you want for compute workloads. The non-server packages are for desktop usage (actually displaying graphics on a monitor).

If you don't know which driver version to use, you can use `580` which is the latest version available in the archive as I write these lines.

## Maintaining the drivers

To keep updating the NVIDIA drivers, regularly check if a new version of the drivers is available by running:

```bash
sudo apt update
apt list | grep 'linux-modules-nvidia-[0-9]\+-server-${FLAVOR}/'
```

To avoid ever downloading the NVIDIA DKMS drivers during system updates, make sure to always update your kernel, reboot and run `apt autoremove` to remove old kernels that could trigger the installation of DKMS drivers. Indeed, new drivers are only pre-built for the latest kernels available in the Ubuntu archive

## FAQ

### Wait but isn't that some opensource drivers that are less performant?

No. These will get you the closed-source, proprietary drivers.

### But aren't those old?

No. They might not be the latest ones if the latest ones just got released but they regularly get updated.

### But what is the difference with the NVIDIA drivers from NVIDIA then?
 1. They are pre-compiled for your Ubuntu Kernel. The drivers you get from NVIDIA are DKMS packages, which mean they will be re-compiled everytime your kernel is updated. Which can take a lot of time.
 2. Drivers shipped by Canonical are signed by Canonical, so secure boot works.

### So I don't need to install the NVIDIA repo at all?

You might have to in some cases (for example to install the NVIDIA container toolkit).

## References

 - [Ubuntu documentation about NVIDIA drivers](https://documentation.ubuntu.com/server/how-to/graphics/install-nvidia-drivers/)
 - [Ubuntu Kernel cycles](https://ubuntu.com/about/release-cycle?product=ubuntu-kernel&release=ubuntu+kernel&version=all)