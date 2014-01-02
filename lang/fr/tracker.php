<?php // $Id: tracker.php,v 1.2 2012-08-12 21:43:55 vf Exp $ 
      // tracker.php - created with Moodle 1.2 development (2003111400)

// Capabilities

$string['tracker:addinstance'] = 'Ajouter un gestionnaire de tickets';
$string['tracker:canbecced'] = 'Peut être observateur';
$string['tracker:comment'] = 'Commenter les tickets';
$string['tracker:configure'] = 'Configurer le gestionnaire';
$string['tracker:configurenetwork'] = 'Configurer les fonctions réseau';
$string['tracker:develop'] = 'Etre choisi pour résoudre un ticket';
$string['tracker:manage'] = 'Gérer les tickets';
$string['tracker:managepriority'] = 'Gérer la priorité des tickets';
$string['tracker:managewatches'] = 'Gérer les abonnements';
$string['tracker:report'] = 'Créer des tickets';
$string['tracker:resolve'] = 'Etre responsable de tickets';
$string['tracker:seeissues'] = 'Voir les tickets';
$string['tracker:shareelements'] = 'Partager des éléments au niveau site';
$string['tracker:viewallissues'] = 'Voir les tickets de tous';
$string['tracker:viewpriority'] = 'Voir la priorité de mes tickets';
$string['tracker:viewreports'] = 'Voir les rapports de traitement';

