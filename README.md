<p align="center">
    <img align="center" src="https://cloud.githubusercontent.com/assets/4491532/21667795/e69dec6e-d2c9-11e6-8563-133291489ed3.png" width="45%">           
</p>

---
<p align="center"><i>Framework PHP élégant, simple, léger, plaisant et flexible</i></p>

---

[![Maintainability](https://api.codeclimate.com/v1/badges/6981c700b82a43834672/maintainability)](https://codeclimate.com/github/dadajuice/zephyrus/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/6981c700b82a43834672/test_coverage)](https://codeclimate.com/github/dadajuice/zephyrus/test_coverage)
[![codecov](https://codecov.io/gh/dadajuice/zephyrus/branch/master/graph/badge.svg)](https://codecov.io/gh/dadajuice/zephyrus)
[![Travis](https://travis-ci.org/dadajuice/zephyrus.svg?branch=master)]()
[![StyleCI](https://styleci.io/repos/77175312/shield?branch=master)](https://styleci.io/repos/77175312)
[![GitHub issues](https://img.shields.io/github/issues/dadajuice/zephyrus.svg)]()
[![GitHub release](https://img.shields.io/github/release/dadajuice/zephyrus.svg)]()

# Philosophie
Bienvenu dans le Framework Zephyrus! Ce framework est fondé sur un modèle pédagogique en s'orientant sur une structure MVC simple, une approche de programmation flexible laissant place à une extensibilité pour tous types de projet, une forte considération pour la sécurité applicative et une liberté de développement. Le tout offert depuis un noyeau orienté-objet élégant favorisant l'écriture d'un code de qualité propre et maintenable. Développement avec une philosophie de maintenir un plaisir à programmer en n'étant pas rigoureusement strict sur une utilisation figée où tout doit passer par une configuration et y être limité. Zephyrus s'insère à mi-chemin entre les plus petits frameworks et les monstres pour ainsi répondre aux besoins de la plupart des projets.

# Quelques caractéristiques générales
* Une **structure de projet simple et intuitive** basée sur une architecture Model-View-Controller. 
* Traitement des vues avec le préprocesseur HTML _[Pug](https://github.com/pug-php/pug)_ nativement intégré ou simplement du PHP natif.
* Approche pédagogique pour la conception élégante de classes et favorise une rétrocompatibilité avec les fonctionnalités natives de PHP comme l'utilisation des super-globales, de la session et autres.
* Routeur de requêtes simple et flexible basé sur des contrôleurs incluant une intégration facile de middlewares dans le flux d'une requête et d'un contrôleur du projet. Facilite la segmentation des responsabilités et la lecture d'une chaîne d'exécution.
* Plusieurs mécanismes de sécurité intégrés tel que les entêtes CSP, les jetons CSRF, protection XSS, détection d'intrusion (_[Expose](https://github.com/enygma/expose)_), mécanisme d'authorisation et plus encore!
* Philosophie d'accès aux données depuis des courtiers manuellement définis offrant un contôle complet sur la construction des requêtes SQL et, par conséquent, une facilité de maintenance et d'optimisation.
* Approche simple pour intégrer des recherches, tris et pagination sur les requêtes manuelles.
* Système de validation de formulaires élégant et facilement extensible offrant une multitude de règles nativement sur les nombres, les chaînes, les fichiers téléversés, les dates, etc.
* Moteur unique simple et optimisé pour la gestion des chaînes de caractères depuis une structure JSON, le tout facilement organisé pour offrir une internationalisation.
* Configuration d’un projet rapide et flexible permettant des paramètres personnalisées utilisables facilement. 
* Hautement extensibles facilement grâce à sa compatibilité avec les modules Composer.
* Plusieurs utilitaires rapides : cryptographie, validations, système de fichiers, gestionnaire d'erreurs, transport de messages, etc.
* Et plus encore !

# Version API
Cette version initialise un projet Zephyrus avec une structure organisée pour répondre directement au besoin d'un API. C'est-à-dire préparer des mécanismes de clés d'API et de token ainsi qu'une organisation d'un contrôleur abstrait initiale pour manipuler les réponses JSON facilement. Le tout en limitant les fonctionnalités non nécessaires pour un API tel que le moteur de vue et la session.

# Installation
Zephyrus nécessite PHP 7.1 ou plus. Présentement, supporte uniquement Apache comme serveur web (pour un autre type de serveur, il suffirait d’adapter les fichiers .htaccess). Le gestionnaire de dépendance [Composer](https://getcomposer.org/) est également requis. La structure résultante de l’installation contient plusieurs exemples pour faciliter les premiers pas.

#### Option 1 : Installation depuis composer (recommandé)
```
$ composer create-project zephyrus/framework-api nom_projet
```

#### Option 2 : Depuis une archive
```
$ mkdir nom_projet
$ cd nom_projet
$ wget https://github.com/dadajuice/zephyrus-framework-api/archive/vx.y.z.tar.gz
$ tar -xvf vx.y.z.tar.gz --strip 1
$ composer install
```

#### Option 3 : Depuis les sources (version de développement pour faire un PR par exemple)
```
$ git clone https://github.com/dadajuice/zephyrus-framework.git
$ composer install  
```

# Utilisation

#### Exemple 1 : Obtenir une liste et un détail depuis la base de données (simple)

app/Models/Brokers/ClientBroker.php
```php
<?php namespace Models\Brokers;

class ClientBroker extends Broker
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM client");
    }

    public function findById($clientId): ?\stdClass
    {
        return $this->selectSingle("SELECT * FROM client WHERE client_id = ?", [$clientId]);
    }
}
```

app/Controllers/ExampleBroker.php
```php
<?php namespace Controllers;

use Models\Brokers\ClientBroker;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {      
        $this->get("/clients", "index");
        $this->get("/clients/{id}", "read");
    }

    public function index()
    {
        $broker = new ClientBroker();       
        return $this->success(['clients' => $broker->findAll()]);
    }

    public function read($clientId)
    {
        $broker = new ClientBroker();
        $client = $broker->findById($clientId);
        if (is_null($client)) {
            return $this->abortNotFound();  
        }
        return $this->success(['client' => $client]);
    }
}
```

#### Exemple 2 : Obtenir une liste avec recherche et tri

app/Models/Brokers/ClientBroker.php
```php
<?php namespace Models\Brokers;

use Zephyrus\Database\Core\Database;

class ClientBroker extends Broker
{
    // Redéfinition du constructeur pour intégrer une configuration globale
    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
    
        // Défini dans un tableau la liste des colonnes de la table sur lesquelles
        // appliquer une recherche.
        parent::setSearchableFields(['name', 'city']);

        // Défini dans un tableau associatif sur quelle colonne appliquer un tri
        // selon le paramètre saisie dans la requête. 
        parent::setSortableFields(['ville' => 'city']);
    }

    public function findAll(): array
    {
        return $this->select("SELECT * FROM client");
    }

    ...
}
```

app/Controllers/ExampleBroker.php
```php
<?php namespace Controllers;

use Models\Brokers\ClientBroker;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {      
        $this->get("/clients", "index");
        ...
    }

    public function index()
    {
        $broker = new ClientBroker();  
        // Intégrer automatiquement les possibles filtres de recherche et tris. Le paramètre de requête "search" 
        // applique la recherche, le paramètre "sort" un tri et le paramètre "order" applique un ordre au tri.      
        $broker->applyFilter(); 
        return $this->success(['clients' => $broker->findAll()]);
    }
}
```

Requête pour tous les clients sans filtre.
```
/clients
```

Requête pour tous les clients qui ont une occurence de "Ron" soit dans leur nom ou leur ville (non sensible à la case).
```
/clients?search=Ron
```

Requête qui applique un tri sur la ville.
```
/clients?sort=ville
```

Requête qui applique un tri sur la ville décroisant.
```
/clients?sort=ville&order=desc
```

Requête qui effectue la recherche et applique un tri sur la ville décroissant.
```
/clients?search=Ron&sort=ville&order=desc
```

#### Exemple 3 : Traitement d'une insertion avec validation

```php
<?php namespace Controllers;

use Models\Brokers\UserBroker;
use Zephyrus\Application\Rule;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {              
        ...
        $this->post("/users", "insert");
    }

    public function insert()
    {
        // Construire un objet Form depuis les données de la requête
        $form = $this->buildForm();
    
        // Appliquer une série de règles de validation sur les champs nécessaires. Il existe une grande quantité
        // de validations intégrés dans Zephyrus. Consulter les Rule:: pour les découvrir.
        $form->validate('firstname', Rule::notEmpty("Firstname must not be empty"));
        $form->validate('lastname', Rule::notEmpty("Lastname must not be empty"));
        $form->validate('birthdate', Rule::date("Date is invalid"));
        $form->validate('email', Rule::email("Email is invalid"));
        $form->validate('password', Rule::passwordCompliant("Password does not meet security requirements"));
        $form->validate('password_confirm', Rule::sameAs("password", "Password doesn't match"));
        $form->validate('username', Rule::notEmpty("Username must not be empty"));
        $form->validate('username', new Rule(function ($value) {
            return $value != "admin";
        }, "Username must not be admin!"));

        // Si la vérification échoue, retourner les messages d'erreur
        if (!$form->verify()) {            
            return $this->error($form->getErrorMessages());
        }

        // Effectuer le traitement si aucune erreur n'est détectée (dans ce cas, ajouter l'utilisateur depuis
        // un courtier et obtenir le nouvel identifiant).
        $clientId = (new UserBroker())->insert($form->buildObject());

        // Retourner au client l'identifiant du nouvel utilisateur
        return $this->success(['client_id' => $clientId]);
    }
}
```

#### Exemple 4 : Fonctionnement des requêtes avec les token api

Le contrôleur démo inclut avec l'installation contient un exemple de requête pour effectuer une authentification `/login` qui permet
d'obtenir un token pour ensuite consulter la route `/`.

app/Controllers/ExampleController.php
```php
<?php namespace Controllers;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {
        $this->get("/", "index");
        $this->post("/login", "login");
    }

    public function index()
    {
        // Il ne sera pas possible d'obtenir ces données sans un TOKEN valide dans la requête qui peut
        // être soit dans l'url ou l'entête HTTP (voir la configuration).
        return $this->success([
            'confidential_data' => 'Bruce Wayne is Batman',
            'userId' => $this->resourceIdentifier
        ]);
    }

    public function login()
    {       
        $userId = $this->authenticate();
        if ($userId < 1) {
            return $this->error(["Login failed!"]);
        }

        // Appliquer l'identifiant de la resource authentifié pour utilisation subséquente
        $this->resourceIdentifier = $userId;
        return $this->success();
    }

    /**
     * Exemple de validation d'authentification.
     *
     * @return int
     */
    private function authenticate(): int
    {
        $username = $this->request->getParameter('username');
        $password = $this->request->getParameter('password');
        if ($username == 'bob' && $password == 'Omega123') {
            return 1;
        }
        if ($username == 'lewis' && $password == 'Omega123') {
            return 2;
        }
        return 0;
    }
}
```

La configurations par défaut contient une section key et token. Il est possible de modifier les noms des 
paramètres au besoin. MODIFIER OBLIGATOIREMENT LA CLÉ. Il est également possible de complètement désactiver
la clé et le token selon vos besoins d'API.

config.ini
```
...

[key]
enable = true
key = "d03641d3c6432a9eb50994506339e227"
parameter_name = "apikey"
header_name = "X-API-KEY"

[token]
enable = true
parameter_name = "token"
header_name = "X-API-TOKEN"
login_route = "/login"
expiration = 86400
force_forbidden = false

...
``` 

Dans un logiciel comme Postman, définissez une requête vers `/login` et `/`.

La requête `/login` en prenant soin d'inclure l'entête X-API-KEY ainsi que les paramètres username et password avec les
valeurs respectives "bob" et "Omega123", va produire la réponse suivante: 

```json
{
    "status": "success",
    "token": "IDeL8UucYh2k6EOKr3w63qSqD5MBlgLale6sr6m5QioYuW9ij8ytxM3YuXH9GKp9|1"
}
```

Le TOKEN est valide pour UNE SEULE requête! À chaque requête, le serveur va automatiquement retourner un nouveau
TOKEN à chaque réponse. L'idée est donc de toujours garder en mémoire dans le logiciel client le dernier TOKEN reçu
pour le renvoyer lors de la prochaine requête.

La requête `/` en prenant soin d'ajouter le TOKEN dans les paramètres va produire la réponse suivante:

```json
{
    "status": "success",
    "confidential_data": "Bruce Wayne is Batman",
    "userId": "1",
    "token": "s3HOF7TS2DeNkOCK9EJqyuUc20sF2TBjszf4RkJGldwAPgDXRcMbU5HZZhUfP7ns|1"
}
```

Réutiliser le même TOKEN va produite la requête suivante:
```json
{
    "status": "error",
    "errors": [
        "Token value does not match"
    ]
}
```

La classe Token est disponible directement dans le projet ce qui la rend facilement modifiable selon les besoins de 
votre API. Vous pouvez désactiver complètement le traitement du TOKEN en mettant la configuration enabled à false dans
la section `[token]` du fichier config.ini.

# Contribution

#### Sécurité
Veuillez communiquer en privé pour tout problème pouvant affecter la sécurité des applications créées avec ce framework.

#### Bogues et fonctionnalités
Pour rapporter des bogues, demander l’ajout de nouvelles fonctionnalités ou faire des recommandations, n’hésitez pas à utiliser l’[outil de gestion des problèmes](https://github.com/dadajuice/zephyrus-framework-api/issues) de GitHub.

#### Développement
Vous pouvez contribuer au développement de Zephyrus en soumettant des [PRs](https://github.com/dadajuice/zephyrus-framework-api/pulls).

# License
MIT (c) David Tucker