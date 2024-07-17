Serving Assets in dev vs prod

In the dev environment, the URL /assets/images/duck-3c16d9220694c0e56d8648f25e6035e9.png is handled and returned by your Symfony app.

For the prod environment, before deploy, you should run:


`php bin/console asset-map:compile`

This will physically copy all the files from your mapped directories to public/assets/ so that they're served directly by your web server.
See Deployment for more details.

Debugging: Seeing All Mapped Assets

To see all of the mapped assets in your app, run:

`php bin/console debug:asset-map`



Feature
- Créer un cron pour enregistrer le log dans la base données
- Regrouper les pointages par hebdomadaire
- Regrouper les pointages par mensuel
- Calculer les temps par regroupement
