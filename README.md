# Module prestashop sbu_privilege #

## Description ##

Permet de mettre en place un système de parrainage (ou d'affiliation) appelé `code privilège`.  
D'un côté, on a les "responsables des ventes" ou commerciaux.
Les clients s'inscrivent avec un code privilège qui correspond au code privilège du responsable des ventes. Le parrain touchera des commissions sur toutes les ventes effectuées avec son code privilège.  
Le parrain doit être placé (manuellement) dans le groupe `Commercial` pour un professionnel, après validation de son identité.  
Le client privilégié sera placé (automatiqument) dans le groupe `Client privilégié`, si son code privilège correpond bien à celui de son responsable des ventes.  
Le client peut aussi être un professionnel. Dans ce cas, il doit être placé (manuellement) dans le groupe `Professionnel privilégié`, après vérification de son code privilège et de son SIREN.  
Il faut indiquer dans la configuration du module quel group joue le rôle du groupe `Parrains Pro`  

## versions PrestaShop Supportées ##

 Ce module est compatible avec les versions 1.7.6.0 et supérieures.
 
## Pré-requis ##
 
  1. Composer, cf [Composer](https://getcomposer.org/) pour en savoir plus
 
## Installation ##
  1. Télécharger la version depuis github puis renommer l'archive en sbu_privilege (supprimer le n° de version) puis renommer le répertoire à l'intérieur de l'archive en sbu_privilege (supprimer le n° de version)
  2. `cd` dans le répertoire du module puis lancer la commande suivante :
      - `composer install` - pour télécharger les dépendances dans le répertoire vendor (au final, pas utile. Ca fait planter cs_fixer)
  3. Installer le module depuis le Back Office

## Il reste à faire ##
- le ménage dans le php (beaucoup de fonctions inutiles)
- Modifier le formulaire de configuration du SBU_PRIVILEGE_COMMERCIAL_GROUP_ID pour proposer les groupes en liste déroulante
- finir la traduction du module
- ajouter une partie dans l'espace client pour afficher et modifier son code privilège (ou juste l'afficher)
- faire la partie migration (pour passer d'une version à une autre avec sauvegarde du contenu de la BDD, sauvegarder le champ code privilège pour pouvoir le réinjecter post-migration)
- permettre de faire des requêtes pour savoir les ventes réalisées par les clients ayant un code privilège donné (le code privilège correspond à un commercial qui se vera attribuer une commission sur toutes les ventes faites avec son code privilège)
- permettre au commercial de suivre, depuis son espace client, la liste des ventes de ses clients et de connaître le montant de ses commissions
- Déplacer le champ privilege code dans le formulaire d'inscription dans le FO (pas possible)
- Lors de la désinstallation, il faudrait faire un export des code_privileges pour éviter les erreurs de réinstallation (persone ne le fait dans aucun module)
- Affectation automatique du groupe 'Professionnel Privilégié' (groupe id 4) lorsqu'un professionnel crée un compte en renseignant son SIREN (et son code privilège)


## Historique des versions ##
### v 1.2.3  ###
- Finalement pour les affiliés on s'inscrit via un site externe. On retire donc la case à cocher du formulaire d'inscription pour le remplacer par une case à cocher pour devenir responsable des ventes

### v 1.2.2  ###
- Correction du bug qui ne permettait plus de modifier un client

### v 1.2.1  ###
- Affectation automatique du groupe 'Client Privilégié' (groupe id 5) lorsqu'un client particulier crée un compte en renseignant son code privilège et que le code privilège est bien valide

### v 1.2.0  ###
- Gestion de la suppression du customer, on efface alors le privilege_code
- Possibilité de s'inscrire en tant que parrain particulier

### v 1.1.0  ###
- Modification d'architecture. Je n'utilise plus le champs code_privilege dans la table customer, mais je crée une nouvelle table exprès pour ça (pour éviter de modifier la table customer)
- modifier le formulaire de modification d'un client dans le BO pour permettre de modifier le code privilège
- trouver une icone pour le module

### v 1.0.0  ###
- Modification du formulaire d'inscription pour ajouter le champ "code privilège"  
- Ajout du champ code privilege dans la liste des clients dans le BO  
- Il y a une partie configuration du module (qui n'est pas terminée)  
- Création du champ privilege_code dans la table customer lors de l'installation et suppression du champ lors de la désinstallation.

### ATTENTION ###
Si je fais un composer install, ça va installer friendsofphp/php-cs-fixer qui va installer un tas de dépependances dont polyfill-intl-normalizer qui est incompatible avec le système de traduction
Du coup, je n'installe pas ces dépendances en local, je n'en ai pas besoin (seulement dans git workflow)

