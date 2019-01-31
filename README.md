LeekTools
========

Application en ligne de commande (utilisant [Symfony](https://symfony.com/)) pour synchroniser les fichiers IA locaux et [LeekWars](http://leekwars.com)

Pour utiliser cette application, vous devez avoir installé [PHP](http://php.net/) et [Composer](https://getcomposer.org/doc/00-intro.md) sur votre ordinateur.

Une fois ces dépendances installées, clonez (ou téléchargez) ce projet, exécutez `composer install`, puis [Récuperez vo scripts](#recuperez-vos-scripts)

## Récupérer vos scripts

```bash
bin/console scripts:fetch
```

Votre compte et votre mot de passe vous seront demandés.

![Aperçu du login](./doc/login-prompt.png)

> Celles-ci ne sont pas stockées, mais si vous ne me faites pas confiance (et ne faites jamais confiance à personne sur Internet), vous pouvez consulter mon code.
> Ca vous rassurera et vous pourriez avoir des idées d’améliorations :D

Une fois que vous avez entré vos identifiants, vous devriez voir ce message de confirmation:

![Aperçu de réussite](./doc/fetch-success.png)

Et tous vos scripts devraient être dans le dossier `scripts` à l'intérieur de ce projet.

> Remarque: ce dossier sera personnalisable dans une version future.

## Commencer à travailler

```bash
bin/console scripts:watch
```

Encore une fois, mettez vos identifiants (Si vous trouvez cela ennuyeux, voir [Personnaliser l'outils](#personnaliser-loutils)), vous devriez voir ce message:

![Voir l'aperçu de départ](./doc/watch-start.png)

Commencez à éditer vos fichiers, sauvegardez et ... tadaa!

![Aperçu de la réussite de la synchronisation](./doc/sync-success.png)

Si votre code n'est pas valide, le résultat le montrera

![Aperçu de l'échec de la synchronisation](./doc/sync-failure.png)

Ici, il me manque un ";" à la ligne 9 (29 ème caractère)

## Personnaliser l'outils

Vous pouvez personnaliser l'outils en changeant la configuration.
Pour ce faire, créez un fichier nommé `.env.local` à coté du fichier `.env`.

Dans ce fichier, vous pouvez configurer 4 paramètres :

- **APP_LOGIN** Votre nom de compte (pour éviter de le retaper à chaque fois, vous pouvez le renseigner même si vous ne remplissez pas le paramètre APP_PASSWORD)
- **APP_PASSWORD** Votre mot de passe (pour éviter de le retaper à chaque fois)
- **APP_FILE_EXTENSION** L'extension que vous voulez utiliser pour les fichiers locaux (défaut: js)
- **APP_SCRIPTS_DIR** Le dossier de votre PC dans lequel vous voulez que vos scripts soient copiés

Exemple:

```dotenv
APP_LOGIN=IceMaD
APP_PASSWORD=M0N-M0T-D3-P4553-5UP3R-53CR3T
APP_FILE_EXTENSION=lks
APP_SCRIPTS_DIR=/home/icemad/scripts
```
