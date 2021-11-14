# Firewall, Tailscale and Ubuntu

I recently enabled the Firewall on my desktop on Ubuntu. I probably did a quick lookup online to find out that `sudo ufw enable` was enough to enable it. I entered the command and forgot about it.

```
$ sudo ufw enable
Firewall is active and enabled on system startup
```

Obviously, (and to be honest I was waiting for it), it didn't take long for things to go bad. A few weeks later, while I was not at home and wanted to SSH on my machine via [tailscale](https://tailscale.com/), I realized that I couldn't and quickly remember about the Firewall.

Quick side note here: I configured `sshd` to only bind to the tailscale IP address. I don't want to expose my desktop on the internet.

## Uncomplicated FireWall

[`ufw`](https://wiki.ubuntu.com/UncomplicatedFirewall) was introduced by Ubuntu to ease firewall configuration.

On Linux, "Firewalling" is usually done through the [Netfilter subsystem](https://www.netfilter.org/) which can be configured via the userspace tool [nftables](https://www.netfilter.org/projects/nftables/index.html) (successor of [iptables](https://www.netfilter.org/projects/iptables/index.html)). Because `nftables` is made to be very generic and provides a full interface for the Netfilter subsystem, while being very powerfull it is not easy to learn.

`ufw` is a simplified interface on top `nftables`. It helps the user to define simple Firewall rules.

This [blog post](https://discourse.ubuntu.com/t/security-firewall/11883) describes basic use cases.

## Allow SSH on tailscale only

A very cool feature of `ufw` is the notion of `app`. An `app` is defined by a config file stored in `/etc/ufw/applications.d`. Apps can be listed with `ufw app list`.

On my system I already had the `OpenSSH` app configured:

```
$ cat /etc/ufw/applications.d/openssh-server
[OpenSSH]
title=Secure shell server, an rshd replacement
description=OpenSSH is a free implementation of the Secure Shell protocol.
ports=22/tcp
```

Indeed, on Ubuntu, this configuration file is shipped with the `openssh-server` package. Now to enable `OpenSSH` on tailscale for both IPv4 and IPv6, I can simply run:

```
sudo ufw allow in on tailscale0 from any to any app OpenSSH
```