$string['AND'] = 'ET';
$string['IN'] = 'DANS';
$string['abandonned'] = 'Abandonné';
$string['action'] = 'Action';
$string['activeplural'] = 'Actifs';
$string['addacomment'] = 'Ajouter un commentaire';
$string['addanoption'] = 'Ajouter une option';
$string['addaquerytomemo'] = 'Ajouter cette recherche à "mes recherches"';
$string['addawatcher'] = 'Ajouter un observateur ';
$string['addtothetracker'] = 'Ajouter à ce traqueur';
$string['administration'] = 'Administration';
$string['administrators'] = 'Administrateurs';
$string['alltracks'] = 'Voir mes travaux dans tous les gestionnaires';
$string['any'] = 'Tous';
$string['askraise'] = 'Demander à augmenter la priorité';
$string['assignedto'] = 'Assigné à';
$string['assignee'] = 'Assigné';
$string['attributes'] = 'Attributs';
$string['browse'] = 'Exploration';
$string['browser'] = 'Navigateur';
$string['build'] = 'Version';
$string['by'] = '<i>assigné par</i>';
$string['cascade'] = 'Remonter au niveau supérieur';
$string['cascadedticket'] = 'Ticket transmis';
$string['categories'] = 'Catégories';
$string['cced'] = 'Abonnés ';
$string['ccs'] = 'Abonnements ';
$string['checkbox'] = 'Cases à cocher'; // @DYNA
$string['checkboxhoriz'] = 'Cases à cocher horizontal'; // @DYNA
$string['chooselocal'] = 'Choisir une instance locale ';
$string['chooseremote'] = 'Choisir un hôte distant ';
$string['chooseremoteparent'] = 'Choisir une instance distante ';
$string['choosetarget'] = 'Choisir un sous-gestionnaire';
$string['clearsearch'] = 'Effacer les critères de recherche';
$string['comment'] = 'Commentaire';
$string['comments'] = 'Commentaires';
$string['component'] = 'Composant';
$string['count'] = 'Nombre';
$string['countbyassignee'] = 'Par assigné';
$string['countbymonth'] = 'Rapport par date de création';
$string['countbyreporter'] = 'Par émetteur';
$string['countbystate'] = 'Rapports par état';
$string['createdinmonth'] = 'Créées ce mois';
$string['createdinmonth'] = 'Créés dans le mois (total : {$a})';
$string['createnewelement'] = 'Créer un nouveau critère';
$string['currentbinding'] = 'Cascade active';
$string['database'] = 'Base de données';
$string['datereported'] = 'Date de signalement';
$string['defaultassignee'] = 'Assigné par défaut';
$string['deleteattachedfile'] = 'Supprimer l\'attachement';
$string['dependancies'] = 'Dépendances';
$string['dependson'] = 'Dépends de ';
$string['evolution'] = 'Tendances';
$string['descriptionisempty'] = 'La description ne peut pas être laissée vide.';
$string['distribute'] = 'Déplacer le ticket';
$string['doaddelementcheckbox'] = 'Ajouter des cases à cocher'; // @DYNA
$string['doaddelementcheckboxhoriz'] = 'Ajouter des cases à cocher'; // @DYNA
$string['doaddelementdropdown'] = 'Ajouter une liste déroulante'; // @DYNA
$string['doaddelementfile'] = 'Ajouter un champ de fichier'; // @DYNA
$string['doaddelementradio'] = 'Ajouter un bouton radio'; // @DYNA
$string['doaddelementradiohoriz'] = 'Ajouter un bouton radio'; // @DYNA
$string['doaddelementtext'] = 'Ajouter un champ de texte'; // @DYNA
$string['doaddelementtextarea'] = 'Ajouter une zone de texte'; // @DYNA
$string['doupdateelementcheckbox'] = 'Modifier des cases à cocher'; // @DYNA
$string['doupdateelementcheckboxhoriz'] = 'Modifier des cases à cocher'; // @DYNA
$string['doupdateelementdropdown'] = 'Modifier une liste de choix';// @DYNA
$string['doupdateelementfile'] = 'Modifier un champ de fichier'; // @DYNA
$string['doupdateelementradio'] = 'Modifier un bouton radio'; // @DYNA
$string['doupdateelementradiohoriz'] = 'Modifier un bouton radio'; // @DYNA
$string['doupdateelementtext'] = 'Modifier un champ de texte'; // @DYNA
$string['doupdateelementtextarea'] = 'Modifier une zone de texte'; // @DYNA
$string['dropdown'] = 'Menu déroulant';
$string['editoptions'] = 'Editer les options';
$string['editproperties'] = 'Editer les propriétés';
$string['editquery'] = 'Modifier une requête mémorisée';
$string['editwatch'] = 'Modifier un abonnement';
$string['elements'] = 'Critères disponibles';
$string['elementsused'] = 'Critères utilisés';
$string['elucidationratio'] = 'Taux d\'élucidation';
$string['emailoptions'] = 'Options de courriel';
$string['emergency'] = 'Avis d\'urgence';
$string['emptydefinition'] = 'La définition du gestionnaire cible est vide.';
$string['enablecomments'] = 'Autoriser les commentaires';
$string['errorcoursemisconfigured'] = 'Ce cours est mal configuré';
$string['errorcoursemodid'] = 'L\'ID de module de cours est incrorrect';
$string['errorfindingaction'] = 'Erreur : L\'action {$a} ne peut être identifiée. ';
$string['errormoduleincorrect'] = 'Le module de cours est erroné';
$string['errornoaccessallissues'] = 'Vous n\'avez pas l\'autorisation de voir tous les tickets.';
$string['errornoaccessissue'] = 'Vous n\'avez pas l\'autorisation de voir ce ticket.';
$string['errornoeditissue'] = 'Vous n\'avez pas l\'autorisaton de modifier ce ticket.';
$string['errorremote'] = 'Erreur distante: {$a}';
$string['evolutionbymonth'] = 'Evolution par mois';
$string['file'] = 'Fichier attaché';
$string['follow'] = 'Suivre';
$string['generaltrend'] = 'Tendance';
$string['hassolution'] = 'Ce bug a une solution publiée';
$string['hideccs'] = 'Cacher les observateurs';
$string['hidecomments'] = 'Cacher les commentaires';
$string['hidedependancies'] = 'Cacher les dépendances';
$string['hidehistory'] = 'Cacher l\'historique';
$string['history'] = 'Assignés';
$string['iamadeveloper'] = 'Je peux travailler sur des tickets';
$string['iamnotadeveloper'] = 'Je ne peux pas tavailler sur des tickets';
$string['icanmanage'] = 'Je peux gérer les tickets';
$string['icannotmanage'] = 'Je ne gère pas les tickets';
$string['icannotreport'] = 'Je ne peux pas créer des tickets';
$string['icannotresolve'] = 'Je ne peux pas prendre en charge des tickets';
$string['icanreport'] = 'Je peux créer des tickets';
$string['icanresolve'] = 'Je suis assigné sur certains tickets';
$string['id'] = 'Identifiant';
$string['intest'] = 'En test';
$string['inworkinmonth'] = 'A resoudre';
$string['issueid'] = 'Ticket';
$string['issuename'] = 'Libellé du ticket ';
$string['issuenumber'] = 'Ticket';
$string['issues'] = 'tickets';
$string['issuestoassign'] = 'Tickets à répartir: {$a}';
$string['issuestowatch'] = 'Tickets à examiner: {$a}';
$string['knownelements'] = 'Rubriques connues ';
$string['listissues'] = 'Liste de tickets';
$string['local'] = 'Local';
$string['lowerpriority'] = 'Baisser la priorité';
$string['lowertobottom'] = 'En dernier';
$string['manageelements'] = 'Gérer les rubriques';
$string['managenetwork'] = 'Fonctions cascade et réseau';
$string['manager'] = 'Mes ressources ';
$string['me'] = 'Mon profil';
$string['message_bugtracker'] = 'Merci pour votre contribution à l\'amélioration générale du service.';
$string['message_ticketting'] = 'Nous avons bien enregistré votre demande. Elle a été assignée à {$a}.';
$string['message_ticketting_preassigned'] = 'Nous avons bien enregistré votre demande. Elle sera traitée très prochainement.';
$string['message_taskspread'] = 'Vous venez de définir une tâche. Pour finaliser votre action, n\'oubliez pas de l\'assigner à un destinataire.';
$string['mode_bugtracker'] = 'Traqueur de défauts ';
$string['mode_ticketting'] = 'Service support utilisateur ';
$string['mode_taskspread'] = 'Distribution de tâches individuelles';
$string['mode_customized'] = 'Gestionnaire customisé';
$string['modulename'] = 'Gestionnaire de tickets';
$string['modulenameplural'] = 'Gestionnaires de tickets';
$string['nofileloaded'] = 'Pas de fichier chargé.';
$string['month'] = 'Mois';
$string['myassignees'] = 'Les personnes que j\'ai assignées';
$string['myissues'] = 'Les tickets que je gère ';
$string['mypreferences'] = 'Mes préférences';
$string['myprofile'] = 'Mon profil';
$string['myqueries'] = 'Mes recherches';
$string['mytickets'] = 'Mon support ';
$string['mytasks'] = 'Mes demandes';
$string['mywatches'] = 'Mes abonnements';
$string['mywork'] = 'Mon travail';
$string['name'] = 'Nom';
$string['namecannotbeblank'] = 'Le nom ne peut pas être laissé vide.';
$string['newissue'] = 'Nouveau ticket';
$string['noassignedtickets'] = 'Aucun travail en cours';
$string['noassignees'] = 'Pas de responsable attribué';
$string['nochange'] = 'Ne pas changer';
$string['nocomments'] = 'Pas de commentaires ';
$string['nodata'] = 'Aucune donnée à traiter.';
$string['nodevelopers'] = 'Pas de développeurs';
$string['noelements'] = 'Aucun élément';
$string['noelementscreated'] = 'Aucun élément créé';
$string['nofile'] = 'Pas de fichier attaché';
$string['noissuesreported'] = 'Aucun ticket relevé';
$string['noissuesresolved'] = 'Aucun ticket résolu';
$string['nolocalcandidate'] = 'Aucun tracker local disponible';
$string['nomnet'] = 'Le réseau Moodle semble désactivé';
$string['nooptions'] = 'Pas d\'option';
$string['noqueryssaved'] = 'Aucune recherche actuellement mémorisée';
$string['noremotehosts'] = 'Aucun hôte réseau disponible';
$string['noremotetrackers'] = 'Aucun tracker distant disponible';
$string['noreporters'] = 'Pas de rapporteurs, il n\'y a probablement pas de ticket dans ce gestionnaire.';
$string['noresolvers'] = 'Pas de responsables';
$string['noresolvingissue'] = 'Pas de ticket attribué';
$string['notickets'] = 'Aucun ticket personnel';
$string['noticketsorassignation'] = 'Pas de tickets ou d\'assignations';
$string['notifications'] = 'Notifications';
$string['notrackeradmins'] = 'Pas d\'administrateurs';
$string['nowatches'] = 'Pas d\'abonnements';
$string['numberofissues'] = 'Nombre de tickets';
$string['observers'] = 'Observateurs';
$string['open'] = 'Ouvert';
$string['option'] = 'Option ';
$string['optionisused'] = 'Ce nom d\'option est déjà utilisé pour cet élément.';
$string['order'] = 'Ordre';
$string['pages'] = 'Pages';
$string['posted'] = 'Posté';
$string['potentialresolvers'] = 'Responsables potentiels';
$string['preferences'] = 'Préférences';
$string['prefsnote'] = 'Les préférences déterminent quelles sont les notifications que vous validez lorsque vous créez une nouvelle entrée de défaut ou lorsque que vous vous abonnez à un défaut existant';
$string['print'] = 'Impression';
$string['priority'] = 'Priorité donnée';
$string['priorityid'] = 'Priorité';
$string['profile'] = 'Mes réglages';
$string['published'] = 'Publié';
$string['queries'] = 'Requêtes';
$string['query'] = 'Requête';
$string['queryname'] = 'Label de la requête';
$string['radio'] = 'Boutons radio'; // @DYNA
$string['radiohoriz'] = 'Boutons radio horizontal'; // @DYNA
$string['raisepriority'] = 'Augmenter la priorité';
$string['raiserequestcaption'] = 'Demande de priorité pour un ticket';
$string['raiserequesttitle'] = 'Demander à augmenter la priorité';
$string['raisetotop'] = 'En premier';
$string['reason'] = 'Raison ';
$string['register'] = 'S\'abonner à ce ticket';
$string['reportanissue'] = 'Créer un nouveau ticket';
$string['reportedby'] = 'Rapporteur';
$string['reporter'] = 'Mes rapports ';
$string['reporter'] = 'Rapporteur';
$string['reports'] = 'Rapports';
$string['resolution'] = 'Solution';
$string['resolved'] = 'Résolu';
$string['resolvedplural'] = 'Résolu(s)';
$string['resolvedplural2'] = 'Résolus';
$string['resolver'] = 'Mes tickets (résolution) ';
$string['resolvers'] = 'Résolveurs';
$string['resolving'] = 'En travail';
$string['runninginmonth'] = 'En cours dans le mois';
$string['saveasquery'] = 'Sauvegarder une requête ';
$string['savequery'] = 'Sauvegarder la requête';
$string['search'] = 'Recherche';
$string['searchbyid'] = 'Recherche par ID';
$string['searchcriteria'] = 'Critères de recherche';
$string['searchresults'] = 'Résultats de recherche';
$string['searchwiththat'] = 'Relancer cette recherche';
$string['selectparent'] = 'Choix de la cible';
$string['sendrequest'] = 'Envoyer la demande';
$string['setoncomment'] = 'Les commentaires déposés ne me sont pas envoyés';
$string['setwhenopens'] = 'L\'avis d\'ouverture ne m\'est pas envoyé';
$string['setwhenpublished'] = 'Les avis de publication (production) ne me sont pas envoyés';
$string['setwhenresolves'] = 'L\'avis à la conclusion ne m\'est pas envoyé';
$string['setwhentesting'] = 'Les avis de solution ne me sont pas envoyés';
$string['setwhenthrown'] = 'L\'avis d\'abandon ne m\'est pas envoyé';
$string['setwhenwaits'] = 'Les avis de mise en sommeil ne me sont pas envoyés';
$string['setwhenworks'] = 'Les avis de prise en charge ne me sont pas envoyés';
$string['sharethiselement'] = 'Rendre ce critère global';
$string['sharing'] = 'Partage';
$string['showccs'] = 'Montrer les observateurs';
$string['showcomments'] = 'Montrer les commentaires';
$string['showdependancies'] = 'Montrer les dépendances';
$string['showhistory'] = 'Montrer l\'historique';
$string['site'] = 'Site';
$string['solution'] = 'Solution';
$string['sortorder'] = 'Ordre';
$string['standalone'] = 'Traqueur autonome.';
$string['statehistory'] = 'Etats';
$string['stateprofile'] = 'Etats de tickets';
$string['status'] = 'Etat';
$string['strictworkflow'] = 'Cycle de vie strict';
$string['submission'] = 'Un nouveau ticket a été ouvert dans le tracker [{$a}]';
$string['submitbug'] = 'Soumettre le ticket';
$string['subtrackers'] = 'Sous-gestionnaires ';
$string['sum_opened'] = 'Ouverts ';
$string['sum_posted'] = 'En attente ';
$string['sum_reported'] = 'Déposés ';
$string['sum_resolved'] = 'Résolus ';
$string['summary'] = 'Résumé';
$string['supportmode'] = 'Mode de support ';
$string['testing'] = 'En test';
$string['text'] = 'Champ de texte'; // @DYNA
$string['textarea'] = 'Zone de texte'; // @DYNA
$string['thanksdefault'] = 'Merci de votre contribution à l\'amélioration continue du service.';
$string['thanksmessage'] = 'Feedback après dépôt';
$string['ticketprefix'] = 'Préfixe du ticket';
$string['tickets'] = 'Tickets';
$string['tracker-levelaccess'] = 'Mes possibilités dans ce traqueur ';
$string['tracker_description'] = '<p>La publication de ce service permet à des trackers du site {$a} de cascader leur collecte vers l\'un de vos trackeurs.</p>
<ul><li><i>Dépendance</i> : Vous devez abonner le serveur {$a} à ce service.</li></ul>
<p>L\'abonement à ce service permet à des trackeurs de cascader des tickets de support vers les trackeurs du site {$a}.</p>
<ul><li><i>Dépendance</i> : Le site {$a} doit publier le service de cascade de trackeurs.</li></ul>';
$string['tracker_name'] = 'Services du gestionnaire de tickets';
$string['tracker_service_name'] = 'Services du gestionnaire de tickets';
$string['trackerelements'] = 'Définition du trackeur ';
$string['trackereventchanged'] = 'Changement d\'état du ticket dans le tracker [{$a}]';
$string['trackerhost'] = 'Hôte du trackeur parent ';
$string['trackername'] = 'Nom du gestionnaire ';
$string['transfer'] = 'Transféré';
$string['transfered'] = 'Transféré';
$string['transferservice'] = 'Transfert des tickets en cascade';
$string['turneditingoff'] = 'Désactiver l\'édition';
$string['turneditingon'] = 'Activer l\'édition';
$string['type'] = 'Type';
$string['unassigned'] = 'Non affecté' ;
$string['unbind'] = 'Supprimer la cascade';
$string['unmatchingelements'] = 'La définition des deux gestionnaires ne correspond pas. Ceci peut poser des problèmes pendant la cascade des tickets entre eux.';
$string['unregisterall'] = 'Me désabonner de tous les tickets' ;
$string['unsetoncomment'] = 'Les commentaires déposés me sont envoyés';
$string['unsetwhenopens'] = 'L\'avis à l\'ouverture m\'est envoyé';
$string['unsetwhenpublished'] = 'Les avis de publication (production) me sont envoyés';
$string['unsetwhenresolves'] = 'L\'avis à la conclusion m\'est envoyé';
$string['unsetwhentesting'] = 'Les avis de solution me sont envoyés';
$string['unsetwhenthrown'] = 'L\'avis d\'abandon m\'est envoyé';
$string['unsetwhenwaits'] = 'Les avis de mise en sommeil me sont envoyés';
$string['unsetwhenworks'] = 'Les avis de prise en charge me sont envoyés';
$string['urgentraiserequestcaption'] = 'Un utilisateur demande une priorité d\'urgence';
$string['urgentsignal'] = 'DEMANDE URGENTE';
$string['view'] = 'Tickets';
$string['vieworiginal'] = 'Voir l\'orginal';
$string['validated'] = 'Validé';
$string['voter'] = 'Votes';
$string['waiting'] = 'Bloqué';
$string['watches'] = 'Obs.';
$string['youneedanaccount'] = 'Vous devez posséder un compte dans cet espace pour pouvoir poster';

