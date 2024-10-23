# 💿 makemkv-auto-php-ripper

This tool can help you automatize your DVD ripping process with the `makemkvcon` program

[`autorip`](./autorip) is a script that executes this loop :
- Wait for a dvd in specified dvd drive (`/dev/sr0` by default)
- Retrieve dvd name
- Rip all video content which is longer than 10 minutes inside a directory named by the dvd name
- Repeat

Warning: Even though this code has been used multiple times, it is not properly tested

## Usage

```bash

# Uses /dev/sr0 by default
php autorip

# Specify a device
# Useful if you have two dvd to rip at the same time
php autorip --device="/dev/sr0"
php autorip --device="/dev/sr1" --name="External drive"
```