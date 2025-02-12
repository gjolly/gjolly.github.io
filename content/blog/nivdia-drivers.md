---
date: 2025-02-12T20:30:00+01:00
title: "How to install NVIDIA drivers on Ubuntu"
description: "Stop installing the NVIDIA drivers from NVIDIA repos on Ubuntu"
tags: ["Linux", "Ubuntu", "NVIDIA"]
---

### Instructions

If you are on an LTS, make sure you are running the HWE kernel:

```bash
sudo apt update
source /etc/os-release
sudo apt install -y linux-generic-hwe-$VERSION_ID
```

And reboot.

Then:

```bash
sudo apt update
sudo apt install -y ubuntu-drivers-common
sudo ubuntu-drivers install
```

That's it. Don't install NVIDIA's Debian repositories, no need to re-compile everytime a new kernel is released and it works with secure boot.

### Wait but isn't that some opensource drivers that are less performant?

No. These will get you the closed-source, proprietary drivers.

### But aren't those old?

No. They might not be the latest ones if the latest ones just got released but they regularly get updated.

### But what is the difference with the NVIDIA drivers from NVIDIA then?

 1. They are pre-compiled for your Ubuntu Kernel. The drivers you get from NVIDIA are DKMS packages, which mean they will be re-compiled everytime your kernel is updated. Which can take a lot of time.
 2. Drivers shipped by Canonical are signed by Canonical, so secure boot works.

### So I don't need to install the NVIDIA repo at all?

You might have to in some cases (for example to install the NVIDIA container toolkit).
