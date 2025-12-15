---
date: 2025-12-13T19:10:00Z
title: "Build an AI inference server on Ubuntu"
description: "Deploy local LLM inference with Ollama and Open WebUI"
tags: ["Linux", "Ubuntu", "AI", "LLM", "Docker", "NVIDIA"]
---
Open source tools like [Ollama](https://ollama.com/) and [Open WebUI](https://docs.openwebui.com/) are convenient for building local LLM inference stacks that let you create a ChatGPT-like experience on your own infrastructure. Whether you are a hobbyist, someone concerned about privacy, or a business looking to deploy LLMs on-premises, these tools can help you achieve that.

## Prerequisites

We assume here that you are running an LTS version of Ubuntu (NVIDIA and AMD tooling is best supported on LTS releases) and that you have a GPU installed on your machine (either NVIDIA or AMD). If you don't have a GPU, you can still follow this guide, but inference will be much slower as it will run on CPU.

## Making sure the system is up-to-date

As long as you use the latest kernels provided by Ubuntu, you can enjoy the pre-built NVIDIA drivers that come with the OS.

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

Removing old kernels is important to avoid pulling DKMS NVIDIA drivers during the installation.

## NVIDIA drivers installation (skip this step if you have an AMD GPU)

### Drivers

Install the NVIDIA drivers by following the instructions in this post: [How to install NVIDIA drivers on Ubuntu]({{< relref "nivdia-drivers.md" >}}).

### NVIDIA Container Toolkit

You will also need the NVIDIA container toolkit which is not available in the Ubuntu archive. Thus, you need to install the NVIDIA repository first:

```bash
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://nvidia.github.io/libnvidia-container/gpgkey | gpg --dearmor | sudo tee /etc/apt/keyrings/nvidia-container-toolkit-keyring.gpg > /dev/null
curl -s -L https://nvidia.github.io/libnvidia-container/stable/deb/nvidia-container-toolkit.list | sed 's#deb https://#deb [signed-by=/etc/apt/keyrings/nvidia-container-toolkit-keyring.gpg] https://#g' | sudo tee /etc/apt/sources.list.d/nvidia-container-toolkit.list > /dev/null
```

Then, install the toolkit:

```bash
sudo apt update
sudo apt install -y nvidia-container-toolkit
```

> **Note**: You can find a detailed guide about this section on the [NVIDIA documentation](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html).

### Verify the installation

You can verify that the installation was successful by running: `nvidia-smi`.
If everything is working correctly, you should see the output of `nvidia-smi` showing your GPU information.

## AMD drivers installation (NVIDIA users can skip this section)

### Drivers

The `amdgpu` drivers are included in the Linux kernel modules on Ubuntu and should work out of the box. To make sure you have them installed, run:

```bash
apt list --installed | grep linux-modules-extra
```

If you don't see any output, it's either because you are running `linux-virtual` (a lightweight kernel bundle for VMs) or because you are running a cloud kernel flavor that doesn't include extra modules by default.

If you are on a cloud, install the appropriate extra modules package for your kernel flavor. For example, on AWS, you would run:

```bash
sudo apt install -y linux-modules-extra-aws
```

If you are not running on a cloud, install either `linux-generic` or `linux-generic-hwe-24.04` (or `-22.04` if you are using Ubuntu 22.04 LTS) depending on whether you are using the HWE kernel or not:

```bash
sudo apt install -y linux-generic
# or
sudo apt install -y linux-generic-hwe-24.04
```

### AMD Container Toolkit

Since we're using Docker for the LLM inference server, the ROCm toolkit (like the CUDA toolkit for NVIDIA) will be included in the container image, so there's nothing to install.

However, just like with NVIDIA, you need to configure Docker to use the AMD GPU. To do this, install the AMD container toolkit repository:

```bash
sudo mkdir -p /etc/apt/keyrings
wget https://repo.radeon.com/rocm/rocm.gpg.key -O - | gpg --dearmor | sudo tee /etc/apt/keyrings/rocm.gpg > /dev/null
source /etc/os-release
echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/rocm.gpg] https://repo.radeon.com/amd-container-toolkit/apt/ ${VERSION_CODENAME} main" | sudo tee /etc/apt/sources.list.d/amd-container-toolkit.list
```

Then, install the toolkit:

```bash
sudo apt update
sudo apt install -y amd-container-toolkit
```

More information can be found on the [AMD ROCm documentation](https://instinct.docs.amd.com/projects/container-toolkit/en/latest/container-runtime/quick-start-guide.html#step-3-configure-repositories).

## Installing Docker

To install Docker on your machine, follow [the official documentation from Docker](https://docs.docker.com/engine/install/ubuntu/#install-using-the-repository).

Once done, if you are using an NVIDIA container, run the following command to configure Docker:

```bash
sudo nvidia-ctk runtime configure --runtime=docker
sudo systemctl restart docker
```

After running this command, you should find something like this in `/etc/docker/daemon.json`:

```json
{
    "runtimes": {
      "nvidia": {
        "args": [],
        "path": "nvidia-container-runtime"
      }
    }
}
```

Similarly, for AMD GPUs, run:

```bash
sudo amd-ctk runtime configure
sudo systemctl restart docker
```

and you should find something like this in `/etc/docker/daemon.json`:

```json
{
    "runtimes": {
      "amd": {
        "path": "amd-container-runtime",
        "runtimeArgs": []
      }
    }
}
```

## Installing Ollama and Open WebUI

Ollama is the server that will be running the LLMs and Open WebUI is the ChatGPT-like UI to chat with the model.

Create a `compose.yml` file with the following content:

```yaml
services:
  ollama:
    # use ollama/ollama:rocm for AMD GPU
    image: ollama/ollama
    volumes:
      - ollama:/root/.ollama
    container_name: ollama
    pull_policy: always
    tty: true
    restart: unless-stopped
    # set "amd" for AMD GPU
    runtime: nvidia
    gpus: all

  open-webui:
    image: ghcr.io/open-webui/open-webui:main
    container_name: open-webui
    volumes:
      - openwebui:/app/backend/data
    pull_policy: always
    depends_on:
      - ollama
    ports:
      - 127.0.0.1:8080:8080
    environment:
      OLLAMA_BASE_URL: http://ollama:11434
      WEBUI_URL: http://localhost:8080
    restart: unless-stopped

volumes:
  ollama:
  openwebui:
```

Simply run `docker compose up -d`, and you should be able to open [http://localhost:8080](http://localhost:8080) in your favorite web browser and start chatting with your model!

But wait, you don't have any model yet!

### Downloading a model

You can download models directly from the `ollama` container. For example, to download the `llama2` model, run:

```bash
docker compose exec -it ollama ollama pull llama2
```

This will download the model inside the `ollama` container and make it available for inference.

You can also list available models by running:

```bash
docker compose exec -it ollama ollama list
```

or check the [Ollama model repository](https://ollama.com/models) for more models. **Make sure the size of the model fits in your GPU memory!** For example, `llama2` requires at least 4GB of GPU memory. You can check your GPU memory by running `nvidia-smi` or `btop`.

> **Note**: The first time a model is used, it might take a bit longer to respond as it needs to be loaded into GPU memory.

## Maintenance

To update the Ollama and Open WebUI images, simply run:

```bash
docker compose pull
docker compose up -d
```

To keep the NVIDIA drivers up-to-date and never pull the DKMS packages, follow the instructions in this post: [How to install NVIDIA drivers on Ubuntu]({{< relref "nivdia-drivers.md" >}}).

## Troubleshooting

I find that the best way to monitor the GPU usage is to use `btop`. If you have `nvidia-smi` or `rocm-smi` installed, `btop` will show you the GPU usage in its UI.

One of the first things to check if you think that the GPU is not being used is the logs from the `ollama` container:

```bash
docker compose logs -f ollama
```

and to look for something like:

```
level=INFO source=types.go:42 msg="inference compute" id=GPU-8c5284c3-6336-84e6-f91e-ba027e8d440b filter_id="" library=CUDA compute=8.9 name=CUDA0 description="NVIDIA L4" libdirs=ollama,cuda_v13 driver=13.0 pci_id=0000:31:00.0 type=discrete total="22.5 GiB" available="22.0 GiB"
[...]
llama_model_load_from_file_impl: using device CUDA0 (NVIDIA L4) (0000:31:00.0) - 22560 MiB free
[...]
load_tensors: offloading 32 repeating layers to GPU
```

If you see that the model is being loaded on CPU instead of GPU, then there is probably something wrong with your NVIDIA or AMD container toolkit or drivers installation.

Check that your card is visible either by running `nvidia-smi`, `rocm-smi` or `btop` on the host machine. If it is not, then the problem is with your drivers installation.

## References

- [Ollama documentation](https://docs.ollama.com/docker)
- [Open-webui documentation](https://docs.openwebui.com/)
- [NVIDIA Container Toolkit installation guide](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html)
- [Ubuntu Kernel cycles](https://ubuntu.com/about/release-cycle?product=ubuntu-kernel&release=ubuntu+kernel&version=all)