// help strings

$string['modulename_help'] = 'Le gestionnaire de tickets permet la gestion de tickets d\'aide, de rapport de défaut, ou de toute activité ou tâche qui nécessite un suivi d\'état dans un cours.

L\'activité permet de constituer un formulaire de dépôt en choisissant des attributs à partir d\'une liste d\'éléments configurable. Certains éléments peuvent même être partagés
au niveau site pour être réutilisés dans d\'autres instances.

Le ticket, (ou tâche représentée) peut être attribuée à un utilisateur.

Le ticket prend un certain nombre d\'états et leur changement émettra des notifications à certains utilisateurs qui les ont autorisées. Chaque utilisateur peut choisir librement les
différentes notificaitons qu\'il peut recevoir.

Les tickets peuvent être liés par dépendance, permettant de remonter une chaine causale.

Les changements d\'état sont historisés pour chaque ticket.

Les gestionnaires peuvent être cascadés localement ou via MNET vers un gestionnaire collecteur de plus haut niveau.

Les gestionnaires peuvent être associés à des sous-gestionnaires, facilitant et organisant le déplacement de tickets entre plusieurs gestionnaires. 
';

$string['supportmode_help'] = 'Le mode de support applique des réglages prédéfinis au gestionnaire pour lui donner un comportement spécifique. 

