---
title: "Lancer mes combats"
---

# Lancer mes combats

```bash
bin/console fight:solo
```

Une fois que les [identifiants rentrés]({{< ref "/docs/installation/personalisation.md" >}}), vous aurez quelques questions auxquelles il faudra répondre:

**Quelle stratégie de choix d'adversaire voulez vous utiliser ?**
*3 choix possibles*

Manuel : Choisir l'adversaire manuellement
Talent : Choisir automatiquement le poireau qui a le moins de talent
Niveau : Choisir automatiquement le poireau qui a le plus petit niveau

**Combien de combats voulez vous faire ?**
*Un nombre*

Le nombre de combat à lancer

**Quel poireau voulez vous utiliser ?**
*Au choix parmis vos poireaux*

## Le mode manuel

En mode manuel, à chaque combat, la commande vous demandera de choisir l'adversaire. Ce mode est équivalent au site mais permet tout de même d'enchaîner les combats assee rapidement.

![Choix](/fights-solo-choice.png)

## Le mode automatique (talent ou niveau)

Les combats vont s'enchainer en attendant que le combat précédant soit fini afin déviter de boucher la file d'attente.

![Combats](/fights-solo-result.png)

Au cas où votre poireau se retrouve coincé dans une file, la commande s'arrêtera avec le message d'erreur suivant:

![Timeout](/fights-solo-timeout.png)


