# Team workflow

This branch contains shared infrastructure and Sofiene's Producer/Farm module.
The remaining modules belong on their owners' feature branches.

| Branch | Purpose |
| --- | --- |
| `main` | Tested stable foundation and releases |
| `develop` | Team integration |
| `feature/sofiene-production` | Sofiene's Producer/Farm work |
| `feature/mariem-harvest-mill` | Mariem's future Harvest/Mill work |
| `feature/oussema-lab-certification` | Oussema's future Laboratory/Certification work |
| `feature/hana-distribution` | Hana's future Product/Distribution work |
| `feature/aymen-consumer-feedback` | Aymen's future Consumer/Feedback/Complaints work |

**Ownership clarification:** Sofiene's latest request explicitly follows
`OliveTrace_Final_Team_Specification.pdf`: Producer/Farm belongs to Sofiene and
Harvest/Mill to Mariem. This supersedes the earlier swap in the shared-base notes.

The Eloquent refactor is currently on `feature/sofiene-production`; `main` and
`develop` are unchanged until integration. See the README update commands to
check out and test this branch. Use Eloquent for new modules and coordinate any
integration of this branch into existing feature work.

After the shared changes are merged, start your branch from current `develop`:

```powershell
git fetch origin
git switch develop
git pull --ff-only origin develop
git switch -c feature/sofiene-production
```

Replace the final branch name with your own. Make small commits in your own words,
push the feature branch, and open a pull request targeting **develop**. Include the
problem solved, resulting behavior, schema/model changes, and tests run.
Review and integrate on develop; promote a tested develop branch to main through
another pull request. Do not push unreviewed feature work directly to main.

Before opening a pull request:

```powershell
composer install
npm ci
npm run build
php artisan migrate
php artisan olivetrace:check-database
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
