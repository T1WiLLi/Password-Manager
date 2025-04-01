
# Conception - Base de données

## Comment structurer la base de données pour stocker les informations de l'utilisateur et les mot de passes ?

----------

-   ## **Table Users**:
    
    -   **id** (clé primaire) : identifiant unique de l'utilisateur
    -   **username** : nom d'utilisateur (Encrypté)
    -   **email** : adresse e-mail de l'utilisateur (Encrypté)
    -   **password** : mot de passe de l'utilisateur (stocké sous forme HASHÉ)
    -   **salt**: valeur aléatoire utilisée pour le hashage du mot de passe
    -   **is_verified**: indique si l'utilisateur a vérifié son adresse e-mail
    -   **created_at**: date de création de l'utilisateur
    -   **updated_at**: date de dernière mise à jour de l'utilisateur
    -   **first_name**: prénom de l'utilisateur (Encrypté)
    -   **last_name**: nom de famille de l'utilisateur (Encrypté)
    -   **phone_number**: numéro de téléphone de l'utilisateur (Optionnel, Encrypté)
    -   **profile_image**: chemin vers l'image de profil de l'utilisateur ou données d'image
-   ## **Table Passwords**:
    
    -   **id** (clé primaire) : identifiant unique du mot de passe
    -   **user_id** : identifiant de l'utilisateur associé au mot de passe
    -   **service_name**: nom du service pour lequel le mot de passe est utilisé
    -   **username**: nom d'utilisateur pour le service
    -   **password**: mot de passe (Encrypté avec AES-256-CBC)
    -   **created_at**: date de création du mot de passe
    -   **last_used**: date de dernière utilisation du mot de passe
    -   **updated_at**: date de dernière mise à jour du mot de passe
-   ## **Table LoginAttempts**:
    
    -   **id** (clé primaire) : identifiant unique de l'essai de connexion
    -   **user_id** : identifiant de l'utilisateur associé à l'essai de connexion
    -   **ip_address**: adresse IP de l'utilisateur lors de l'essai de connexion
    -   **user_agent**: informations sur le navigateur et l'appareil utilisé pour la connexion
    -   **login_time**: date et heure de l'essai de connexion
    -   **status**: indique si l'essai de connexion a été réussi ou non (@enum"success, failed")
    -   **location**: emplacement géographique de l'utilisateur lors de l'essai de connexion
-   ## **Table EmailVerification**:
    
    -   **id** (clé primaire) : identifiant unique de la vérification d'adresse e-mail
    -   **user_id** : identifiant de l'utilisateur associé à la vérification d'adresse e-mail
    -   **token**: token de vérification d'adresse e-mail
    -   **created_at**: date de création de la vérification d'adresse e-mail
    -   **expires_at**: date d'expiration de la vérification d'adresse e-mail
-   ## **Table MFAMethods**:
    
    -   **id** (clé primaire) : identifiant unique de la méthode MFA
    -   **user_id** : identifiant de l'utilisateur associé
    -   **method_type**: type de méthode MFA (@enum "email", "sms", "authenticator")
    -   **is_enabled**: indique si cette méthode est activée
    -   **last_verification**: date de la dernière vérification avec cette méthode
    -   **verification_data**: données nécessaires pour la vérification (secret pour authenticator, numéro de téléphone pour SMS, etc.)
    -   **grace_period_until**: date de fin de la période de grâce de 20 jours
-   ## **Table PasswordSharing**:
    
    -   **id** (clé primaire) : identifiant unique du partage
    -   **password_id** : identifiant du mot de passe partagé
    -   **owner_id** : identifiant de l'utilisateur propriétaire du mot de passe
    -   **shared_with_id** : identifiant de l'utilisateur avec qui le mot de passe est partagé
    -   **created_at**: date de création du partage
    -   **updated_at**: date de dernière mise à jour du partage