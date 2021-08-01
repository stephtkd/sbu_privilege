# Module prestashop sbu_privilege #

## Description ##

Permet de mettre en place un système de parrainage appelé `code privilège`.  
Les clients s'inscrivent avec un code privilège qui correspond au code d'un commercial. Le commercial touchera des commissions sur toutes les ventes effectuées avec son code privilège.  
Le commercial doit être placé (manuellement) dans le groupe `Commercial`, après validation de son identité.  
Le client privilégié sera placé (manuellement) dans le groupe `Client privilégié`, après vérification de son code privilège.  
Les clients peuvent aussi être des professionnels. Dans ce cas, ils seront placés (manuellement) dans le groupe `Professionnel`, après vérification de leur code privilège.  
Il faut indiquer dans la configuration du module quel group joue le rôle du groupe `Commercial`  


## Il reste à faire ##
- le ménage dans le php (beaucoup de fonctions inutiles)
- Modifier le formulaire de configuration du SBU_PRIVILEGE_COMMERCIAL_GROUP_ID pour proposer une les groupes en liste déroulante
- modifier le formulaire de modification d'un client dans le BO pour permettre de modifier le code privilège
- finir la traduction du module
- ajouter une partie dans l'espace client pour afficher et modifier son code privilège (ou juste l'afficher)
- faire la partie migration (pour passer d'une version à une autre avec sauvegarde du contenu de la BDD, sauvegarder le champ code privilège pour pouvoir le réinjecter post-migration)
- permettre de faire des requêtes pour savoir les ventes réalisées par les clients ayant un code privilège donné (le code privilège correspond à un commercial qui se vera attribuer une commission sur toutes les ventes faites avec son code privilège)
- permettre au commercial de suivre, depuis son espace client, la liste des ventes de ses clients et de connaître le montant de ses commissions
- trouver une icone pour le module
- Déplacer le champ privilege code dans le formulaire d'inscription dans le FO (pas possible)
- Lors de la désinstallation, il faudrait faire un export des code_privileges pour éviter les erreurs de réinstallation


## Historique des versions ##
- v 1.0.0  
Modification du formulaire d'inscription pour ajouter le champ "code privilège"  
Ajout du champ code privilege dans la liste des clients dans le BO  
Il y a une partie configuration du module (qui n'est pas terminée)  
Création du champ privilege_code dans la table customer lors de l'installation et suppression du champ lors de la désinstallation.
