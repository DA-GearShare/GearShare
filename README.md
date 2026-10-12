# GearShare

GearShare is a web application for managing and reserving shared laboratory equipment. Users sign in with their GitHub account, browse the equipment inventory, and reserve items for a given time slot. Administrators maintain
equipment, equipment pools, photos and opening hours, and can follow reservation changes and transfers.

## Features

- Sign in via OAuth (GitHub)
- Equipment inventory with search, status and photos
- Reservations with multiple items, status tracking, change log and transfer log
- Condition photos (documenting equipment state at hand-over and return)
- Opening hours (recurring rules and exceptions)
- Notification log
- REST API with OpenAPI documentation

## Tech Stack

**Backend**

- PHP / [Symfony](https://symfony.com) with Doctrine ORM and Doctrine Migrations
- KnpU OAuth2 Client for GitHub login
- NelmioApiDocBundle (OpenAPI docs) and NelmioCorsBundle

**Frontend** (`frontend/`)

- [Vue 3](https://vuejs.org) with TypeScript, Vue Router and Pinia stores
- [Vite](https://vite.dev) as build tool
- Vitest for tests, ESLint for linting

**Infrastructure**

- Docker Compose (development and production configurations)

## Project Structure

```
src/            Symfony application (Controller, Entity, Enum, Repository, Security)
config/         Symfony configuration
migrations/     Doctrine migrations
frontend/       Vue 3 single-page application
docs/           API spec, DB schema
```

## Getting Started

### Prerequisites

- [Docker Compose](https://docs.docker.com/compose/install/) v2.10+
- OAuth credentials for GitHub (see `config/packages/knpu_oauth2_client.yaml` for the required
  environment variables) -> Store them in `.env.local`

### Development

1. Build fresh images: `docker compose build --pull --no-cache`
2. Start the containers: `docker compose up --wait`
3. Open `http://localhost`
4. Run the database migrations: `docker compose exec app bin/console doctrine:migrations:migrate`
5. Optional, load sample data: `docker compose exec app bin/console doctrine:fixtures:load`
6. Stop the containers: `docker compose down --remove-orphans`

## Documentation

- [`docs/api.yaml`](docs/api.yaml): OpenAPI specification
- [`docs/db.sql`](docs/db.sql): database schema

## License

Released under the MIT License, see [LICENSE](LICENSE).

## Credits

The Docker setup is based on [Symfony Docker](https://github.com/dunglas/symfony-docker)
by [Kévin Dunglas](https://dunglas.dev), co-maintained by [Maxime Helias](https://twitter.com/maxhelias) and sponsored
by [Les-Tilleuls.coop](https://les-tilleuls.coop).
