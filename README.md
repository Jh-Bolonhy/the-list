# Element App - Laravel 12 + Vue.js

A simple Element list application built with Laravel 12 backend API and Vue.js frontend with hierarchical structure support and archiving functionality.

## Features

- ✅ Create, Read, Update, Archive (CRUD) operations for elements
- ✅ Hierarchical structure - elements can have parent-child relationships
- ✅ Mark elements as completed/incomplete
- ✅ Edit element title and description
- ✅ Archive elements with all descendants (virtual deletion)
- ✅ Restore archived elements with all descendants
- ✅ Modern, responsive UI with Tailwind CSS
- ✅ Real-time updates without page refresh
- ✅ RESTful API backend
- ✅ Multi-language support (English, Russian)

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

- `GET /api/elements` - Get all non-archived elements
- `GET /api/elements?archived=true` - Get all archived elements
- `POST /api/elements` - Create a new element
- `GET /api/elements/{id}` - Get a specific element
- `PUT /api/elements/{id}` - Update an element
- `POST /api/elements/{id}/archive` - Archive an element and all its descendants
- `POST /api/elements/{id}/restore` - Restore an archived element and all its descendants
- `DELETE /api/elements/{id}/force` - Permanently delete an element (only if archived)

### Frontend Development

For development with hot reloading:

```bash
npm run dev
```

Or use the convenient composer script that runs everything:

```bash
composer run dev
```

This will start:
- Laravel server (http://localhost:8000)
- Vite dev server (hot reloading)
- Queue worker
- Log viewer (Pail)

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
│       └── Element.php               # Element model with hierarchical support
├── database/
│   └── migrations/
│       └── create_elements_table.php # Database migration
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   └── ElementApp.vue        # Main Vue component
│   │   ├── lang/
│   │   │   ├── en.js                 # English translations
│   │   │   └── ru.js                 # Russian translations
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

### Create a Child Element
```bash
curl -X POST http://localhost:8000/api/elements \
  -H "Content-Type: application/json" \
  -d '{"title": "Task 1", "parent_element_id": 1}'
```

### Get All Elements
```bash
curl http://localhost:8000/api/elements
```

### Get Archived Elements
```bash
curl http://localhost:8000/api/elements?archived=true
```

### Update an Element
```bash
curl -X PUT http://localhost:8000/api/elements/1 \
  -H "Content-Type: application/json" \
  -d '{"completed": true}'
```

### Archive an Element (and all descendants)
```bash
curl -X POST http://localhost:8000/api/elements/1/archive
```

### Restore an Archived Element (and all descendants)
```bash
curl -X POST http://localhost:8000/api/elements/1/restore
```

### Permanently Delete an Element
```bash
curl -X DELETE http://localhost:8000/api/elements/1/force
```

## Database Schema

The `elements` table contains the following fields:

- `id` - Primary key (auto-increment)
- `parent_element_id` - Foreign key to parent element (nullable, for hierarchical structure)
- `title` - Element title (required)
- `description` - Element description (optional)
- `completed` - Completion status (boolean, default: false)
- `archived` - Archive status (boolean, default: false)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Foreign Key Constraint

- `parent_element_id` references `elements.id` with `onDelete('restrict')`
- This prevents physical deletion of parent elements that have children
- Structure is preserved when archiving (virtual deletion)

## Key Features Explained

### Hierarchical Structure

Elements can have parent-child relationships, allowing you to create nested structures:
- Parent elements can have multiple children
- Children maintain reference to their parent via `parent_element_id`
- Structure is preserved when archiving

### Archiving System

Instead of deleting elements, the application uses an archiving system:
- Elements are marked as `archived = true` (virtual deletion)
- When archiving a parent, all descendants are automatically archived
- Archived elements can be restored with all their descendants
- Structure and relationships are preserved in the archive
- Only archived elements can be permanently deleted

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
