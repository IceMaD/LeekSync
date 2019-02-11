---
title: "Personnalisation"
---

# Personnalisation

## Identification

Votre identifian et votre mot de passe vous seront demandés à chaque commande.

![Aperçu du login](/login-prompt.png)

> Ceux-ci ne sont pas stockés, mais si vous ne me faites pas confiance (et ne faites jamais confiance à personne sur Internet), vous pouvez consulter mon code.
>
> Ca vous rassurera et vous pourriez avoir des idées d’améliorations :D

Si vous trouvez cela rébarabatif vous pouvez les stocker comme suit

## Configuration

Il est possible de personnaliser l'outils en changeant la configuration.
Pour ce faire, créer un fichier nommé `.env.local` à coté du fichier `.env`.

Dans ce fichier, il y 4 paramètres possibles :

**APP_LOGIN**     
Le nom de compte (pour éviter de le retaper à chaque fois, il fonctionnera même si APP_PASSWORD est vide)

**APP_PASSWORD**     
Le mot de passe (pour éviter de le retaper à chaque fois)

**APP_FILE_EXTENSION**     
L'extension à utiliser pour les fichiers locaux (défaut: js)

**APP_SCRIPTS_DIR**     
Le dossier du PC dans lequel les scripts seront copiés

Exemple:

```dotenv
APP_LOGIN=IceMaD
APP_PASSWORD=M0N-M0T-D3-P4553-5UP3R-53CR3T
APP_FILE_EXTENSION=lks
APP_SCRIPTS_DIR=/home/icemad/scripts
```
