# Priya.Getting.Started
Getting started with Priya including examples

The install.php installs priya on the system.

You need to have php installed on your system or use:

```bash
sudo apt-get install php -y
```

When you have installed php on your system do (sudo or as root):

```bash
sudo wget https://priya.software/install | php install
```

or

```bash
sudo wget https://raw.githubusercontent.com/like-it/Priya.Getting.Started/master/install.php | php install.php
```

it will ask you which directory to install to.

It is also asking for a remote repository url (return = skip)

- it will install git if git is uninstalled.
- it will install apache2 if apache2 is uninstalled.
- it will add priya.local to the /etc/host file
- it will add a sites-available configuration file for apache2
- it will enable modrewrite for apache2
- it will restart apache2
- it will add priya to /usr/bin
- it will add the following submodules in the Vendor directory
- Priya
- Smarty
- Json
- Jquery
- FontAwesome
- Finaly it is setting it up for priya.local to work on apache2 and doing some tests.



