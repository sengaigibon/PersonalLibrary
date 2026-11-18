# 📚 Personal Library

A modern web application for managing your book library, tracking your reading journey and monitoring reading progress with detailed logs and ratings.

## 🌟 Features

### 👥 Readers & Libraries
- **Multiple Readers**: Choose who is reading on the welcome page; new readers are created with `make create-reader`
- **Separate Libraries**: Each reader has their own books and reading logs, and only sees their own (there is no login: anyone can pick any reader)

### 📖 Book Management
- **Add New Books**: Create entries with title, author, ISBN, page count, purchase date, and a "reference book" flag
- **Book Library**: Paginated table of your books, searchable by title, author, status (unread, reading, finished) or ID
- **Book Details**: Detailed view of each book with all its metadata
- **Edit & Delete**: Books can be edited, and deleted as long as they have no reading logs
- **Validation**: Title and author are required; ISBN length and page count are checked

### 📊 Reading Progress Tracking
- **Start / Finish Reading**: One click in the library starts a reading session today, or finishes the one in progress
- **Log Past Readings**: Record a reading you already did from the library, with exact dates or just the year, and a rating
- **Reading Status**: Visual indicators for each book: 📚 Unread, 📖 Reading, ✅ Finished
- **Re-reading**: A book can be read several times; each reading is its own log
- **Reading Log**: Paginated list of your reading sessions, searchable by book title, with ⭐ ratings (1-5) and personal notes

### 📈 Dashboard
- **Reading Now**: The books you are currently reading, with a link to open them in the library
- **This Year**: Books read, pages read, and average days per book
- **Library Totals**: Library size, books read, percentage read, and books waiting to be read
- **New Books**: Books bought this year and last year, with their reading status and how many of last year's you have read

### 🎨 User Interface
- **Dashboard Layout**: Sidebar navigation with content areas and card-based pages
- **Interactive Elements**: Hover effects and transitions, flash messages for every action

### 🔒 Security
- **CSRF Protection**: Book and reading log forms, and delete actions, are protected against cross-site request forgery

### 💾 Backup & Restore
- **JSON Backups**: `php bin/console app:db:backup` saves readers, books and reading logs to `~/backups`; `app:db:restore` restores the most recent backup

## 🛠️ Tech Stack

### Backend
- **PHP 8.4+**: Modern PHP with latest features
- **Symfony 7.x**: Robust PHP framework for web applications
- **Doctrine ORM**: Database abstraction and object-relational mapping
- **PostgreSQL**: Primary database for data persistence
- **Twig**: Template engine for clean, maintainable views

### Frontend
- **HTML5 & CSS3**: Modern web standards
- **JavaScript (ES6+)**: Client-side interactivity and AJAX
- **Custom CSS**: Hand-written stylesheets in `assets/styles/`
- **Font Icons**: Unicode icons for visual elements

### Development & Tools
- **Composer**: PHP dependency management
- **Symfony CLI**: Development server and tools
- **Asset Mapper**: Modern asset management for Symfony
- **PHPUnit**: Testing framework for unit and functional tests
- **Doctrine Migrations**: Database schema versioning
- **Docker**: Containerized development environment

### External APIs
- **OpenLibrary API**: Book title and publish date lookup by ISBN (backend only, not used by the UI yet)
- **HTTP Client**: Symfony's HTTP client for API integration

## 📁 Project Structure

```
PersonalLibrary/
├── assets/                # Frontend assets
│   ├── styles/            # Stylesheets (common, dashboard, flash messages)
│   ├── scripts/           # Flash messages, searchable selects
│   └── controllers/       # Stimulus controllers
├── docker/                # Container images and configuration
├── src/
│   ├── Command/           # Console commands (backup, restore, create reader)
│   ├── Controller/        # Application controllers
│   ├── DataFixtures/      # Test data
│   ├── Entity/            # Database entities (Person, Book, ReadLog)
│   ├── Form/              # Symfony forms
│   ├── Repository/        # Database repositories
│   └── Services/          # Current reader, OpenLibrary API client
├── templates/             # Twig templates
│   ├── book/              # Book management templates
│   ├── log/               # Reading log templates
│   ├── dashboard/         # Dashboard templates
│   └── welcome/           # Welcome page
├── tests/                 # Integration and functional tests
├── config/                # Application configuration
├── migrations/            # Database migrations
└── public/                # Web accessible files
```

## 🚀 Installation

### Prerequisites

