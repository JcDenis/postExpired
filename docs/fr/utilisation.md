# postExpired : guide d'utilisation

postExpired permet de programmer, pour un billet ou une page, des changements à une date donnée : passer le billet en « non publié », le changer de catégorie, fermer les commentaires, lui mettre un mot de passe, etc.

## Table des matières

1. [Programmer un changement sur un billet](#programmer-un-changement-sur-un-billet)
2. [Programmer sur plusieurs billets](#programmer-sur-plusieurs-billets)
3. [Retirer une date de péremption](#retirer-une-date-de-péremption)
4. [Quand le changement a-t-il lieu ?](#quand-le-changement-a-t-il-lieu-)
5. [Bon à savoir](#bon-à-savoir)

## Programmer un changement sur un billet

Ouvrez un billet (ou une page), existant ou nouveau. Dans la colonne de droite, une rubrique **Date de péremption** est repliée : cliquez dessus pour la déplier.

![Rubrique vide](img/billet-menu-vide.png)

Remplissez :

* **Date** : le jour et l'heure du changement, dans votre fuseau horaire.
* **À cette date, changer** : les valeurs à modifier. « Inchangé » laisse la valeur actuelle.
  * **État** : publié, en attente ou non publié.
  * **Catégorie** : une catégorie, ou « Sans catégorie » (billets seulement).
  * **Sélection** : sélectionné ou non (billets seulement).
  * **Statut des commentaires** et **des rétroliens** : ouverts ou fermés.
  * **Modifier le mot de passe** : cochez la case et saisissez le nouveau mot de passe. Laissé vide, le mot de passe du billet est supprimé.

![Rubrique remplie](img/billet-menu-rempli.png)

Enregistrez le billet. La date et les changements sont mémorisés avec le billet : rouvrez-le pour les retrouver.

Si la date est remplie sans aucun changement, ou si un changement est choisi sans date, rien n'est mémorisé et un message vous le signale.

## Programmer sur plusieurs billets

Dans la liste des billets (ou des pages), cochez les billets, choisissez l'action **Ajouter une date de péremption** et validez. Une page vous demande la date et les changements, comme ci-dessus. Si la date ou les changements manquent, un message vous le signale et la page reste affichée.

![Action groupée](img/action-groupee.png)

Les billets choisis reçoivent tous la même date et les mêmes changements.

## Retirer une date de péremption

* Sur un billet : videz la date, remettez tous les changements sur « Inchangé », puis enregistrez.
* Sur plusieurs billets : cochez-les et choisissez l'action **Retirer une date de péremption**.

## Quand le changement a-t-il lieu ?

Le plugin ne tourne pas en tâche de fond : la date est vérifiée à chaque ouverture d'une page du blog, côté public (par un visiteur) comme côté administration. Une fois la date dépassée, les changements sont appliqués et la date de péremption est supprimée du billet.

Un billet n'a qu'une seule date de péremption.

## Bon à savoir

* Tant que personne n'ouvre une page du blog ou de l'administration, le changement n'a pas lieu, même si la date est passée.
* Le plugin fournit aussi des balises pour les thèmes (`EntryExpiredIf`, `EntryExpiredDate`, `EntryExpiredTime`) afin d'afficher la date de péremption sur le blog. Les dates sont affichées dans le fuseau horaire du billet.
