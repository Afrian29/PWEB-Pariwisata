<<<<<<< HEAD
# Regional Tourism Information System

A simple, dynamic PHP web application for managing and displaying tourism information in a region.

## Features

- **Tourism Place Management**: Add, edit, and delete tourism destinations
- **Search and Filter**: Search by name, location, or description
- **Category Filtering**: Filter places by category (beach, mountain, temple, museum, etc.)
- **Rating System**: Display ratings for each tourism place
- **Image Upload**: Upload and display images for tourism places
- **Responsive Design**: Works on desktop and mobile devices
- **Pagination**: Handle large numbers of tourism places efficiently
- **Admin Panel**: Secure admin interface for content management

## File Structure

```
tourism-system/
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet
│   └── images/                  # Uploaded images
├── admin/
│   └── manage_places.php        # Admin management interface
├── config/
│   └── database.php             # Database configuration
├── includes/
│   └── functions.php            # Helper functions
├── index.php                   # Main page
├── database_schema.sql         # SQL database schema
└── README.md                   # This file
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB database
- Web server (Apache, Nginx, or PHP built-in server)

### Setup Instructions

1. **Database Setup**
   - Import the `database_schema.sql` file into your MySQL database
   - Update database credentials in `config/database.php` if needed

2. **File Permissions**
   - Ensure the `assets/images/` directory is writable by the web server
   - Set appropriate permissions for file uploads

3. **Web Server Configuration**
   - Place the files in your web server's document root
   - Configure your server to point to the `tourism-system` directory

4. **Access the Application**
   - Visit `http://localhost/tourism-system/` in your browser
   - Admin panel: `http://localhost/tourism-system/admin/manage_places.php`

### Default Admin Login
- Username: `admin`
- Password: `admin123`

## Usage

### For Visitors
- Browse tourism places on the main page
- Search for specific destinations
- Filter by category
- View place details, ratings, and images

### For Administrators
- Add new tourism places with images
- Edit existing place information
- Delete unwanted entries
- Manage all tourism content from the admin panel

## Database Schema

The system uses one main table `tourism_places` with the following fields:
- `id` - Primary key
- `name` - Place name
- `description` - Detailed description
- `location` - Geographic location
- `category` - Category type (beach, mountain, temple, etc.)
- `rating` - Rating from 1.0 to 5.0
- `image` - Image filename
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Customization

### Adding New Categories
To add new tourism categories:
1. Update the category dropdown in `admin/manage_places.php`
2. Add appropriate styling if needed

### Modifying the Design
- Edit `assets/css/style.css` for visual changes
- Modify HTML templates in PHP files for layout changes

### Database Configuration
Update `config/database.php` with your database credentials:
```php
private $host = 'localhost';
private $username = 'your_username';
private $password = 'your_password';
private $database = 'tourism_db';
```

## Security Features

- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- File upload validation
- Form validation and error handling

## Browser Support

The application is compatible with:
- Chrome/Chromium
- Firefox
- Safari
- Microsoft Edge
- Opera

## License

This project is open source and available under the MIT License.

## Support

For issues and questions:
1. Check the file structure and permissions
2. Verify database configuration
3. Ensure PHP and MySQL extensions are installed
4. Review error logs for specific error messages

## Future Enhancements

Potential features to add:
- User authentication and reviews
- Advanced search filters
- Interactive maps integration
- Multi-language support
- Mobile app companion
- Booking system integration
- Social media sharing
- Email notifications
=======
# PWEB-Pariwisata
Repo ini dibuat untuk memenuhi tugas akhir dari mata kuliah Pemrograman Web dengan tema Pariwisata Lokal
>>>>>>> 3839da11786a096ac6bd08ddf1c094c1450371d8
