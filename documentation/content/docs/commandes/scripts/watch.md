---
title: "Synchronisation automatique"
---

# Synchronisation automatique

### Lancer l'observateur

L'observateur est un script qui va observer le dossier scripts sur le PC et répliquer tous les changements qu'il détecte sur le site de LeekWars.

Il n'est pas capable de savoir quand des modifications ont été effectuées sur le site. Pour récupérer les scripts depuis le site, voir [Récuperer mes scripts]({{< ref "/docs/commandes/scripts/fetch.md" >}})

```bash
bin/console scripts:watch
```

Une fois que les [identifiants rentrés]({{< ref "/docs/installation/personalisation.md" >}}), ce message devrait apparaitre:

![Observateur](/scripts-watch-start.png)

Commencer à éditer les fichiers d'IA, sauvegarder et ... tadaa!

![Réussite de la synchronisation](/scripts-sync-success.png)

Si le code n'est pas valide, le résultat le montrera

![Echec de la synchronisation](/scripts-sync-failure.png)

Ici, il me manque un ";" à la ligne 9 (29 ème caractère)

### Opérations prises en compte

Les opérations suivantes sont automatiquement synchronisées vers le site :

- Création d'une IA
- Suppression d'une IA
- Modification du contenu d'une IA
- Modification du nom d'une IA
- Déplacement d'une IA d'un dossier à un autre dossier
- Déplacement de plusieurs IAs d'un dossier à un autre dossier
- Création d'un dossier (Le dossier n'est synchronisé qu'une fois qu'une IA est crée dedans)
- Suppression d'un dossier (Et de toutes les IAs qui sont dedans)
- Modification du nom d'un dossier
- Déplacement d'un dossier dans un autre dossier

Les opérations suivantes **NE SONT PAS** gérés (et feront planter le script) :

- Déplacement de plusieurs dossiers d'un coup dans un autre dossier
- Changer l'extension d'une IA pendant que l'observateur tourne
