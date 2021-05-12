# zord-core
Framework générique pour développement de portails

# Install
http://user.zord.tech/doku.php?id=documentation:install

# Cadre métier

Édition numérique de textes littéraires. Publication de textes et outils de recherche scientifiques dans les contenus
Lien avec les normes et outils des lecteurs, chercheurs, bibliothécaires, distributeurs : enregistrements de références bibliographiques, citations, annotations, fiches de métadonnées normées…

# Objectif de Zord

Zord est un logiciel destiné à :

* la publication en ligne de textes XML
* le data-mining dans ces XML sémantisés

Il a été conçu à l'origine pour la publication de livres : parties/chapitres.
Il devra évoluer vers la publication de revues : numéros/articles et de dictionnaires (encyclopédies) : entrées/index
Portails

Une instance de Zord permet de créer plusieurs portails, chacun présentant un corpus de textes. Un même texte peut être publié dans plusieurs portails. Le text-mining peut être transversal à plusieurs portails.
Plusieurs occurrences du logiciel supportant chacune plusieurs portails web distincts peuvent avoir des fonctionnalités transversales entre elles (telle que la recherche terminologique simultannée dans le corpus de deux éditeurs différents).
Humanités numériques (Digital Humanities)

Fait référence aux chercheurs en humanités (sociologie, littérature, philosophie, linguistique…) au moyen d'outils numériques.
Zord a pour vocation d'être un outil à disposition des chercheurs en littérature, histoire, histoire du livre ou n'importe quelle discipline pour fouiller les textes de manière scientifique. D'où :

* XML sémantique
* outils de recherche et de visualisation des résultats de la recherche.

# Niveau potentiel de complexité des textes

* textes littéraires encodés en XML-TEI,
* essais, correspondances, théâtre, dialogues, dictionnaires, traductions…,
* plusieurs niveaux de notes. J'usqu'à trois : notes d'édition, variantes, gloses,
* textes en vis-à-vis : visualisation en vis-à-vis,
* alphabets latin et non latins : grec, hébreu, slave… : UTF8,
* prose et/ou vers numérotés,
* bibliographies,
* insertion des numéros de page du texte original (textes issus d'ouvrages papier ou pdf),
* correspondance folio/PDF, page à page.

# Licence et dépôt

Licence LGPL v.3 : https://github.com/DrozNumerique/Zord/blob/master/LICENSE
Les sources du logiciel ainsi que sa documentation seront déposée et régulièrement mises à jour sur le dépôt Git de Zord :

* https://github.com/DrozNumerique/zord-core : Framework générique pour développement de portails
* https://github.com/DrozNumerique/zord-library : Library application build upon Zord framework
* https://github.com/DrozNumerique/zord-store : composants communs dédiés à la gestion d'un référentiel d'ouvrages 

La paternité du code est attribuée à ses développeurs David Dauvergne, Éric Arrivé, est n'est en aucun cas cessible. S'y ajouteront les coorodonnées de ses futurs développeurs.

# Supports cibles et responsive design

Le prestataire doit assurer un design s'adaptant aux différents supports de lecture : PC, tablettes .

Multi-liguisme

L'ensemble des pages et fonctionnalités de chacun des sites devra pouvoir être décliné et plusieurs langues. Le site sera nativement construit de manière bilingue fr/en.
