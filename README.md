# Todo App - Laravel 12 + Vue.js

A simple Todo list application built with Laravel 12 backend API and Vue.js frontend.

## Features

- ✅ Create, Read, Update, Delete (CRUD) operations for todos
- ✅ Mark todos as completed/incomplete
- ✅ Edit todo title and description
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
   cd todo-app
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

- `GET /api/todos` - Get all todos
- `POST /api/todos` - Create a new todo
- `GET /api/todos/{id}` - Get a specific todo
- `PUT /api/todos/{id}` - Update a todo
- `DELETE /api/todos/{id}` - Delete a todo

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
todo-app/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── TodoController.php    # API controller
│   └── Models/
│       └── Todo.php              # Todo model
├── database/
│   └── migrations/
│       └── create_todos_table.php # Database migration
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   └── TodoApp.vue       # Main Vue component
│   │   ├── app.js                # Vue app entry point
│   │   └── bootstrap.js          # Axios configuration
│   └── views/
│       └── app.blade.php         # Main HTML template
├── routes/
│   └── web.php                   # API routes
└── vite.config.js                # Vite configuration
```

## API Usage Examples

### Create a Todo
```bash
curl -X POST http://localhost:8000/api/todos \
  -H "Content-Type: application/json" \
  -d '{"title": "Buy groceries", "description": "Milk, bread, eggs"}'
```

### Get All Todos
```bash
curl http://localhost:8000/api/todos
```

### Update a Todo
```bash
curl -X PUT http://localhost:8000/api/todos/1 \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'
```

### Delete a Todo
```bash
curl -X DELETE http://localhost:8000/api/todos/1
```

## Database Schema

The `todos` table contains the following fields:

- `id` - Primary key (auto-increment)
- `title` - Todo title (required)
- `description` - Todo description (optional)
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
