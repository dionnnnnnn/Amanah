# Déploiement automatique vers Infomaniak

Le workflow [`.github/workflows/deploy-infomaniak.yml`](../.github/workflows/deploy-infomaniak.yml) déploie chaque push sur `main` vers l'hébergement Infomaniak.

## 1. Préparer le dépôt Git sur Infomaniak

Depuis la console SSH Infomaniak :

```bash
mkdir -p ~/git_depot
cd ~/git_depot
git init --bare amanah.git
git --git-dir=amanah.git update-server-info
```

Le dossier publié doit rester `amanah/public`, jamais le dossier parent. Le chemin réel du dépôt est généralement de la forme :

```text
/home/clients/123456789/git_depot/amanah.git
```

## 2. Créer une clé dédiée au déploiement

Sur la machine locale, générer une clé Ed25519 distincte des clés personnelles :

```bash
ssh-keygen -t ed25519 -f ~/.ssh/amanah-infomaniak -C "amanah-github-actions"
```

Ajouter le contenu de `~/.ssh/amanah-infomaniak.pub` dans `authorized_keys` du compte SSH Infomaniak. Infomaniak recommande Ed25519 et ne prend pas en charge le transfert d'agent SSH.

Vérifier la connexion et relever l'empreinte du serveur :

```bash
ssh-keyscan -t ed25519 x0000.ftp.infomaniak.com
```

Comparer cette empreinte avec une connexion vérifiée, puis enregistrer la ligne complète dans le secret `INFOMANIAK_KNOWN_HOSTS`.

## 3. Ajouter les secrets GitHub

Dans `Settings → Secrets and variables → Actions`, ajouter :

| Secret | Valeur |
|---|---|
| `INFOMANIAK_SSH_KEY` | contenu privé de `amanah-infomaniak` |
| `INFOMANIAK_KNOWN_HOSTS` | ligne vérifiée issue de `ssh-keyscan` |
| `INFOMANIAK_USER` | utilisateur FTP/SSH Infomaniak |
| `INFOMANIAK_HOST` | hôte SSH, par ex. `x0000.ftp.infomaniak.com` |
| `INFOMANIAK_REPO_PATH` | chemin absolu, par ex. `/home/clients/123456789/git_depot/amanah.git` |
| `INFOMANIAK_TARGET_DIR` | dossier de l'application, par ex. `/home/clients/123456789/web/amanah` |

Le dossier cible du site doit être configuré dans le Manager Infomaniak sur :

```text
/home/clients/123456789/web/amanah/public
```

## 4. Déclenchement et retour arrière

Après configuration, un `git push origin main` lance le workflow. Il pousse d'abord le commit vers le dépôt bare Infomaniak, puis clone cette version dans un dossier temporaire avant de synchroniser le dossier de l'application.

Le déploiement conserve une copie précédente dans `~/.deploy/amanah-previous`. Pour revenir en arrière, restaurer cette copie avec `rsync` après s'être connecté en SSH, puis vérifier le site et les journaux PHP.

Le workflow n'écrase pas `backend/.env` ni `backend/storage/`. Ces éléments doivent être créés et configurés directement sur Infomaniak.
