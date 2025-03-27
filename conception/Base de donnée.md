# Conception - Base de données 

## Comment structurer la base de données pour stocker les informations de l'utilisateur et les mot de passes ?

---

- ## **Table Users**: 
    - **id** (clé primaire) : identifiant unique de l'utilisateur
    - **username** : nom d'utilisateur (Encrypté)
    - **email** : adresse e-mail de l'utilisateur (Encrypté)
    - **password** : mot de passe de l'utilisateur (stocké sous forme HASHÉ)
    - **salt**: valeur aléatoire utilisée pour le hashage du mot de passe
    - **is_verified**: indique si l'utilisateur a vérifié son adresse e-mail
    - **created_at**: date de création de l'utilisateur
    - **updated_at**: date de dernière mise à jour de l'utilisateur

- ## **Table Passwords**:
    - **id** (clé primaire) : identifiant unique du mot de passe
    - **user_id** : identifiant de l'utilisateur associé au mot de passe
    - **service_name**: nom du service pour lequel le mot de passe est utilisé
    - **password**: mot de passe (Encrypté avec AES-256-CBC)
    - **created_at**: date de création du mot de passe
    - **last_used**: date de dernière utilisation du mot de passe

- ## **Table LoginAttempts**: 
    - **id** (clé primaire) : identifiant unique de l'essai de connexion
    - **user_id** : identifiant de l'utilisateur associé à l'essai de connexion
    - **ip_address**: adresse IP de l'utilisateur lors de l'essai de connexion
    - **login_time**: date et heure de l'essai de connexion
    - **status**: indique si l'essai de connexion a été réussi ou non (@enum"success, failed")
    - **location**: emplacement géographique de l'utilisateur lors de l'essai de connexion

- ## **Table EmailVerification**: 
    - **id** (clé primaire) : identifiant unique de la vérification d'adresse e-mail
    - **user_id** : identifiant de l'utilisateur associé à la vérification d'adresse e-mail
    - **token**: token de vérification d'adresse e-mail
    - **created_at**: date de création de la vérification d'adresse e-mail


