---
date: 2023-08-19T08:41:21Z
title: "What is Ubuntu, the operating system?"
description: "Understanding the release lifecycle of the Ubuntu Linux Distribution"
tags: ["Ubuntu", "Linux"]
---

## Release cycle

Ubuntu is released every 6 months in April and October. The version number for a given release is made of the year and the mouth when it was released. For example, Ubuntu 20.04 was released in April (04) 2020. Thus, refering to Ubuntu 20 or Ubuntu 18 doesn't make sense as the OS was released twice those years. Every two year, the April release is an Long Term Support (LTS) release. LTS releases are supported for 5 years and are usually what people tend to use the most. Indeed, non-LTS releases (also called interim releases) are only supported for 9 months.

## What is Ubuntu

Ubuntu (the operating system) is a set of Debian packages. Different installations of Ubuntu will have different set of packages installed by default but the user is free to install ANY package. Ubuntu Server is different from Ubuntu Desktop simply because the installer doesn't install the same set of packges to start with. For example, Ubuntu Server doesn't not have any window manager installed while Ubuntu Desktop doesn't have `htop` or `tmux` by default. However, once the installer is done the user is free to install a window manager from the official repo and any advanced user of Ubuntu will install `htop` on their Desktop install.

It is important to de-mistify the role of the installer. The installer is made of mini version of Ubuntu running a simple program that interacts with the user input. It partition the disk(s), create a filesystem, install packages on the filesystem and apply some minimal config changes to better fit the user's system. That's it. An experience user could do all of that manually:
 * boot a live version of Ubuntu from a USB stick
 * partition the disk with `fdidk`
 * format the partitions (`ESP`, `Boot`, `rootfs`)
 * mount all those partition somewhere
 * run `debootstrap`
 * use `chroot` update and install extra packages
 * reboot on the new system

## What is a Release?

A release is a set of packages pinned to a specific version. Some people tend to be very confused by this point. They think Ubuntu ships "old" or "deprecated" software. In fact this is one of the main benefits of the releases, they offer the assurence for the users that the (opensource) programs that are part of Ubuntu will keep being maintained for the life of the release. This is a very strong commitement considering Ubuntu LTSes are supported for 5 years. However, it also means that released version of Ubuntu will never get newer software or newer version of the included softwares. If version U of Ubuntu is released with version S of `systemd`, U will never get S+1. To get a newer version of `systemd` users will have to wait for U+1 (so the next `.10` or `.04` release) and upgrade to it in order to be able to get a newer version of `systemd`.

## What does "support" mean?

If a released version of Ubuntu will never get new software versions, what does support even mean? At this point, it is important to understand that not getting any new feature (and thus no **breaking change**) does not mean that the user will not receive security and bug fixes. The [Stable Release Update process](https://wiki.ubuntu.com/StableReleaseUpdates), describes what can and what cannot be updated after a version has been released. Especially, security and high-impact bug fixes are allowed to be backported but only the fixes will be backported, not new features.

## Why it can be confusing?

Since Windows 10, Microsoft
