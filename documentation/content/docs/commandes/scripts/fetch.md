---
title: "Récupérer mes scripts"
---

# Récupérer mes scripts

```bash
bin/console scripts:fetch
```

Une fois que les [identifiants rentrés]({{< ref "/docs/installation/personalisation.md" >}}), ce message de confirmation apparait:

![Aperçu de réussite](/scripts-fetch-success.png)

Et tous les scripts devraient être dans le dossier `scripts` à l'intérieur de ce projet (ou dans le [dossier personnalisé]({{< ref "/docs/installation/personalisation.md" >}})).

### Limitation

Si vous avez plusieurs scripts ayant le même nom dans le même dossier (par exemple, 3 scripts appelés "Sans titre"),
la commande no fonctionnera pas car l'OS (que ce soit Windows, Mac ou linux) ne peut pas gérer plusieurs fichier ayant le même nom.

Il faudra donc faire un peu de renommage avant de faire cette commande