**With containers (recommended):**
- [Podman](https://podman.io/) + `podman-compose`, or Docker + Docker Compose
- `make`

**Without containers (local setup):**
- PHP 8.4 or higher (with the `pdo_pgsql`, `intl`, `zip` and `mbstring` extensions)
- Composer
- PostgreSQL 16, installed and running
- Symfony CLI (optional, for `symfony server:start`)

### Setup Steps (containers)

1. **Clone the repository**
   ```bash
   git clone git@github.com:sengaigibon/PersonalLibrary.git
   cd PersonalLibrary
   ```

2. **Configure the environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` and set at least:
   - `APP_SECRET`: generate one with `openssl rand -hex 16`
   - `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`: the database credentials (avoid `'` in the password)

   You don't need to change `DATABASE_URL` for the containers; `compose.yaml` builds it from the `POSTGRES_*` values.  


3. **Build and start the containers**
   ```bash
   make up-build
   ```
   This builds the `postgres` and `app` images and starts them. On the first start, the `postgres` container creates the database and stores the data in `./db/data`. The app waits until the database is healthy. Check that both containers are `Up` with `podman-compose ps`.


4. **Create the database schema**
   ```bash
   make migrate
   ```
   The database itself already exists (step 3), so there is no `doctrine:database:create` step here.


5. **Create your first reader**
   ```bash
   make create-reader
   ```
   The command asks for a nickname and a full name. You can also pass them directly:
   ```bash
   podman-compose exec -u www-data app php bin/console app:reader:create jdoe "John Doe"
   ```
   The app can't be used without a reader: the welcome page only lets you choose an existing reader. Run the command again to add more readers.

6. **Access the application**
   - Open http://localhost:8081
   - Choose your reader and click "Enter to the library"

### Setup Steps (without containers)

1. Clone the repository (as above) and install the dependencies:
   ```bash
   composer install
   ```
2. Configure the environment:
   ```bash
   cp .env.example .env
   ```
   Set `APP_SECRET`, and set `DATABASE_URL` to your local PostgreSQL server (user, password, host, port and database name).
3. Create the database and the schema (PostgreSQL must be running):
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
4. Create your first reader:
   ```bash
   php bin/console app:reader:create
   ```
5. Start the development server and open http://localhost:8000:
   ```bash
   symfony server:start
   ```

## 📱 Usage

### Getting Started
1. **Choose a Reader**: Open the application and pick your reader on the welcome page
2. **Add Your First Book**: In the Library, click "Add New Book"
3. **Start Reading**: Click "Start" next to the book in the Library
4. **Finish Reading**: Click "Finish" when you are done; add a rating and notes by editing the entry in the Reading Log

### Navigation
- **Dashboard**: Reading statistics for the current year and your library
- **Library**: Manage your books
- **Reading Log**: All your reading sessions
- **Exit**: Back to the welcome page to choose another reader

## 🔧 Development

### Database Migrations
```bash
# Create new migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate
```

### Testing

Integration tests use a separate database: the main database name with a `_test` suffix (e.g. `app_test`), configured in `config/packages/doctrine.yaml`. Your development data is never touched.

```bash
make test-db         # (re)create the test database, run migrations and load the fixtures
make test-fixtures   # reload the fixtures only (purges the test database first)
make test               # run all tests
make test-integration   # repository tests (tests/Integration)
make test-functional    # HTTP/controller tests (tests/Functional)
```

Run `make test-db` once after setting up the project, and again after adding a migration.

The fixtures live in `src/DataFixtures/` and use fixed values so tests can assert exact results:
- **Readers**: `alice` (owns most books), `bob` (a small library, used to check that libraries are separate) and `carol` (empty library)
- **Books**: 10 books, each owned by one reader, covering every status (unread, reading, finished, finished twice), one reference book, one without a page count, and books bought in 2024 and 2025
- **Read logs**: 8 logs between 2024 and 2025, including a book started and finished on the same day

The dashboard depends on the current year, so dashboard tests should fix the date to 2025 with `ClockSensitiveTrait::mockTime()` (see `ReadLogFixtures.php` for the expected 2025 statistics).

The status of each book per reader is documented in `BookFixtures.php`. Each fixture has a reference constant (e.g. `BookFixtures::DUNE`) that you can use to identify it in tests.

Integration tests live in `tests/Integration/` and extend `DatabaseTestCase`. It reloads the fixtures before each test class, provides `$this->id(BookFixtures::DUNE)` to get a fixture's database id, and `inRolledBackTransaction()` for tests that add data. It refuses to run against any database that isn't a `_test` database.

Functional tests live in `tests/Functional/` and extend `ApplicationTestCase`. They send real HTTP requests through routing, controllers, forms and Twig. Because they change data, the fixtures are reloaded before every test. `chooseReader()` picks the current reader the same way the welcome page does. External APIs are replaced with `MockHttpClient`, so tests never call OpenLibrary.

### Clearing Cache
```bash
php bin/console cache:clear
```

### Rebuilding the Containers After Changes

The `make` targets run `podman-compose` under the hood. With Docker, override it per command, e.g. `make up-build COMPOSE="docker compose"`, or change `COMPOSE` at the top of the `Makefile`.

The project directory is bind-mounted into the `app` container (`.:/var/www/html`), so **most code changes don't need a rebuild**. What you need depends on what you changed:

| What changed | What to do |
|---|---|
| PHP code, Twig templates, CSS/JS, config in `config/` | Nothing, just reload the page (if it doesn't show up, run `make cache-clear`) |
| `composer.json` / `composer.lock` | `make composer-install` (see below) |
| New migration in `migrations/` | `make migrate` |
| `docker/app/Dockerfile`, `docker/nginx.conf`, `docker/default.conf`, `docker/supervisord.conf`, `docker/entrypoint.sh` | Rebuild the image |
| `docker/postgres/*` | Rebuild the image |
| `compose.yaml` / `compose.override.yaml` | Recreate the containers: `make down && make up` |

**Rebuild the images and restart the containers:**
```bash
make down
make up-build          # = podman-compose up -d --build
```

To rebuild a single service, or force a clean build without cached layers:
```bash
make build-app              # only the app image
make build-app-no-cache     # ignore cached layers
make up-app                 # start/recreate only the app container
```

**Composer dependencies:** `vendor/` is kept in a named volume (`vendor`), which is **not** refreshed when the image is rebuilt. After changing dependencies, install them in the running container:
```bash
make composer-install
```
Or remove the volume, so the next start fills it from the freshly built image:
```bash
make down-volumes   # removes named volumes (vendor); the database in ./db/data is a bind mount and is kept
make up-build
```

**Database migrations:**
```bash
make migrate
```

**Check that everything is running:**
```bash
podman-compose ps
podman-compose logs -f app   # nginx / php-fpm output
make logs                    # Symfony's dev.log
```

**Cache, logs and file ownership:** inside the container, Symfony writes its cache and logs to `/tmp/symfony` (`APP_CACHE_DIR` and `APP_LOG_DIR` in `compose.yaml`), not to the project's `./var`. The host and the container each have their own cache, so `php bin/console` works on the host too and `./var` stays owned by you.

When you run PHP commands in the container yourself, run them as `www-data` (the same user as php-fpm): `podman-compose exec -u www-data app php bin/console ...`. The `make` targets already do this. `make shell` opens a shell as `www-data`; use `make shell-root` only for system tasks.
The app is served at http://localhost:8081.

## 🎯 Routes

All routes except the welcome page require a reader to be chosen. Books and reading logs of another reader return 404.

### Readers
- `GET /` - Welcome page (choose a reader)
- `POST /setReader` - Set the current reader (JSON: `{"readerId": 1}`)
- `GET /dashboard` - Dashboard

### Books
- `GET /books` - Library (query: `title`, `author`, `status`, `id`, `page`, `limit`)
- `GET|POST /books/new` - Create a book
- `GET /books/{id}` - Book details
- `GET|POST /books/{id}/edit` - Edit a book
- `POST /books/{id}` - Delete a book
- `GET /books/start/{id}` - Start reading a book (JSON)
- `GET /books/finish/{id}` - Finish reading a book (JSON)
- `GET /books/query/{isbn}` - Look up a book's title and publish date on OpenLibrary (JSON, not used by the UI yet)

### Reading Logs
- `GET /read/log` - Reading log (query: `title`, `page`, `limit`)
- `GET|POST /read/log/new` - Create a reading log
- `POST /read/log/quicknew/{id}` - Log a past reading of a book (JSON)
- `GET /read/log/{id}` - Reading log details
- `GET|POST /read/log/{id}/edit` - Edit a reading log
- `POST /read/log/{id}` - Delete a reading log

## 🆘 Support

For support, please contact the development team or create an issue in the repository.

---

Built with ❤️ using Symfony and modern web technologies by [jknight](https://sengaigibon.github.io/en/)
