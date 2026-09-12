# artifact-release

```text
 _   _  ___ _ __  _   _ ___     _  __     __  _   _ __
|_| |_)  |  | |_ |_| /   |     |_) |_ |   |_ |_| (_ |_
| | | \  |  | |  | | \_  |     | \ |_ |__ |_ | | _) |_
```

Small Docker Compose staging coordinator for Git-based CI.

Define a staging overlay once, then reuse it locally or from CI for feature branches and pull requests.

## What it does

`artifact-release` wraps a base Compose file and a staging overlay with a stable project name and staging env file.

```text
compose.yaml
+ compose.staging.yaml
+ .env.staging
+ ARTIFACT_RELEASE_PROJECT
= one isolated staging environment
```

Commands:

```bash
./artifact-release validate
./artifact-release up
./artifact-release status
./artifact-release down
./artifact-release destroy
./artifact-release config
```

## Quick start

Requirements:

- Docker with Compose v2
- Bash

Clone the repository, create the staging env file, and start the example:

```bash
cp .env.staging.example .env.staging
export ARTIFACT_RELEASE_PROJECT=artifact-release-demo

./artifact-release validate
./artifact-release up
```

Open:

- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- Mailpit: http://localhost:8025

Stop the preview but keep its database volume:

```bash
./artifact-release down
```

Delete the preview including its volumes:

```bash
./artifact-release destroy
```

## Environment setup

The example keeps non-secret defaults in `.env.staging.example`.

```dotenv
STAGING_APP_URL=http://localhost:8080
STAGING_DB_UI_URL=http://localhost:8081

APP_PORT=8080
PHPMYADMIN_PORT=8081
MAILPIT_UI_PORT=8025

DB_DATABASE=artifact_release
DB_USERNAME=artifact_release
DB_PASSWORD=change-me
DB_ROOT_PASSWORD=change-root-me
```

Create the real file locally:

```bash
cp .env.staging.example .env.staging
```

Then change the passwords and URLs. `.env.staging` is ignored by Git.

The environment name is separate from `.env.staging`:

```bash
export ARTIFACT_RELEASE_PROJECT=my-app-feature-login
```

Keep that project name stable for subsequent deployments of the same preview. A new commit should update the same environment, not create a new database.

Optional file overrides:

```bash
export ARTIFACT_RELEASE_BASE_COMPOSE=compose.yaml
export ARTIFACT_RELEASE_STAGING_COMPOSE=compose.staging.yaml
export ARTIFACT_RELEASE_ENV_FILE=.env.staging
```

## Example application

The included example deliberately uses a very small PHP application.

`compose.yaml` defines:

- PHP 8.4 + Apache
- MariaDB
- persistent database volume

`compose.staging.yaml` adds or changes:

- `APP_ENV=staging`
- branch/preview application URL
- staging database credentials
- Mailpit SMTP configuration
- phpMyAdmin
- Mailpit
- local example ports

The important part is that the staging overlay owns the staging-specific behavior instead of duplicating the entire application definition.

## Using it in your repository

Copy `artifact-release` into your repository and keep these files beside your existing Compose application:

```text
artifact-release
compose.yaml
compose.staging.yaml
.env.staging.example
.github/workflows/staging.yaml
```

For a Laravel-style application, for example, the staging overlay might contain:

```yaml
services:
  app:
    environment:
      APP_ENV: staging
      APP_DEBUG: "false"
      APP_URL: ${STAGING_APP_URL:?set STAGING_APP_URL}
      DB_HOST: db
      DB_DATABASE: ${DB_DATABASE:?set DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME:?set DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD:?set DB_PASSWORD}
      MAIL_MAILER: smtp
      MAIL_HOST: mailpit
      MAIL_PORT: 1025
```

Your real application may also need workers, queues, cache services, migrations, storage setup, or framework-specific config cache handling. Keep those application-specific details in your repository rather than hiding them inside Artifact Release.

## GitHub Actions example

The repository includes `.github/workflows/staging.yaml`.

It demonstrates this model:

```text
PR opened/updated
  -> checkout exact revision
  -> build/test in your normal CI
  -> create .env.staging from GitHub environment/secrets
  -> choose stable ARTIFACT_RELEASE_PROJECT
  -> ./artifact-release validate
  -> ./artifact-release up
```

Configure these GitHub environment secrets for the example:

```text
STAGING_DB_PASSWORD
STAGING_DB_ROOT_PASSWORD
```

The workflow writes those values into `.env.staging` only on the runner.

The included workflow uses `self-hosted` because Docker Compose needs to run on the machine that owns the staging environment. In a real setup, that runner should be a dedicated staging host or communicate with one explicitly.

The example workflow is intentionally minimal. Your application CI should run tests and build/pull the exact application images before deployment.

## How isolation works

Docker Compose namespaces its normal containers, networks, and volumes by project name.

For example:

```bash
export ARTIFACT_RELEASE_PROJECT=ar-123456-pr42
./artifact-release up
```

and:

```bash
export ARTIFACT_RELEASE_PROJECT=ar-123456-pr43
./artifact-release up
```

create separate Compose projects.

Avoid globally shared resource names in the base Compose file, including:

- `container_name`
- fixed named volumes shared across projects
- external networks unless intentionally shared
- fixed host ports when multiple previews run on one host
- shared bind mount paths for mutable data

For many concurrent previews, use a reverse proxy/ingress instead of allocating fixed host ports as shown in this localhost example.

## Safety

A staging deployment executes the repository's Compose definition on a Docker host. Treat deployment access as privileged.

Do not expose deployment credentials to untrusted fork pull requests. Do not deploy unreviewed `pull_request_target` checkout content onto a shared Docker host.

`destroy` removes the Compose project's volumes. Use it only when you really want to delete that preview's persistent database.

## License

MIT
