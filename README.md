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
- **PHP 8.3+**: Modern PHP with latest features
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
- PHP 8.3 or higher
- Composer
- PostgreSQL
- Node.js (for asset building)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ReadingLog
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env .env.local
   # Edit .env.local with your database configuration
   ```

4. **Setup database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Start development server**
   ```bash
   symfony server:start
   ```

6. **Access the application**
   - Open http://localhost:8000
   - Navigate to `/dashboard` for the main application

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
