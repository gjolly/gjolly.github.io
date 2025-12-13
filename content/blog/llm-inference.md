---
date: 2025-12-13T19:10:00Z
title: "Build an AI inference server on Ubuntu"
description: "Deploy local LLM inference with ollama and open-webui"
tags: ["Linux", "Ubuntu", "AI", "LLM", "Docker", "NVIDIA"]
---
Open source tools like [Ollama](https://ollama.com/) and [Open WebUI](https://docs.openwebui.com/) are convenient for building local LLM inference stacks that let you create a ChatGPT-like experience on your own infrastructure. Whether you are a hobbyist, someone concerned about privacy, or a business looking to deploy LLMs on-premises, these tools can help you achieve that. To start, the only thing you need is a server or PC running Ubuntu and a GPU for faster inference results.

## Making sure the system is up-to-date

As long as you use the latest kernels provided by Ubuntu, you can enjoy the pre-built NVIDIA drivers that come with the OS.

First make sure your server is up-to-date:

```bash
sudo apt update
sudo apt full-upgrade -y
```

If your system needs reboot, reboot it before running:

```bash
sudo apt autoremove
```

You can check if your system needs to be rebooted by checking if this file exists: `/var/run/reboot-required`.

## Installing the NVIDIA driver (skip this step if you have an AMD GPU)

### Drivers

Install the NVIDIA drivers by following the instructions in this post: [How to install NVIDIA drivers on Ubuntu]({{< relref "nivdia-drivers.md" >}}).

### NVIDIA Container Toolkit

You will also need the NVIDIA container toolkit which is not available in the Ubuntu archive. Thus, you need to install the NVIDIA repository first:

```bash
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://nvidia.github.io/libnvidia-container/gpgkey | gpg --dearmor | sudo tee /etc/apt/keyrings/nvidia-container-toolkit-keyring.gpg > /dev/null
curl -s -L https://nvidia.github.io/libnvidia-container/stable/deb/nvidia-container-toolkit.list | sed 's#deb https://#deb [sig
ned-by=/etc/apt/keyrings/nvidia-container-toolkit-keyring.gpg] https://#g' | sudo tee /etc/apt/sources.list.d/nvidia-container-toolkit.list > /dev/null
```

Then, install the toolkit:

```bash
sudo apt update
sudo apt install -y nvidia-container-toolkit
```

> Note: You can find a detailed guide about this section on the NVIDIA documentation
> https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html

### Verify the installation

You can verify that the installation was successful by running: `nvidia-smi`.
If everything is working correctly, you should see the output of `nvidia-smi` showing your GPU information.

## Install AMD drivers (NVIDIA users can skip this section)

The `amdgpu` drivers are included in the Linux kernel and should work out of the box. Since we're using Docker for the LLM inference server, the ROCm toolkit (like the CUDA toolkit for NVIDIA) will be included in the container image, so there's nothing to install.

## Install Docker

Install Docker on your machine by following this documentation: https://docs.docker.com/engine/install/ubuntu/#install-using-the-repository.

Once done, if you are using an NVIDIA container, run the following command to configure Docker:

```bash
sudo nvidia-ctk runtime configure --runtime=docker
sudo systemctl restart docker
```

After running this command, you should find something like this in `/etc/docker/daemon.json`:

```bash
{
    "runtimes": {
      "nvidia": {
        "args": [],
        "path": "nvidia-container-runtime"
      }
    }
}
```

## Installing `ollama` and `open-webui`

`ollama` is the server that will be running the LLMs and `open-webui` is the chatgpt-like UI to chat with the model.

Create a `docker-compose.yml` file with the following content:

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
    gpus: all

  open-webui:
    image: ghcr.io/open-webui/open-webui:main
    container_name: open-webui
    volumes:
      - openwebui:/app/backend/data
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

> **Note**: For AMD GPU users, replace `gpus: all` with
> ```yaml
> devices:
>   - /dev/kfd
>   - /dev/dri
> ```
> and the `ollama` image by `ollama/ollama:rocm`.

Simply run `docker-compose up -d`, and you should be able to open [http://localhost:8080](http://localhost:8080) in your favorite web browser and start chatting with your model!


But wait, you don't have any model yet!

## Downloading a model

You can download models directly from the `ollama` container. For example, to download the `llama2` model, run:

```bash
docker exec -it ollama ollama pull llama2
```

This will download the model inside the `ollama` container and make it available for inference.

You can also list available models by running:

```bash
docker exec -it ollama ollama list
```

or check the [Ollama model repository](https://ollama.com/models) for more models. *Make sure the size of the model fits in your GPU memory!* For example, `llama2` requires at least 4GB of GPU memory. You can check your GPU memory by running `nvidia-smi` or `btop`.

## Maintenance

To update the `ollama` and `open-webui` images, simply run:

```bash
docker-compose pull
docker-compose up -d
```

To keep the NVIDIA drivers up-to-date and never pull the DKMS packages, follow the instructions in this post: [How to install NVIDIA drivers on Ubuntu]({{< relref "nivdia-drivers.md" >}}).

# References

- [Ollama documentation](https://docs.ollama.com/docker)
- [Open-webui documentation](https://docs.openwebui.com/)
- [NVIDIA Container Toolkit installation guide](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html)
- [Ubuntu Kernel cycles](https://ubuntu.com/about/release-cycle?product=ubuntu-kernel&release=ubuntu+kernel&version=all)