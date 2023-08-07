---
date: 2023-08-07T11:10:00Z
title: "User temporary directory"
description: "A temporary directory in the user home folder"
tags: ["Linux", "systemd", "Ubuntu"]
---

## `systemd-tmpfiles`

`/tmp` and other temporary directories and files are now managed by `systemd` and are not `tmpfs`. `sytemd-tmpfiles` gives to the user the ability to choose what they want to do with temporary directories/files. There are a ton of options that the user can choose from and everything is managed though config files (see `man tmpfiles.d`).

## Create a temporary directory in your HOME folder

Using the global `/tmp` directory can be security issue as anyone can read this directory. If the user is not very carefull with the permissions they set on their files, confidential information might leak. Also, programs packaged in `snap` cannot access the global temporary directory `/tmp` by default.

To create a temporary directory in your HOME, write a config file like this one in `$HOME/.config/user-tmpfiles.d/tmp.conf`:

```
# Delete the content of ~/tmp on reboot
D %h/tmp 0750 - - -
```

and enable the following user service:

```bash
systemctl --user status systemd-tmpfiles-setup.service
```

Now everytime the user login, `$HOME/tmp` will be cleaned (or created if needed). For the config to take effect immediately, you can run `systemd-tmpfiles --user --create` to create the directory and `systemd-tmpfiles --user --remove` to cleanup the directory.

If you don't like to see your `Downloads` becoming more of a mess day after day, you can also create the following rule:

```
e %h/Downloads 0755 - - 30d
```

Then, files older than 30 days will be removed automatically.
