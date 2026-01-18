---
date: 2026-01-14T15:30:00Z
title: "Attestable Immutable Nodes for Kubernetes"
description: "How immutable operating systems and Confidential Computing can provide a trustworthy foundation for Kubernetes worker nodes"
tags: ["Confidential Computing", "Linux", "Kubernetes"]
---

## Rethinking the Trust Boundary of Kubernetes Nodes

Most Kubernetes security mechanisms implicitly assume that worker nodes are trustworthy. In practice, this assumption is weak. The operating systems running underneath Kubernetes are often mutable, difficult to audit, and only loosely tied to what was originally provisioned. Even when containers are well isolated and supply chains are secured, a compromised or drifted node OS undermines the entire stack.

A more robust approach is to treat the node operating system as a **security boundary**, not just a runtime dependency. This is where immutable and attestable operating systems become relevant. By making the OS immutable and cryptographically verifiable, Kubernetes can rely on a foundation whose integrity is provable rather than assumed.

---

## The Android Analogy: A Proven Security Model

A useful comparison can be made with the Android ecosystem. Android devices rely on an immutable, verified operating system image that is measured during boot. Applications are isolated and distributed independently, but they always execute on top of a system image whose integrity can be verified through hardware-backed attestation.

Kubernetes already mirrors the Android model at the application level. Workloads are packaged as OCI containers, deployed declaratively, and isolated using kernel primitives. What is missing is the equivalent of Android’s verified system image for Kubernetes worker nodes.

In an immutable Kubernetes node model, the cluster of operating systems plays the same role as Android’s system image, while Kubernetes workloads remain OCI containers. The result is a clean separation: the OS provides a verifiable execution substrate, and applications remain portable and replaceable.

---

## Immutability as a Baseline, Not the End Goal

Immutability ensures that once a node boots, its operating system cannot be modified through conventional means, including package management or ad-hoc configuration changes. Updates happen by replacing the entire image rather than mutating the running system. This significantly reduces configuration drift and limits the impact of post-boot compromise.

However, immutability alone does not establish trust. A node may be immutable and still boot an unknown or malicious image. To build a meaningful security foundation, immutability must be paired with **attestation**.

---

## TPM-Backed Attestation

A Trusted Platform Module (TPM) is a hardware component that provides cryptographic capabilities for secure boot and attestation. During the boot process, the TPM measures each component loaded—firmware, bootloader, kernel, initramfs, and configuration—by computing and storing cryptographic hashes in its Platform Configuration Registers (PCRs).

These measurements form a chain of trust: each boot stage measures the next before transferring control to it. Because the TPM is a dedicated hardware component, these measurements cannot be forged by software running on the system. Once the system is fully booted, the PCR values represent a cryptographic summary of exactly what was loaded.

Remote attestation leverages these measurements. The TPM can produce a signed quote containing PCR values, which an external verifier can check against expected values. This allows the verifier to confirm not only that a node booted successfully, but precisely *what* it booted.

---

## Confidential Computing: When Your Node is a VM

But what happens if your node is a VM and there is no physical TPM available?

This is where Confidential Computing provides a solution. When Kubernetes worker nodes run as Confidential Virtual Machines, their memory is protected by hardware-enforced isolation (using technologies like AMD SEV-SNP or Intel TDX), and a virtual TPM (vTPM) operates inside the VM's Trusted Execution Environment.