* Traqueur de défauts: Les rapporteurs ont accès à toute la liste de tickets pour un examen collaboratif des demandes déposées. Tous les états sont activés, y compris
les états participant aux phases de test sur des versions de préproduction.

* Support utilisateur/Ticketting: Les rapporteurs n\'ont accès qu\'aux tickets qu\'ils ont émis. Les développeurs peuvent voir toute la liste de tickets
assignés ou non et peuvent s\'assigner sur les tickets. Certains états propre à un processus de gestion "technique" ont été désactivés.

* Task distribution: Les rapporteurs peuvent voir tous les tickets postés par les autres rapporteurs. Les développeurs (destinataires) ne peuvent voir
que la liste de tickets qui leurs sont assignés à travers la vue "Mon travail". Des états sont désactivés pour une gestion très simplifiée des tickets.

* Gestionnaire Customisé : Aucune prédéfinition des roles, surcharges ni états de tickets n\'est imposée. Ce mode est le plus souple, mais demande une bonne connaissance
des réglage de Moodle et une réflexion sur le schéma d\'usage.

';

$string['elements_help'] = '
On peut constituer le formulaire de récolte des défauts à l\'aide d\'éléments. Un formulaire contient au moins les 
champs "résumé", "description", et "rapporté par", mais il est possible d\'ajouter tout type de qualificateur au défaut.

