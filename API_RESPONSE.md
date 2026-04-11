# API Response Structure

This document explains the standardized JSON API response structure used across the Revive backend.

## Response Format

### Success Response (200, 201)
```json
{
  "success": true,
  "message": "Human readable message",
  "data": {
    "id": 1,
    "name": "Example",
    ...
  }
}
```

### Success Response with Pagination (200)
```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "id": 1, "name": "Item 1" },
    { "id": 2, "name": "Item 2" }
  ],
  "meta": {
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "last_page": 7,
      "from": 1,
      "to": 15
    }
  }
}
```

### Error Response (4xx, 5xx)
```json
{
  "success": false,
  "message": "Error description"
}
```

### Validation Error Response (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

## Using ApiController

All controllers should extend `ApiController` instead of the base `Controller`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\User;

class UserController extends ApiController
{
    // Your controller code here
}
```

## Response Methods

### 1. Success Response
```php
return $this->success($data, 'User retrieved successfully', 200);
// or shorthand
return $this->success($data);
```

### 2. Created Response (201)
```php
$user = User::create($validated);
return $this->created($user, 'User created successfully');
```

### 3. Paginated Response
```php
$users = User::paginate(15);
return $this->paginated($users, 'Users retrieved successfully');
```

### 4. No Content Response (204)
```php
$user->delete();
return $this->noContent('User deleted successfully');
```

### 5. Validation Error (422)
```php
$errors = [
    'email' => ['Email is required'],
    'password' => ['Password must be at least 8 characters'],
];
return $this->validationError($errors, 'Validation failed');
```

### 6. Not Found Error (404)
```php
$user = User::find($id);
if (!$user) {
    return $this->notFound('User not found');
}
```

### 7. Unauthorized Error (401)
```php
if (!auth()->check()) {
    return $this->unauthorized('You must be logged in');
}
```

### 8. Forbidden Error (403)
```php
if (!$user->can('update', $post)) {
    return $this->forbidden('You cannot update this post');
}
```

### 9. Conflict Error (409)
```php
if (User::where('email', $email)->exists()) {
    return $this->conflict('Email already in use');
}
```

### 10. Server Error (500)
```php
return $this->serverError('Something went wrong');
```

### 11. Custom Error
```php
return $this->error('Custom error message', 400, [
    'detail' => ['Additional error details'],
]);
```

## Complete Example: User Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends ApiController
{
    /**
     * List all users with pagination
     */
    public function index()
    {
        $users = User::paginate(15);
        return $this->paginated($users, 'Users retrieved successfully');
    }

    /**
     * Show a specific user
     */
    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }

        return $this->success(new UserResource($user), 'User retrieved successfully');
    }

    /**
     * Create a new user
     */
    public function store(StoreUserRequest $request)
    {
        // Validation happens automatically via FormRequest
        
        $user = User::create($request->validated());
        
        return $this->created(new UserResource($user), 'User created successfully');
    }

    /**
     * Update a user
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }

        $user->update($request->validated());
        
        return $this->success(new UserResource($user), 'User updated successfully');
    }

    /**
     * Delete a user
     */
    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }

        $user->delete();
        
        return $this->noContent('User deleted successfully');
    }
}
```

## Exception Handling

The `App\Exceptions\Handler` automatically converts all exceptions to JSON responses for API requests:

- **ValidationException** → 422 with error details
- **NotFoundHttpException** → 404
- **AuthenticationException** → 401
- **AuthorizationException** → 403
- **HttpException** → Status code from exception
- **Other Exceptions** → 500 (with message in debug mode)

No HTML error pages will be returned for API requests.

## Status Codes Reference

| Code | Meaning | Use Case |
|------|---------|----------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request |
| 401 | Unauthorized | Not authenticated |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Resource conflict (duplicate, etc.) |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Server Error | Internal server error |

## Best Practices

1. **Always extend ApiController** in your API controllers
2. **Use FormRequests** for validation - they automatically format validation errors
3. **Use Resources** for data transformation
4. **Return consistent response types** using the ApiResponse methods
5. **Include meaningful messages** for users
6. **Use appropriate status codes** for each scenario
7. **Don't catch all exceptions** - let the Handler deal with them for JSON responses

## FormRequest Integration

Form requests automatically handle validation and format errors:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
```

When validation fails, the `Handler` automatically returns a 422 response with formatted errors.