![vTPM in Confidential VM](/images/vtpm-in-confidential-vm-diagram.png)
*Image credit: [Microsoft](https://learn.microsoft.com/fr-fr/azure/confidential-computing/virtual-tpms-in-azure-confidential-vm)*

This vTPM is not merely a software abstraction. Its state and keys are shielded from the host and cloud operator, protected by the same hardware isolation that secures the VM's memory. It can produce cryptographic evidence about the VM's boot process that is verifiably bound to the hardware Trusted Execution Environment.

As a result, measurements collected during boot can be trusted as originating from the node itself rather than from the cloud provider or hypervisor. This extends the TPM-based attestation model to virtualized environments while maintaining the same security properties.

---

## Making Attestation Durable with UKIs and dm-verity

For attestation to be meaningful, measurements must correspond to the system that continues running after boot. Measuring a kernel and initramfs is insufficient if the root filesystem can later be altered without detection.

A practical solution combines Unified Kernel Images with dm-verity. A UKI packages the kernel, initramfs, and kernel command line into a single, signed, and measurable artifact. This ensures that early boot components and boot parameters are cryptographically bound together.

The root filesystem is protected using dm-verity, with its root hash embedded directly in the kernel command line. Because the command line is part of the UKI, it is included in the boot measurements recorded by the TPM. At runtime, dm-verity enforces the integrity of the root filesystem: any modification results in I/O errors rather than silent corruption.

![Immutable Attestable Node Architecture](/images/immutable-attestable-node-architecture.svg)

This design has an important consequence. **Launch-time attestation remains valid during runtime**, because the system cannot diverge from the measured state without being detected. The node is not only attestable at boot, but continuously constrained to the attested configuration.

---

## Alignment with Current Cloud Provider Efforts

This approach is consistent with what major cloud providers are already attempting with attestable virtual machine images and TPM-backed measurement pipelines. The use of hardware-assisted isolation and TPM-based attestation, such as those exposed through Nitro-based platforms, reflects the same core idea: the VM image should be a verifiable security boundary.

Applying this model explicitly to Kubernetes worker nodes simply acknowledges that the node OS is a critical part of the trusted computing base, not an implementation detail.

---

## Current Limitations

Despite the maturity of the underlying technologies, Kubernetes itself does not yet integrate remote attestation into its node lifecycle. There is no native mechanism to verify attestation evidence during node provisioning, nor to make scheduling or admission decisions based on node integrity claims. Existing solutions rely on custom bootstrap logic and external verification services.

Another limitation is the reliance on cloud-provided firmware and TEE implementations. While Confidential Computing significantly raises the bar, the initial root of trust is still controlled by the cloud provider. This constrains transparency and limits portability across environments.

---

## Beyond CPUs: Toward Confidential AI Inference

The same architectural principles extend naturally to accelerators. GPUs are increasingly gaining confidential execution capabilities, including protected memory and attestation support. When combined with an immutable, attestable node OS, this opens the door to end-to-end confidential AI inference.

In such a model, the operating system, Kubernetes runtime, AI framework, and GPU execution context can all be verified. This enables strong guarantees for sensitive inference workloads, where both models and data must remain protected even from infrastructure operators.

![Confidential AI Inference Topology](/images/example-topology-4-gpu.png)
*Image credit: [NVIDIA](https://developer.nvidia.com/blog/confidential-computing-on-h100-gpus-for-secure-and-trustworthy-ai/)*


---

## Conclusion

Bringing an Android-like security model to infrastructure is a significant step. The constraints are different, the environments are more heterogeneous, and the trust boundaries are harder to define. Still, the building blocks are starting to align. Confidential Computing, hardware-backed isolation, and TPMs operating inside TEEs make it increasingly practical to reason about node integrity in concrete terms.

While Kubernetes does not yet fully integrate these mechanisms, the direction is clear. As confidential VMs and attestable execution environments become more widely available, treating the node OS as a verifiable foundation rather than an opaque substrate appears less experimental and more like a natural evolution of infrastructure security.

---

## References

- [Microsoft: Virtual TPMs in Azure Confidential VMs](https://learn.microsoft.com/fr-fr/azure/confidential-computing/virtual-tpms-in-azure-confidential-vm)
- [NVIDIA: Confidential Computing on H100 GPUs for Secure and Trustworthy AI](https://developer.nvidia.com/blog/confidential-computing-on-h100-gpus-for-secure-and-trustworthy-ai/)
- [Linux Kernel: dm-verity](https://www.kernel.org/doc/html/latest/admin-guide/device-mapper/verity.html)
- [UAPI Group: Unified Kernel Image (UKI)](https://uapi-group.org/specifications/specs/unified_kernel_image/)
- [AWS Attestable AMIs](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/attestable-ami.html)