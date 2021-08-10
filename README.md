# Module prestashop sbu_privilege #

## Description ##

Permet de mettre en place un système de parrainage appelé `code privilège`.  
Les clients s'inscrivent avec un code privilège qui correspond au code d'un commercial. Le commercial touchera des commissions sur toutes les ventes effectuées avec son code privilège.  
Le commercial doit être placé (manuellement) dans le groupe `Commercial`, après validation de son identité.  
Le client privilégié sera placé (manuellement) dans le groupe `Client privilégié`, après vérification de son code privilège.  
Les clients peuvent aussi être des professionnels. Dans ce cas, ils seront placés (manuellement) dans le groupe `Professionnel`, après vérification de leur code privilège.  
Il faut indiquer dans la configuration du module quel group joue le rôle du groupe `Commercial`  

### versions PrestaShop Supportées

 Ce module est compatible avec les versions 1.7.6.0 et supérieures.
 
### Pré-requis
 
  1. Composer, cf [Composer](https://getcomposer.org/) pour en savoir plus
 
### How to install
 
  1. Download or clone module into `modules` directory of your PrestaShop installation
  2. Rename the directory to make sure that module directory is named `demoextendsymfonyform1`*
  3. `cd` into module's directory and run following commands:
      - `composer install` - to download dependencies into vendor folder
  4. Install module from Back Office
 
 *Because the name of the directory and the name of the main module file must match.

## Installation ##
  1. Télécharger la version depuis github puis renommer l'archive en sbu_privilege (supprimer le n° de version) puis renommer le répertoire à l'intérieur de l'archive en sbu_privilege (supprimer le n° de version)
  2. `cd` dans le répertoire du module puis lancer la commande suivante :
      - `composer install` - pour télécharger les dépendances dans le répertoire vendor
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
- Lors de la désinstallation, il faudrait faire un export des code_privileges pour éviter les erreurs de réinstallation
- Mettre en place nouvelle traduction (trans et non l)
- il faut rajouter le delete quand on efface un customer, il faut effacer le privilege_code

## Historique des versions ##
- v 1.1.0  
Modification d'architecture. Je n'utilise plus le champs code_privilege dans la table customer, mais je crée une nouvelle table exprès pour ça (pour éviter de modifier la table customer)
- modifier le formulaire de modification d'un client dans le BO pour permettre de modifier le code privilège
- trouver une icone pour le module

- v 1.0.0  
Modification du formulaire d'inscription pour ajouter le champ "code privilège"  
Ajout du champ code privilege dans la liste des clients dans le BO  
Il y a une partie configuration du module (qui n'est pas terminée)  
Création du champ privilege_code dans la table customer lors de l'installation et suppression du champ lors de la désinstallation.
