# Branding 
- Logo
- Nom
- Design
- Slogan

# Signup 
- nom, prenom, email, password (optionel #telephone)
- confirmation de mot de passe
- confirmation de courriel

# login
- email, password
- MFA (selon la config du compte)
    - courriel
    - sms
    - one time password authenticator app
    - Temps de grâce de 20 jours. (Pas demander MFA pendant 20 jours).

# profile
- modifier informations de compte, (nom, prenom, #telephone, password) pas le courriel.
- Activer / Désactiver les MFA (bit-wise mask)
- Image de profile (setup et/ou modification)

# Gestion des mots de passes
- Ajouter un mot de passe (Site web / Service, username, mot de passe) ex : omnivox/username/password
- Modifier un mot de passe
- Supprimer un mot de passe (Modal de confirmation)
- Gérer les mots de passe (afficher les mots de passe)

# Extra

- Le partage de mot de passe. 
    - Préciser le courriel avec qui partagé