![Har Logo](hardownloader.svg?raw=true)

# HAR Downloader



usage: downloader.php <command> [<options>]

COMMANDS

*  get    get only one image
*  pull   try to download all images

Options:

* --item -i  item ID to download
* --destination -d destination directory
* --verbose -v verbose output

eg. `hardownloader get -i 24301 -d /tmp/har -v`


Installation
------------


```shell
sudo apt install lsb-release wget apt-transport-https bzip2

wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update
sudo apt install hardownloader
```

