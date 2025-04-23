---
date: 2025-04-23T09:10:00Z
title: "Practical Guide To Virtual Networking on Linux"
description: "How to create and manage virtual networks on you Linux host."
tags: ["Linux", "Networking"]
---

## Linux Networking: Bridged and Routed VM Networking

### Scenario 1: Bridged Networking (Layer 2 Integration)

**Goal**: VMs/containers appear as full LAN peers, get IPs from the LAN's DHCP, and are reachable directly.

- Create a bridge interface (`br0`).
- Add the physical interface (e.g., `eth0`) to the bridge.
- Assign IP or DHCP **to `br0` only**. `eth0` should have no IP.
- Virtual interfaces (e.g., `tap0`) are also added to `br0`.

#### Key Commands:

```bash
sudo ip link add br0 type bridge
sudo ip link set eth0 master br0
sudo ip link set br0 up
sudo dhcpcd br0
```

> No NAT, no subnetting. Full LAN access.

### Scenario 2: Routed Subnet for VMs (Layer 3 Isolation)

**Goal**: Create a dedicated VM network (`10.0.0.0/24`) separate from the LAN (`192.168.1.0/24`). The host routes between them.

#### Steps:

1. **Create a bridge for VMs**:

```bash
sudo ip link add br0 type bridge
sudo ip addr add 10.0.0.1/24 dev br0
sudo ip link set br0 up
```

2. **Enable IP forwarding**:

```bash
echo 1 | sudo tee /proc/sys/net/ipv4/ip_forward

# To make it permanent
echo "net.ipv4.ip_forward=1" | sudo tee -a /etc/sysctl.conf
```

3. **Add NAT to route external traffic**:

```bash
sudo iptables -t nat -A POSTROUTING -s 10.0.0.0/24 -o eth0 -j MASQUERADE
```

4. **Attach VM interfaces (`tapX`, `vethX`) to `br0`**.
5. **(Optional) Run a DHCP server (e.g., dnsmasq) on `br0`**.

> Gives you subnet isolation, control, and routing. Useful for test environments, services segregation, or firewall zones.

## Step-by-Step: Create and Attach a TAP Interface for a VM

### 1. Create a TAP Device

You must create it as the user that will run the VM, or as root.

```bash
sudo ip tuntap add dev tap0 mode tap user $(whoami)
```

- `tap0` is the virtual NIC.
- `mode tap` means Ethernet-like interface.
- `user` ensures your VM process (e.g., QEMU/KVM) can access the device.

### 2. Attach the TAP Device to the Bridge

```bash
sudo ip link set tap0 master br0
sudo ip link set tap0 up
```

Now, `tap0` is a bridge port just like a physical NIC. Anything connected to it is part of the bridge network (`br0`).

### 3. Launch Your VM with TAP Networking

If you’re using **QEMU/KVM** directly:

```bash
qemu-system-x86_64 \
  -m 2048 \
  -hda vm.img \
  -netdev tap,id=net0,ifname=tap0,script=no,downscript=no \
  -device virtio-net-pci,netdev=net0
```

- `-netdev tap`: Connects QEMU to `tap0`.
- `script=no`: Tells QEMU not to run legacy up/down scripts.
- `virtio-net-pci`: Fast virtual NIC (recommended).

### 4. Configure Networking Inside the VM

Inside the guest OS (e.g., Ubuntu):

- Use DHCP (auto-configure if `br0` has access to a DHCP server).
- Or assign a static IP on the appropriate subnet (`10.0.0.x/24` if routed, or whatever your LAN is if bridged to `eth0`).

## Comparison

| Mode         | L2 Bridging         | L3 Routed Subnet        |
|--------------|---------------------|--------------------------|
| Host uses    | `br0` for LAN       | `eth0` (LAN), `br0` (VM subnet) |
| VMs get IP   | From LAN DHCP       | From internal DHCP or static  |
| IP Forwarding| Not needed          | **Required**               |
| NAT Needed   | No                  | Optional (only if no static routes on LAN) |
| Isolation    | None (same LAN)     | Full (separate network)   |
| Routing      | Bridged             | Host-level router         |
