---
title: "Installation"
---

# Installation

## Prérequis

LeekTools utilise [PHP](http://php.net/) et [Composer](https://getcomposer.org/doc/00-intro.md)

Voici quelques tutoriels pour les principaux

#### **Ubuntu (Adaptable aux autres distributions en remplaçant apt par le bon gestionnaire de packet)**

Installation de PHP et composer
```bash
sudo apt install php7.2-cli php7.2-mbstring php7.2-xml php7.2-zip
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"  
sudo sudo mv composer.phar /usr/local/bin/composer
```

Installation de LeekTools
```bash
git clone https://github.com/IceMaD/LeekTools.git
cd LeekTools
composer install
```

#### **Mac**

J'ai pas de Mac sous la main mais ça devrait pas être trop différent de Linux. Il me semble même que PHP est installé par défaut sur Mac.

#### **Windows**

On va installer Linux sur windows, et ça prend du temps (mais pas d'inquiétude, il n'y a presque rien à faire, juste à attendre) !

Commencer par installer bash pour windows en suivant ce [Tutoriel](https://korben.info/installer-shell-bash-linux-windows-10.html) jusqu'à avoir Ubuntu sur Windows.

![Ubuntu sur windows](/setup-windows-ubuntu.png)

Ensuite executer les commandes suivantes pour être sur qu'on est à jour et installer ce qui manque ainsi que l'outils :

Mise à jour d'ubuntu (pas requis, mais recommandé s'il viens d'être installé)
```bash
sudo apt update
sudo apt upgrade
```

Installation de PHP et composer
```bash
sudo apt install php7.2-cli php7.2-mbstring php7.2-xml php7.2-zip
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"  
sudo sudo mv composer.phar /usr/local/bin/composer
```

Installation de LeekTools
```bash
git clone https://github.com/IceMaD/LeekTools.git
cd LeekTools
composer install
```

Pour faire le lien avec windows, utiliser la configuratation **APP_SCRIPTS_DIR** comme expliqué dans la section [Personnaliser l'outils]({{< ref "/docs/installation/personalisation.md" >}}) en gardant en tête que dans Ubuntu, le disque `C:\\` se situe dans `/mnt/c` et que les `\` doivent être remplacer par des `/`.

Par exemple, si mon dossier est `C:\Users\Marc\Desktop\scripts`, je doit mettre `/mnt/c/Users/Marc/Desktop/scripts`.