Les éléments sont des "éléments de formulaire" courants qui permettent de collecter des critères, tels que boutons radio, cases à cocher, listes déroulantes, champ de texte libre
ou même une zone de texte.

Les éléments sont définis par les propriétés suivantes :

### Le nom

Le nom sert à identifier l\'élément au niveau technique. Il doit être constitué sans caractères spéciaux, sans accents ni espaces. Le nom n\'apparait à aucun moment sur l\'interface du tracker.

### La description</h3>

La description est un texte qui est utilisé lorsqu\'il faut faire mention de l\'élément sur l\'interface.

### Options

Certains éléments comme les cases à cocher les listes ou les boutons radio permettent de saisir une valeur "contrainte" à un ensemble de valeur fini. Les options permettent de déterminer cet ensemble de valeurs possibles.

Les options sont éditées une fois l\'élément créé.

Les champs et zones de texte n\'admettent pas d\'options
';

$string['options_help'] = '
Les options sont les différentes valeurs de critères de qualification.

Les options sont définis par les propriétés suivantes :

### Un nom

Le nom sert à identifier l\'option. Il doit être constitué sans caractères spéciaux, sans accents ni espaces. Le nom n\'apparait à aucun moment sur l\'interface du tracker. On peut le considérer aussi comme le "code" technique pour cette valeur du critère.

