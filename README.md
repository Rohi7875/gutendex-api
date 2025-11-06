# Gutendex API

A RESTful API for retrieving books from Project Gutenberg with advanced filtering capabilities.

## Features

- **Multiple Filter Options**: Filter by Gutenberg ID, language, MIME type, topic, author, or title
- **Case-Insensitive Partial Matching**: Topic, author, and title filters support partial matching
- **Multiple Values**: Each filter accepts comma-separated multiple values
- **Pagination**: 25 books per page
- **Sorted Results**: Books sorted by download count (most popular first)
- **Comprehensive Data**: Returns title, authors, genre, languages, subjects, bookshelves, and download links

## API Documentation

The API is documented using OpenAPI 3.0 (Swagger). View the documentation:

- **Swagger YAML**: `swagger.yaml`
- **Interactive Documentation**: Use [Swagger UI](https://swagger.io/tools/swagger-ui/) or [Swagger Editor](https://editor.swagger.io/) to view the documentation

## Installation

### Prerequisites

- PHP 8.2 or higher
- PostgreSQL 9.5 or higher
- Composer
- Node.js and NPM (for frontend assets, if needed)

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd gutendex-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update `.env` file with your database credentials**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Import database schema and data**
   - Import your PostgreSQL database dump to populate the books data

7. **Start the development server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000/api/v1/books`

## API Endpoints

### GET /api/v1/books

Retrieve books with optional filters.

#### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `gutenberg_id` | string | Project Gutenberg ID(s), comma-separated | `1,2,3` |
| `language` | string | Language code(s), comma-separated | `en,fr` |
| `mime_type` | string | MIME type(s), comma-separated | `text/html,text/plain` |
| `topic` | string | Topic(s) matching subject/bookshelf, comma-separated | `child,infant` |
| `author` | string | Author name(s), partial match, comma-separated | `shakespeare` |
| `title` | string | Title(s), partial match, comma-separated | `adventure` |
| `page` | integer | Page number (default: 1) | `1` |

#### Response Format

```json
{
  "total_count": 54859,
  "books": [
    {
      "title": "The Adventures of Tom Sawyer",
      "authors": [
        {
          "name": "Mark Twain",
          "birth_year": 1835,
          "death_year": 1910
        }
      ],
      "genre": "Children's literature",
      "languages": ["en"],
      "subjects": ["Adventure stories", "Boys"],
      "bookshelves": ["Children's literature"],
      "links": [
        {
          "mime_type": "text/html",
          "url": "https://www.gutenberg.org/files/74/74-h/74-h.htm"
        }
      ]
    }
  ]
}
```

## Examples

### Basic Request
```bash
GET /api/v1/books
```

### Filter by Language
```bash
GET /api/v1/books?language=en
```

### Multiple Filters
```bash
GET /api/v1/books?language=en,fr&topic=child&author=shakespeare
```

### Pagination
```bash
GET /api/v1/books?page=2
```

## Deployment

See `DEPLOYMENT.md` for detailed instructions on deploying to Ubuntu AWS EC2.

Quick summary:
1. Launch Ubuntu 22.04 LTS EC2 instance
2. Install PHP 8.2, PostgreSQL, and Nginx
3. Clone repository and install dependencies
4. Configure environment variables
5. Set up Nginx and SSL
6. Import database

For complete step-by-step instructions, refer to `DEPLOYMENT.md`.

## Testing

Run the test suite:
```bash
php artisan test
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues and questions, please open an issue in the repository.
