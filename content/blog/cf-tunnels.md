---
date: 2025-12-16T15:30:00Z
title: "Exposing a local web server using Cloudflare Tunnels"
description: "Make your local web server accessible from the internet using Cloudflare Tunnels"
tags: ["Cloudflare"]
showtoc: false
---
## Context

I often run into this problem: I have a local file on my computer that I want to share with a friend or colleague on the other side of the world. While I could upload it to a file sharing service, it's can be very annoying to have to upload it somewhere first, especially if it's a one-off situation and if the file is big. More over, it's my data and I don't necessarily want to upload it to a Google or Dropbox server.
Similarly, when I'm developing a web application on my local machine, I often want to show it to someone else for testing or feedback. Again, uploading it to a public server can be cumbersome and I don't want to start dealing with a deployment strategy if I just stated prototyping.

If you ran into similar situations, you might have heard of [ngrok](https://ngrok.com/), a popular tool to expose local servers to the internet. However, ngrok's free tier is quite limited and requires you to trust a third party service with your data.

An alternative to ngrok is [Cloudflare Tunnels](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/), a service provided by Cloudflare that allows you to securely expose your local web server to the internet without having to open any ports on your router or firewall. The best part is that it's free for personal use and you can use it with your own domain name if you have one already registered with Cloudflare.

## One-liner with Docker and try-cloudflare.com

What you just need is:
- A local web server running on your machine. This can be anything from a simple static file server to a complex web application. eg, a Python server running on `8000`:
  ```bash
  python3 -m http.server 8000
  ```
- Docker installed on your machine. You can find installation instructions [here](https://docs.docker.com/get-docker/).

And then run:

```bash
docker run --rm --network host cloudflare/cloudflared:latest tunnel --url http://localhost:8000
```

This command will start a Cloudflare Tunnel that forwards traffic from a public URL to your local web server running on `http://localhost:8000`. After a few seconds, you should see output similar to this:

```bash
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):  |
|  https://component-cameras-assists-brad.trycloudflare.com                                  |
+--------------------------------------------------------------------------------------------+
```

Voila! But be careful: now anyone who visits the tunnel address will be able to access your local web server. Since domain names are public, make sure you don't use sensitive information in the URL. If you want to stop sharing your server, just kill the script (Ctrl+C) and the tunnel will be closed.

## Using your own domain name

If you want to use your own domain name instead of the default `try-cloudflare.com`, you will need a Cloudflare account and a script that is a bit more sophistictated but still easy to use. I've made a script for myself (and for you) that automates everything. Save this script bellow as `create-tunnel.sh`, make it executable with `chmod +x create-tunnel.sh`, and run it with the local URL of your web server as an argument:

```bash
./create-tunnel.sh http://localhost:8000
```

### create-tunnel.sh

```bash
#!/bin/bash -eu
# Create and run a Cloudflare tunnel using Docker.
# Usage: ./create-tunnel.sh <local-url>

# Check if docker is available
if ! command -v docker > /dev/null; then
    echo "please install docker"
    exit 1
fi

url="$1"

# TODO: make this configurable
domain=tunnel
tunnel_name=tunnel-to-local-machine

config_directory="$HOME/.config/tunnel"
mkdir -p "$config_directory"

CLOUDFLARED=(
    docker run --rm
    --network host
    --name cloudflared
    --user 0
    -v "$config_directory:/root/.cloudflared"
    cloudflare/cloudflared:latest
)

# The first time we run this, we need to login to Cloudflare
# to authorize the tunnel.
if [ ! -f "${config_directory}/cert.pem" ]; then
    "${CLOUDFLARED[@]}" tunnel login
fi

tunnel_id="$("${CLOUDFLARED[@]}" tunnel list \
        --output json \
            | jq -r ".[] | select(.name == \"${tunnel_name}\") | .id")"

# Create the tunnel if it doesn't already exist
if [ -z "${tunnel_id}" ] ; then
    "${CLOUDFLARED[@]}" tunnel create "$tunnel_name"
    tunnel_id="$("${CLOUDFLARED[@]}" tunnel list \
        --output json \
            | jq -r ".[] | select(.name == \"${tunnel_name}\") | .id")"
fi

# Fetch the credentials file if it doesn't already exist locally
if [ ! -f "${config_directory}/${tunnel_id}.json" ]; then
    "${CLOUDFLARED[@]}" tunnel \
        --credentials-file "/root/.cloudflared/${tunnel_id}.json" \
        token "$tunnel_id"
fi

# This will do nothing if the DNS record already points to the tunnel
# and fail if the domain is already taken.
"${CLOUDFLARED[@]}" tunnel route dns "$tunnel_name" "$domain"

# Finally, run the tunnel
"${CLOUDFLARED[@]}" \
    --loglevel info \
    tunnel run \
    --url "$url" \
    --credentials-file "/root/.cloudflared/${tunnel_id}.json" \
    "$tunnel_name"
```