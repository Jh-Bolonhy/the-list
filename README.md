# Element App - Laravel 12 + Vue.js

A simple Element list application built with Laravel 12 backend API and Vue.js frontend.

## Features

- ✅ Create, Read, Update, Delete (CRUD) operations for elements
- ✅ Mark elements as completed/incomplete
- ✅ Edit element title and description
- ✅ Modern, responsive UI with Tailwind CSS
- ✅ Real-time updates without page refresh
- ✅ RESTful API backend

## Tech Stack

- **Backend**: Laravel 12 (PHP)
- **Frontend**: Vue.js 3
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **Database**: SQLite (default)

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm

## Installation

1. **Clone or navigate to the project directory**
   ```bash
   cd the-list
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run database migrations**
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

## Running the Application

1. **Start the Laravel development server**
   ```bash
   php artisan serve
   ```

2. **Open your browser and navigate to**
   ```
   http://localhost:8000
   ```

## Development

### Backend API Endpoints

The application provides the following RESTful API endpoints:

- `GET /api/elements` - Get all elements
- `POST /api/elements` - Create a new element
- `GET /api/elements/{id}` - Get a specific element
- `PUT /api/elements/{id}` - Update an element
- `DELETE /api/elements/{id}` - Delete an element

### Frontend Development

For development with hot reloading:

```bash
npm run dev
```

### Building for Production

```bash
npm run build
```

## Project Structure

```
the-list/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── ElementController.php    # API controller
│   └── Models/
│       └── Element.php               # Element model
├── database/
│   └── migrations/
│       └── create_todos_table.php    # Database migration
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   └── ElementApp.vue        # Main Vue component
│   │   ├── app.js                    # Vue app entry point
│   │   └── bootstrap.js              # Axios configuration
│   └── views/
│       └── app.blade.php             # Main HTML template
├── routes/
│   └── web.php                       # API routes
└── vite.config.js                    # Vite configuration
```

## API Usage Examples

### Create an Element
```bash
curl -X POST http://localhost:8000/api/elements \
  -H "Content-Type: application/json" \
  -d '{"title": "Buy groceries", "description": "Milk, bread, eggs"}'
```

### Get All Elements
```bash
curl http://localhost:8000/api/elements
```

### Update an Element
```bash
curl -X PUT http://localhost:8000/api/elements/1 \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'
```

### Delete an Element
```bash
curl -X DELETE http://localhost:8000/api/elements/1
```

## Database Schema

The `elements` table contains the following fields:

- `id` - Primary key (auto-increment)
- `title` - Element title (required)
- `description` - Element description (optional)
- `completed` - Completion status (boolean, default: false)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
