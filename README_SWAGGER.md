# Swagger UI Setup for OMPAY API

This document explains how to use Swagger UI for documenting and testing the OMPAY API.

## Overview

Swagger UI has been integrated into the OMPAY Laravel project using the `darkaonline/l5-swagger` package. It loads the existing `openapi.yaml` specification file located in `storage/api-docs/openapi.yaml`.

## Accessing the Documentation

### Local Development
1. Start the Laravel development server:
   ```bash
   php artisan serve
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:8000/api/documentation
   ```

### Production
In production, access the documentation at:
```
https://yourdomain.com/api/documentation
```

## Features

- **Interactive API Testing**: Use the "Try it out" button on any endpoint to test it directly from the documentation.
- **Authentication**: Supports Bearer token authentication (JWT) for protected endpoints.
- **Complete API Coverage**: Documents all OMPAY endpoints including registration, login, accounts, and transactions.

## Authentication in Swagger UI

To test protected endpoints:

1. Click the "Authorize" button at the top of the Swagger UI page.
2. Enter your JWT token in the format: `Bearer <your-jwt-token>`
3. Click "Authorize" to apply the token to all subsequent requests.

## Updating the Documentation

### After Modifying the OpenAPI Specification

1. Edit the `storage/api-docs/openapi.yaml` file with your changes.
2. The documentation will automatically reflect the changes (no regeneration needed since `generate_always` is set to `false`).

### If You Need to Regenerate from Code Annotations

If you prefer to generate documentation from PHP annotations instead of using the static YAML file:

1. Uncomment the annotations path in `config/l5-swagger.php`:
   ```php
   'annotations' => [
       base_path('app'),
   ],
   ```

2. Set `generate_always` to `true` in the config.

3. Run the generation command:
   ```bash
   php artisan l5-swagger:generate
   ```

## Configuration

The Swagger configuration is located in `config/l5-swagger.php`. Key settings:

- **Documentation File**: `storage/api-docs/openapi.yaml`
- **Format**: YAML
- **Title**: OMPAY API Documentation
- **Security**: Bearer token authentication enabled

## Troubleshooting

### Documentation Not Loading
- Ensure the server is running: `php artisan serve`
- Check that `storage/api-docs/openapi.yaml` exists and is valid YAML
- Verify the route is accessible: `php artisan route:list | grep documentation`

### Authentication Issues
- Ensure your JWT token is valid and properly formatted
- Check that the token hasn't expired
- Verify the endpoint requires authentication

### CORS Issues
If testing from a different domain, ensure CORS is properly configured in `config/cors.php`.

## Package Information

- **Package**: darkaonline/l5-swagger
- **Version**: 8.6.5
- **Swagger UI Version**: 5.30.2

For more information, refer to the [L5-Swagger documentation](https://github.com/DarkaOnLine/L5-Swagger).