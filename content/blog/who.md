+++
date = 2020-08-31T11:40:00Z
title = "The UNIX `who` command"
description = "Understanding where `who` gets its data"
slug = ""
tags = ["Linux", "utmp", "UNIX"]
categories = []
externalLink = ""
series = []
+++

# Who am I?

Have you ever wondered how the `who` command works? No? Ok let's check that anyway.

## What is `who`

Let's start with the very basic. `who` allows a user to list the user logged on the system. It can even tell you who you are among those users.
On my machine:
```
$ who
gauthier tty2         2020-08-30 15:06 (tty2)
gauthier pts/1        2020-08-30 15:06 (tmux(1555).%0)
gauthier pts/2        2020-08-30 16:41 (tmux(1555).%6)
gauthier pts/4        2020-08-30 15:57 (tmux(1555).%3)
```
It tells me that I am logged on the "physical" terminal `tty2` and on three pseudo terminals. Indeed my current session of Gnome Shell is running on `tty2` and I have 3 `tmux` windows open.

But where is it getting those information. At first I thought it would be from a file of the `proc` filesystem, but `strace` told me an other story.

## A bit of reverse engineering

In order to see what `who` is doing I could try to find the source code and dig into it. But like most of us, I hate reading someone else code and it was easier for me to just run `strace` against the `who` process. Since we are expecting `who` to read system files, we can only focus on `open` and `stat` syscalls.

```
$ strace who 2>&1 | grep open
openat(AT_FDCWD, "/etc/ld.so.cache", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/libc.so.6", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/locale/locale-archive", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/var/run/utmp", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/etc/localtime", O_RDONLY|O_CLOEXEC) = 3
```

Now we need to filter what's intresting and what's not. The first files three files `/etc/ld.so.cache`, `/usr/lib/libc.so.6`, `/usr/lib/locale/locale-archive` are common C libraries that are not iteresting for us. Then it is opening `/var/run/utmp` and `/etc/localtime`.
At this point we could just go on the internet and figure out what are those files, but let's try to just use the `man` pages first:
```
$ man -wK `/etc/localtime`
```
Sends us to `localtime(5)` which explains:
```
The /etc/localtime file configures the system-wide timezone of the local system that is used by applications for presentation to the user.
```
Very well so probably `who` uses this file (or uses a function that is using this file) to find out how to print the login timestamp (columns 4 and 5 of `who`'s output).

Now let's do the same for `/var/run/utmp`:
```
$ man -wK `/var/run/utmp`
```
A lot of man pages are referencing `/var/run/utmp`, even `who(5)` is. Unfortunatly, it doesn't say what this file is storing. However, if we check `utmp(5)`:
```
The  utmp  file  allows  one to discover information about who is currently using the system.  There may be more users currently using the system, because not all programs use utmp
logging.
```
Great! So that's where `who` is getting its data. However, we can't direcly read this file:
```
The file is a sequence of utmp structures, declared as follows in <utmp.h> (note that this is only one of several definitions around; details depend on the version of libc):
```

At this point we understood what `who` is doing: it is reading `/var/run/utmp`, parsing the content and displaying it nicely. Let's see if we can reproduce this simple behaviour.

### My own `who`

What we simply need to do is: open `/var/run/utmp`, read n bytes (where n is the size of the utmp structure), print the info contained in each structure, continue until we reach the end of the file. With a bit of formating, we can even make it look like the original `who` command.

```
#include <utmp.h>
#include <stdlib.h>
#include <stdio.h>
#include <time.h>

int main() {
  // open the file
  FILE * file= fopen("/var/run/utmp", "rb");

  // just for safety
  if (file == NULL) {
    return 1;
  }

  // initialize the utmp structure
  struct utmp entry;

  // read the entries from the file one by one
  while (fread(&entry, sizeof(struct utmp), 1, file) != 0) {
    if (entry.ut_type != USER_PROCESS)
      continue;

    // format the date (remember who uses the /etc/localtime?)
    char date[80];
    time_t raw_time = entry.ut_tv.tv_sec;
    struct tm *ts = localtime(&raw_time);
    strftime(date, sizeof(date), "%Y-%m-%d %H:%M", ts);

    // print the output for this entry, tries to mock who's output
    printf("%-8s %-12s %s (%s)\n", entry.ut_user, entry.ut_line, date, entry.ut_host);
  }

  fclose(file);
}
```

```
$ clang -o who who.c
$ ./who
gauthier tty2         2020-08-31 10:02 (tty2)
gauthier pts/1        2020-08-31 10:03 (tmux(2220).%0)
gauthier pts/2        2020-08-31 10:08 (tmux(2220).%1)
gauthier pts/3        2020-08-31 10:33 (tmux(2220).%5)
```

TADA!

Of course, this only mocks the most basic behaviour of who and doesn't handle any option, like the famous `who am i` or `who mom hates`.

### Going further

There is still a lot to say about who. We could for example mention `lastlog` command and its corresponding file `/var/log/wtmp`, dig into the `utmp` structure, or just try to understand what `utmp` stands for.
