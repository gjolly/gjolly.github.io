---
date: 2025-09-25T11:30:00+01:00
title: "How I built an Ubuntu archive mirror using Cloudflare"
description: "A fast and modern way to create an package mirror."
tags: ["Debian", "Ubuntu", "Cloudflare"]
---


For a while, I wanted to set up an Ubuntu archive mirror using Cloudflare. It felt like a natural idea: the archive is a set of static files that could be easily cached, and Cloudflare is very good at caching files close to users around the world.

---

## What is an archive mirror?

If you have ever run `apt update` on Ubuntu, you have used the archive. It is a big collection of files: packages (`.deb` files) and index files (`Packages.gz`, `Release`, etc.) that tell `apt` what is available.

The structure was designed more than 20 years ago, before CDNs and large-scale caching were common. It is very stable, but not optimized for today’s internet.

If you want to see how it looks, you can browse [archive.ubuntu.com](http://archive.ubuntu.com) or read about the [Debian repository format](https://wiki.debian.org/DebianRepository/Format).

---

## My first idea: the “big sync”

My first plan was to copy the entire archive into **Cloudflare R2**, their low-cost object store. I thought I would write workers to parse the index files, detect changes, and keep R2 in sync with the upstream archive.

But this was heavy: the archive is very large, the initial storage without being too high would not be null and the code to make sure that the indices would stay in sync with what was available in the mirror would not be trivial. Indeed, if a index reference a package which has not been synced, it would cause the client to fail. All of that for a mirror that maybe only a few people would use.

---

## What I ended up doing: “lazy syncing”

Instead, I settled on something much simpler.

- When a client asks for a package file, my worker fetches it from the upstream archive, stores it in R2, and caches it in Cloudflare’s edge for 1 day.
- Index files are **not stored in R2**. They are just cached in Cloudflare for 30 minutes.

This way, I do not need to preload or parse the whole archive. Packages appear only if someone asks for them.

One of the drawback here is that every request hits the worker which add more cost but I figured that if it would become a problem later on, I could switch to my original idea..

---

## Putting it in production

As you might expect, getting this to production was not without issues.

At first, I made a mistake with a symlink that pointed `/ubuntu` back to itself. Yes, a recursive path! You can try it by yourself: http://archive.ubuntu.com/ubuntu/ubuntu/ubuntu/ubuntu, you can add as many `ubuntu` as you wish, it will always work because on the actual filesystem `ubuntu/ubuntu` is a symlink to itself! On a static file server, it might work with no problem but I certainly didn't want my object store to be filled with duplicated data.

My next "oops" moment was when I submitted the mirror to the official [Ubuntu archive mirrors list](https://launchpad.net/ubuntu/+archivemirrors), to my surprise, the probes all failed. Indeed, I had completely forgot to support **HEAD** requests

---
# Running the numbers

Let's take a random package of the archive: `mysql` and try to download it from the upstream archive maintained by Canonical, from my regional mirror and from my service:

```
$ curl -o /dev/null https://archive.ubuntu.com/ubuntu/pool/main/m/mysql-8.4/mysql-client-core_8.4.6-0ubuntu0.25.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 2083k  100 2083k    0     0  7569k      0 --:--:-- --:--:-- --:--:-- 7576k
$ curl -o /dev/null https://fr.archive.ubuntu.com/ubuntu/pool/main/m/mysql-8.4/mysql-client-core_8.4.6-0ubuntu0.25.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 2083k  100 2083k    0     0  5895k      0 --:--:-- --:--:-- --:--:-- 5885k
$ curl -o /dev/null https://ubuntu.gjolly.dev/ubuntu/pool/main/m/mysql-8.4/mysql-client-core_8.4.6-0ubuntu0.25.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 2083k  100 2083k    0     0  1510k      0  0:00:01  0:00:01 --:--:-- 1510k
```

As you can see (`average Dload`) my service is the worse, but wait! Let's try again now:
```
$ curl -o /dev/null https://ubuntu.gjolly.dev/ubuntu/pool/main/m/mysql-8.4/mysql-client-core_8.4.6-0ubuntu0.25.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 2083k  100 2083k    0     0  14.0M      0 --:--:-- --:--:-- --:--:-- 14.1M
```

Yay! Now that it's in the cache, we are twice as fast as the official archive.

Let's do that but with a bigger package, for example `linux-modules-extra-6.14.0-22-generic_6.14.0-22.22~24.04.1_amd64.deb` which is `114MB` big:

```
$ curl -o /dev/null https://archive.ubuntu.com/ubuntu/pool/main/l/linux-hwe-6.14/linux-modules-extra-6.14.0-22-generic_6.14.0-22.22~24.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  113M  100  113M    0     0  29.2M      0  0:00:03  0:00:03 --:--:-- 29.2M
$ curl -o /dev/null https://fr.archive.ubuntu.com/ubuntu/pool/main/l/linux-hwe-6.14/linux-modules-extra-6.14.0-22-generic_6.14.0-22.22~24.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  113M  100  113M    0     0  2294k      0  0:00:50  0:00:50 --:--:-- 5368k
$ curl -o /dev/null https://ubuntu.gjolly.dev/ubuntu/pool/main/l/linux-hwe-6.14/linux-modules-extra-6.14.0-22-generic_6.14.0-22.22~24.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  113M  100  113M    0     0  84.1M      0  0:00:01  0:00:01 --:--:-- 84.1M
```

But once again, after caching we get `134 MB/s`

```
$ curl -o /dev/null https://ubuntu.gjolly.dev/ubuntu/pool/main/l/linux-hwe-6.14/linux-modules-extra-6.14.0-22-generic_6.14.0-22.22~24.04.1_amd64.deb
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  113M  100  113M    0     0   134M      0 --:--:-- --:--:-- --:--:--  134M
```

---
## Why this design makes sense

This approach only works well if more than one person uses the mirror in each Cloudflare edge location. If I am the only one hitting it, then most requests will go all the way back to the upstream archive. But if many users share the same edge cache, the benefit grows quickly: new  packages are quickly added to the R2 bucket and stay cached on the edge, downloads become much faster: only the first client has to pay a "high" latency

So, if you want to try it, please do! The more people use it, the better it works.
