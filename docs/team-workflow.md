# Team workflow

The repository currently contains shared infrastructure only. The five business
modules belong on future feature branches; no feature branch is created for you.

| Branch | Purpose |
| --- | --- |
| `main` | Tested stable foundation and releases |
| `develop` | Team integration |
| `feature/mariem-producer-farms` | Mariem's future Producer/Farm work |
| `feature/sofiene-harvest-mill` | Sofiene's future Harvest/Mill work |
| `feature/oussema-lab-certification` | Oussema's future Laboratory/Certification work |
| `feature/hana-distribution` | Hana's future Product/Distribution work |
| `feature/aymen-consumer-feedback` | Aymen's future Consumer/Feedback/Complaints work |

**Ownership clarification:** `OliveTrace_Final_Team_Specification.pdf` assigns
Producer/Farm to Sofiene and Harvest/Mill to Mariem. Sofiene's explicit base-project
request swaps those two owners. This repository follows the requested branch
names above; read the PDF's functional descriptions with that ownership swap.
The PDF's other functional contracts remain the reference for later work.

Start your branch from current `develop`:

```powershell
git fetch origin
git switch develop
git pull --ff-only origin develop
git switch -c feature/mariem-producer-farms
```

Replace the final branch name with your own. Make small commits in your own words,
push the feature branch, and open a pull request targeting **develop**. Include the
problem solved, resulting behavior, database/mapping changes, and tests run.
Review and integrate on develop; promote a tested develop branch to main through
another pull request. Do not push unreviewed feature work directly to main.

Before opening a pull request:

```powershell
composer install
npm ci
npm run build
php artisan migrate
php artisan olivetrace:check-doctrine
php artisan test
php vendor/bin/pint --test
git status
```

Keep `composer.lock` and `package-lock.json` committed. Do not update dependencies
as an incidental part of module work. Pull shared auth/layout/schema changes from
develop regularly and agree on cross-module foreign keys before writing both
sides. Coordinate changes to User, roles, layouts and shared routes with the team.

The repository owner can invite the four teammates from GitHub **Settings >
Collaborators**, using their GitHub usernames. Branch protections and collaborator
permissions should be agreed by the team; the base does not change account access.
