# 📚 Reading Record (RR)

A modern web application for tracking your reading journey, managing your book library, and monitoring reading progress with detailed logs and ratings.

## 🌟 Features

### 📖 Book Management
- **Add New Books**: Create entries with title, author, ISBN, page count, and purchase date
- **ISBN Search Integration**: Auto-populate book details using OpenLibrary API
- **Book Library**: View all books in a modern, responsive table with search and filtering
- **Book Details**: Detailed view of each book with all metadata
- **Edit & Delete**: Full CRUD operations for book management

### 📊 Reading Progress Tracking
- **Start Reading**: One-click to begin tracking a reading session
- **Reading Status**: Visual indicators showing book status (Unread, Reading, Finished)
- **Finish Reading**: Complete reading sessions with ratings and notes
- **Reading Logs**: Detailed history of all reading sessions

### 📈 Reading Analytics
- **Visual Status Indicators**: 
  - 📚 Unread books
  - 📖 Currently reading
  - ✅ Finished books
- **Star Ratings**: 5-star rating system with visual star display
- **Reading Notes**: Personal notes and thoughts for each reading session
- **Progress Timeline**: Chronological view of reading activity

### 🎨 Modern User Interface
- **Interactive Elements**: Smooth animations and hover effects
- **Dashboard Layout**: Sidebar navigation with content areas
- **Modern Cards**: Clean, card-based design throughout

### 🔒 Security Features
- **CSRF Protection**: All forms protected against cross-site request forgery
- **Input Validation**: Server-side validation for all user inputs
- **Secure Routing**: Properly configured route security

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
- **Custom CSS Framework**: Tailored styling system (rr-styles.css)
- **Font Icons**: Unicode icons for visual elements

### Development & Tools
- **Composer**: PHP dependency management
- **Symfony CLI**: Development server and tools
- **Asset Mapper**: Modern asset management for Symfony
- **PHPUnit**: Testing framework for unit and functional tests
- **Doctrine Migrations**: Database schema versioning
- **Docker**: Containerized development environment

### External APIs
- **OpenLibrary API**: Book metadata retrieval by ISBN
- **HTTP Client**: Symfony's HTTP client for API integration

## 📁 Project Structure

```
ReadingLog/
├── assets/                 # Frontend assets
│   ├── styles/
│   │   └── rr-styles.css  # Main stylesheet
│   └── controllers/       # Stimulus controllers
├── src/
│   ├── Controller/        # Application controllers
│   ├── Entity/           # Database entities (Book, ReadLog)
│   ├── Form/             # Symfony forms
│   ├── Repository/       # Database repositories
│   └── Services/         # Business logic services
├── templates/            # Twig templates
│   ├── book/            # Book management templates
│   ├── log/             # Reading log templates
│   ├── dashboard/       # Dashboard templates
│   └── welcome/         # Welcome page
├── config/              # Application configuration
├── migrations/          # Database migrations
└── public/              # Web accessible files
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
   This builds the `postgres` and `app` images and starts them. On the first start, the `postgres` container creates the database user and the database from the `POSTGRES_*` values, and stores the data in `./db/data`. The app waits until the database is healthy. Check that both containers are `Up` with `podman-compose ps`.


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
   podman-compose exec app php bin/console app:reader:create jdoe "John Doe"
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
1. **Access the Dashboard**: Navigate to `/dashboard` to enter the main application
2. **Add Your First Book**: Click "Add New Book" to create your first book entry
3. **Start Reading**: Use the "Start Reading" button to begin tracking
4. **Finish & Rate**: Complete your reading session with notes and ratings

### Navigation
- **Dashboard**: Overview and main navigation
- **Books Library**: Manage your book collection
- **Reading Log**: View all reading sessions and progress

### Features Walkthrough
- **Book Status**: Each book shows its current status (Unread/Reading/Finished)
- **Quick Actions**: Start/finish reading directly from the books table
- **Reading History**: Track all your reading sessions with dates and ratings
- **Notes & Ratings**: Add personal thoughts and rate books 1-5 stars

## 🔧 Development

### Database Migrations
```bash
# Create new migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate
```

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
podman-compose logs -f app
```
The app is served at http://localhost:8081.

## 🎯 API Endpoints

### Book Management
- `GET /books` - Books library (dashboard)
- `POST /book/start/{id}` - Start reading a book
- `POST /book/finish/{id}` - Finish reading a book
- `GET /book/{id}` - View book details
- `POST /book/new` - Create new book
- `PUT /book/{id}/edit` - Update book

### Reading Logs
- `GET /read/log` - Reading log index
- `POST /read/log/new` - Create reading log
- `GET /read/log/{id}` - View reading log
- `PUT /read/log/{id}/edit` - Update reading log
- `DELETE /read/log/{id}` - Delete reading log


## 🆘 Support

For support, please contact the development team or create an issue in the repository.

---

Built with ❤️ using Symfony and modern web technologies by [jknight](https://sengaigibon.github.io/en/)
