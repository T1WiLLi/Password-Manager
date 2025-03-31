# Conception - Sécurité

## Comment assurer la sécurité dans l'application ? 

---

- ## **Authentification Multifacteur**: 
    L'authentification multifacteur doit au moins être comprise de deux facteurs différent
    - Le facteur de connaissance : Mot de passe.
    - Le facteur de possession : Un email avec un code de vérification.
    - Le facteur de localisation : Un code envoyer par email, lorsque la connection est faite depuis un nouvel emplacement.

- ## **Cryptage des données**:
    L'application à pour but d'enregistrer des mots de passes de manière extrêmement sécurisé. De ce fait, les mots de passe enregistrer dans l'application devront être encrypter, nous pouvons utiliser AES-256 pour cela avec un chiffrement symétrique, avec comme mode d'opération CBC. Étant donner que nous allons gérer plusieurs utilisateur et dans un but de sécurité, la clé d'encryption vas découler du mot de passe de l'utilisateur qui représente la clé. Cela nous permet de ne pas stocker la clé d'encryption en base de données.

- ## **Mise à jour de la clé d'encryption**:
    Lorsque l'utilisateur change son mot de passe, la clé d'encryption doit être mise à jour. Nous pouvons utiliser un algorithme de hachage comme SHA-256 pour hacher le mot de passe, puis utiliser le résultat comme clé d'encryption. Cela nous permet de mettre à jour la clé d'encryption sans avoir à stocker la clé d'encryption en base de données. Il nous faudra également décrypter les données enregistrées avec la clé d'encryption précédente, puis les re-
    crypter avec la nouvelle clé d'encryption. Cela nous permet de garantir que les données sont toujours sécurisées. De plus, le JWE devra être mis à jour avec la nouvelle clé d'encryption.