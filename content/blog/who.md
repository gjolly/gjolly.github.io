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

While working on a completely different project, I started to ask myself how the `who` command was working under the hood. In the end, I thought it was a good topic for a blog post.

## Who is `who`

Let's start with the basics.  the `who` command allows you to list the users currently logged on the system.
For example, on my machine:

```bash
$ who
gauthier tty2         2020-08-30 15:06 (tty2)
gauthier pts/1        2020-08-30 15:06 (tmux(1555).%0)
gauthier pts/2        2020-08-30 16:41 (tmux(1555).%6)
gauthier pts/4        2020-08-30 15:57 (tmux(1555).%3)
```

It tells me that I am logged on the "physical" terminal `tty2` and on three pseudo terminals. Indeed my current session of Gnome Shell is running on `tty2` and I have 3 `tmux` windows open.

But where is it getting those information? Probably from a file as everything is a file with Linux, but let's check which one and how the data is stored there.

## A bit of reverse engineering

In order to see what the `who` command is doing I could try to find the source code and dig into it. But I found it fun to use `strace` to check what the process was doing instead. Since we are expecting `who` to read system files, we can only focus on the `open` syscalls.

```bash
$ strace who 2>&1 | grep open
openat(AT_FDCWD, "/etc/ld.so.cache", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/libc.so.6", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/locale/locale-archive", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/var/run/utmp", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/etc/localtime", O_RDONLY|O_CLOEXEC) = 3
```

We can quickly filter what is interesting and what is not. The first files two files `/etc/ld.so.cache`, `/usr/lib/libc.so.6` are shared libraries loaded by the process, those are interesting us.

Then `/usr/lib/locale/locale-archive`, `/var/run/utmp` and `/etc/localtime` are opened. Let's see what those files are storing.

When exploring this kind of topics, it is always interesting to first search into the man pages before starting browsing the web. The 5th section of the manual is dedicated to "file formats and conventions" and seems a good place to start.

### locale-archive

```bash
$ man -wK 5 '/usr/lib/locale/locale-archive'
```

Sends us to `locale(5)` where we can read:

```bash
The  locale  definition file contains all the information that the localedef(1) command needs to convert it into the binary locale data‐base.
```

The page also sends us to `locale(7)` for more explanation about those informations:

```bash
A  locale is a set of language and cultural rules. These cover aspects such as language for messages, different character sets, lexico‐graphic conventions, and so on. A program needs to be able to determine its locale and act accordingly to be portable to different cultures.
```

So the `who` command read from this file, probably using the `setlocale(3)` function, to find out how the information should be formated and displayed.

We can actually check it:

```bash
$ LC_ALL='fr_FR.utf8' who
gauthier tty2         Sep  2 11:38 (:1)
gauthier pts/1        Sep  2 12:11 (tmux(2445).%0)
gauthier pts/2        Sep  2 12:37 (tmux(2445).%1)
gauthier pts/3        Sep  2 13:04 (tmux(2445).%2)
```

Indeed, the date is is not formated the same way!

### localtime

```bash
$ man -wK 5 '/etc/localtime'
```

Sends us to `localtime(5)` which explains:

```bash
The /etc/localtime file configures the system-wide timezone of the local system that is used by applications for presentation to the user.
```

Probably `who` uses this file (or uses a function that is using this file) to print timestamps (columns 4 and 5 of `who`'s output) using the correct timezone configured by the user.

### utmp

Finally comes `/var/run/utmp`:

```bash
$ man -wK 5 '/var/run/utmp'
/usr/share/man/man5/utmp.5.gz
```

Where we can read:

```bash
The  utmp  file  allows  one to discover information about who is currently using the system.  There may be more users currently using the system, because not all programs use utmp logging.
```

Great! We found where the `who` command is getting its data. It would be nice to be able to read this file to get those data without using the `who` command. Unfortunately:

```bash
The file is a sequence of utmp structures, declared as follows in <utmp.h> (note that this is only one of several definitions around; details depend on the version of libc):
```

At this point we understood what the `who` command is doing: it is reading `/var/run/utmp`, parsing the content and formating it nicely. Let's see if we can reproduce this simple behavior.

### My own `who`

What we simply need to do is: open `/var/run/utmp`, read `n` bytes (where `n` is the size of the `utmp` structure), print the info contained in each structure, continue until we reach the end of the file. With a bit of formating, we can even make it look like the original `who` command.

```c
#include <utmp.h>
#include <stdlib.h>
#include <stdio.h>
#include <time.h>
#include <locale.h>

int main() {
  // set the right locale to display the information nicely
  setlocale(LC_ALL, "");

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

```bash
$ clang -o who who.c
$ ./who
gauthier tty2         2020-08-31 10:02 (tty2)
gauthier pts/1        2020-08-31 10:03 (tmux(2220).%0)
gauthier pts/2        2020-08-31 10:08 (tmux(2220).%1)
gauthier pts/3        2020-08-31 10:33 (tmux(2220).%5)
```

TADA!

We can also check with `strace` if the behavior is the same:

```bash
$ strace ./who 2>&1 | grep -e open
openat(AT_FDCWD, "/etc/ld.so.cache", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/libc.so.6", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/usr/lib/locale/locale-archive", O_RDONLY|O_CLOEXEC) = 3
openat(AT_FDCWD, "/var/run/utmp", O_RDONLY) = 3
openat(AT_FDCWD, "/etc/localtime", O_RDONLY|O_CLOEXEC) = 4
```

Indeed our program is doing the same as the original `who`.

Of course, this only mocks the most basic features of the `who` command and doesn't handle any option, like the famous `who am i` or `who mom hates`.

### Going further

There is still a lot to say about the `who` command. We could for example mention the `lastlog` command and its corresponding file `/var/log/wtmp`, dig into the `utmp` structure, or just try to understand what `utmp` stands for.