### La description

La description est un texte qui est utilisé lorsqu\'il faut faire mention de l\'option sur l\'interface.

### Ordre des options

Vous pouvez définir l\'ordonnancement des options. Cet ordre détermine comment les listes ou les différents choix sont présentés aux utilisateurs.

Les champs et zones de texte n\'admettent pas d\'options
';


$string['ticketprefix_help'] = '
Ce paramètre permet de préfixer une chaîne constante devant les identifiants numériques des défauts. Ceci permet
une meilleure identification et communication pendant la résolution de défauts.
';

$string['urgentquery_help'] = '
Cocher cette case peut donner un signal auw développeurs ou aux gestionnaires du support pour prendre en compte votre demande plus rapidement.

Attention cependant, il n\'existe aucune procédure automatisée qui considère ce paramètre. La prise en compte de ce degré d\'urgence reste
à la discretion des personnes chargées d\'évaluer ces demandes.
';

$string['mods_help'] = '
Ce module permet à un administrateur ou un opérateur technique de récolter localement les défauts et 
dysfonctionnements de la plate-forme. Il peut être utilisé dans le cadre de l\'exploitation de Moodle, mais également
comme outil de résolution de défaut dans le cas général. Il peut être instancié plusieurs fois dans le même cours
comme un module d\'activité.

La fiche de description de défaut est paramétrable. Il est possible de définir les rubriques que l\'on souhaite
faire détailler par les utilisateurs. Le moteur de recherche intégré tient compte de ce paramétrage. 
';

$string['enablecomments_help'] = '
Si cette option est active, les enregistrements de défaut peuvent être commentés par le public autorisé à lire les fiches.
';

$string['allownotifications_help'] = '
Ce paramètre permet d\'activer ou inhiber les notifications par courrier. Si elles sont activées, certains événements
dans le traqueur de défauts peuvent conduire à l\'émission d\'un courriel vers les utilisateurs concernés.
';

$string['defaultassignee_help'] = '
Vous pouvez demander à ce que les tickets entrants soient assignés par défaut à un des résolveurs. Ceci n\'empêche pas 
la notification aux gestionnaires de tickets.
';

$string['strictworkflow_help'] = '
Lorsqu\'activé, chaque rôle (interne au regard du gestionnaire, rapporteur, développeur, résolveur ou responsable) n\'aura accès qu\'aux états correspondant à ce rôle.
';
