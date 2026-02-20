# Shuttle Vehicle Manager - REST API Documentation

## Base URL
```
https://siteurl.com/wp-json/lankashuttle/v1
```

---

## Authentication

### Public Endpoints
The following endpoints are public and don't require authentication:
- `GET /vehicles`
- `GET /vehicles/{id}`
- `GET /auth/status`

### Protected Endpoints
The following endpoints require user to be logged in:
- `GET /my-vehicles`
- `POST /vehicles`
- `PUT /vehicles/{id}`
- `DELETE /vehicles/{id}`
- `POST /vehicles/{id}/availability`
- `GET /user/profile`
- `PUT /user/profile`

Authentication is done via WordPress cookies or JWT tokens (if implementing token-based auth in future).

---

## Endpoints

### 1. Get All Vehicles (Public)

**Request:**
```http
GET /vehicles?page=1&per_page=20&status=verified
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |
| status | string | No | Filter by status: `pending` or `verified` |

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Toyota Coaster - WP-2023-AB1234",
      "type": "Toyota Coaster",
      "model": "High Roof",
      "license_plate": "WP-2023-AB1234",
      "seating_capacity": 29,
      "year_manufacture": 2023,
      "year_registration": 2023,
      "owner_id": 456,
      "status": "verified",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:45:00Z"
    }
  ],
  "pagination": {
    "total": 150,
    "pages": 8,
    "current_page": 1,
    "per_page": 20
  }
}
```

---

### 2. Get Single Vehicle Details

**Request:**
```http
GET /vehicles/123
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Toyota Coaster - WP-2023-AB1234",
    "type": "Toyota Coaster",
    "model": "High Roof",
    "license_plate": "WP-2023-AB1234",
    "seating_capacity": 29,
    "year_manufacture": 2023,
    "year_registration": 2023,
    "owner_id": 456,
    "status": "verified",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-20T14:45:00Z",
    "features": [
      "high_back_seats",
      "full_ac",
      "wifi_free",
      "usb_charging"
    ],
    "images": [
      "https://siteurl.com/wp-content/uploads/2024/01/vehicle-1.jpg",
      "https://siteurl.com/wp-content/uploads/2024/01/vehicle-2.jpg"
    ],
    "rc_document": "https://siteurl.com/wp-content/uploads/2024/01/rc.pdf",
    "insurance_document": "https://siteurl.com/wp-content/uploads/2024/01/insurance.pdf",
    "emission_document": "https://siteurl.com/wp-content/uploads/2024/01/emission.pdf",
    "revenue_license_document": null,
    "fitness_document": null,
    "availability_data": "[{\"dates\": [\"2024-02-10\", \"2024-02-11\"], \"note\": \"Wedding event\"}]"
  }
}
```

**Error Responses:**

404 Not Found:
```json
{
  "code": "vehicle_not_found",
  "message": "Vehicle not found",
  "data": {
    "status": 404
  }
}
```

---

### 3. Get Current User's Vehicles

**Request:**
```http
GET /my-vehicles
```

**Required Header:**
```
Cookie: wordpress session cookies (or Bearer token)
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Toyota Coaster - WP-2023-AB1234",
      "type": "Toyota Coaster",
      "model": "High Roof",
      "license_plate": "WP-2023-AB1234",
      "seating_capacity": 29,
      "year_manufacture": 2023,
      "year_registration": 2023,
      "owner_id": 456,
      "status": "verified",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-20T14:45:00Z"
    },
    {
      "id": 124,
      "title": "Wagon R - WP-2024-CD5678",
      "type": "Wagon R",
      "model": "VX",
      "license_plate": "WP-2024-CD5678",
      "seating_capacity": 5,
      "year_manufacture": 2024,
      "year_registration": 2024,
      "owner_id": 456,
      "status": "pending",
      "created_at": "2024-02-01T08:15:00Z",
      "updated_at": "2024-02-01T08:15:00Z"
    }
  ],
  "count": 2
}
```

**Error Responses:**

401 Unauthorized (not logged in):
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  "data": {
    "status": 401
  }
}
```

---

### 4. Create New Vehicle

**Request:**
```http
POST /vehicles
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_type": "Toyota Coaster",
  "vehicle_model": "High Roof",
  "year_manufacture": 2023,
  "year_registration": 2023,
  "license_plate": "WP-2023-AB1234",
  "seating_capacity": 29,
  "vehicle_features": [
    "high_back_seats",
    "full_ac",
    "wifi_free",
    "usb_charging"
  ],
  "vehicle_images": [
    "https://url-to-image-1.jpg",
    "https://url-to-image-2.jpg"
  ],
  "rc_document": "https://url-to-rc.pdf",
  "insurance_document": "https://url-to-insurance.pdf"
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| vehicle_type | string | Yes | Type of vehicle (e.g., "Toyota Coaster", "Wagon R") |
| vehicle_model | string | Yes | Model name (e.g., "High Roof", "VX") |
| year_manufacture | integer | Yes | Year of manufacture (1900-2099) |
| year_registration | integer | Yes | Year of registration (1900-2099) |
| license_plate | string | Yes | License plate number |
| seating_capacity | integer | Yes | Number of seats (1-99) |
| vehicle_features | array | No | Array of feature keys (see features list below) |
| vehicle_images | array | No | Array of image URLs or base64-encoded images |
| rc_document | string | No | RC document URL or base64-encoded file |
| insurance_document | string | No | Insurance document URL or base64 |
| emission_document | string | No | Emission document URL or base64 |
| revenue_license_document | string | No | Revenue license URL or base64 |
| fitness_document | string | No | Fitness document URL or base64 |

**Available Vehicle Features:**
```
high_back_seats, adjustable_armrests, extra_legroom, individual_seatbelts,
full_ac, tinted_windows, coolbox, overhead_racks, boot_luggage,
overhead_racks_space, underfloor_luggage, lcd_screens, audio_system,
wifi_free, microphone_pa, usb_charging, abs_ebs, airbags,
fire_extinguisher, gps_tracking, emergency_exits, led_mood_lights,
reading_lamps, air_suspension, onboard_restroom, panoramic_windows
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Vehicle created successfully and is pending verification",
  "data": {
    "id": 125,
    "status": "pending"
  }
}
```

**Error Responses:**

400 Bad Request (missing required field):
```json
{
  "code": "missing_field",
  "message": "vehicle_type is required",
  "data": {
    "status": 400
  }
}
```

401 Unauthorized:
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  "data": {
    "status": 401
  }
}
```

---

### 5. Update Vehicle

**Request:**
```http
PUT /vehicles/123
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_type": "Toyota Coaster",
  "vehicle_model": "Super Luxury",
  "year_manufacture": 2023,
  "year_registration": 2023,
  "license_plate": "WP-2023-AB1234",
  "seating_capacity": 35,
  "vehicle_features": [
    "high_back_seats",
    "full_ac",
    "wifi_free"
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Vehicle updated successfully and is now pending verification",
  "data": {
    "id": 123,
    "title": "Toyota Coaster - WP-2023-AB1234",
    "type": "Toyota Coaster",
    "model": "Super Luxury",
    "license_plate": "WP-2023-AB1234",
    "seating_capacity": 35,
    "year_manufacture": 2023,
    "year_registration": 2023,
    "owner_id": 456,
    "status": "pending",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-02-20T15:30:00Z"
  }
}
```

**Error Responses:**

404 Not Found:
```json
{
  "code": "vehicle_not_found",
  "message": "Vehicle not found",
  "data": {
    "status": 404
  }
}
```

403 Forbidden (not owner):
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 403
  }
}
```

---

### 6. Delete Vehicle

**Request:**
```http
DELETE /vehicles/123
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Vehicle deleted successfully"
}
```

**Error Responses:**

404 Not Found:
```json
{
  "code": "vehicle_not_found",
  "message": "Vehicle not found",
  "data": {
    "status": 404
  }
}
```

403 Forbidden:
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 403
  }
}
```

---

### 7. Update Vehicle Availability

**Request:**
```http
POST /vehicles/123/availability
Content-Type: application/json
```

**Request Body:**
```json
{
  "availability_data": [
    {
      "dates": ["2024-02-10", "2024-02-11"],
      "note": "Wedding event - not available"
    },
    {
      "dates": ["2024-02-25", "2024-02-26", "2024-02-27"],
      "note": "Maintenance scheduled"
    }
  ]
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| availability_data | array | Yes | Array of reservation objects |
| availability_data[].dates | array | Yes | Array of reserved dates in YYYY-MM-DD format |
| availability_data[].note | string | No | Optional note about the reservation |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Availability updated successfully"
}
```

**Error Responses:**

400 Bad Request:
```json
{
  "code": "missing_data",
  "message": "availability_data is required",
  "data": {
    "status": 400
  }
}
```

404 Not Found:
```json
{
  "code": "vehicle_not_found",
  "message": "Vehicle not found",
  "data": {
    "status": 404
  }
}
```

---

### 8. Get User Profile

**Request:**
```http
GET /user/profile
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "username": "owner_user",
    "email": "owner@example.com",
    "full_name": "John Doe",
    "nic_number": "123456789V",
    "mobile_number": "+94771234567",
    "whatsapp_number": "+94771234567",
    "address": "123 Main Street, Colombo 01",
    "profile_status": "verified",
    "profile_image": "https://siteurl.com/wp-content/uploads/2024/01/profile.jpg",
    "roles": ["vehicle_owner"]
  }
}
```

---

### 9. Update User Profile

**Request:**
```http
PUT /user/profile
Content-Type: application/json
```

**Request Body:**
```json
{
  "full_name": "John Doe",
  "nic_number": "123456789V",
  "mobile_number": "+94771234567",
  "whatsapp_number": "+94771234567",
  "address": "123 Main Street, Colombo 01",
  "email": "newemail@example.com",
  "profile_image": "https://url-to-image.jpg"
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| full_name | string | No | Full name of the owner |
| nic_number | string | No | National Identity Card number |
| mobile_number | string | No | Contact phone number |
| whatsapp_number | string | No | WhatsApp number |
| address | string | No | Address |
| email | string | No | Email address |
| profile_image | string | No | Profile image URL or base64 |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully"
}
```

**Error Responses:**

400 Bad Request (invalid email):
```json
{
  "code": "invalid_email",
  "message": "Invalid email address",
  "data": {
    "status": 400
  }
}
```

---

### 10. Get Authentication Status

**Request:**
```http
GET /auth/status
```

**Response (200 OK - Authenticated):**
```json
{
  "authenticated": true,
  "user": {
    "id": 456,
    "username": "owner_user",
    "email": "owner@example.com",
    "roles": ["vehicle_owner"]
  }
}
```

**Response (200 OK - Not Authenticated):**
```json
{
  "authenticated": false,
  "user": null
}
```

---

## Vehicle Features List

Available vehicle features that can be specified when creating/updating vehicles:

### Comfort Features
- `high_back_seats` - High-back Adjustable Seats
- `adjustable_armrests` - Adjustable Armrests
- `extra_legroom` - Extra legroom / spacious interior
- `individual_seatbelts` - Individual seat belts
- `full_ac` - Full air-conditioning
- `tinted_windows` - Tinted / curtained windows for privacy & sun protection

### Amenities
- `coolbox` - Coolbox / refrigerator for refreshments
- `overhead_racks` - Overhead luggage racks / bottle holders
- `boot_luggage` - Boot (Trunk) Luggage Space
- `overhead_racks_space` - Overhead Racks Space
- `underfloor_luggage` - Underfloor Luggage Space
- `lcd_screens` - LCD / LED TV screens
- `audio_system` - High-quality audio system with Bluetooth/USB
- `wifi_free` - WiFi connectivity Free
- `microphone_pa` - Microphone / PA system (for guides & tour leaders)
- `usb_charging` - USB charging ports / 230V power outlets

### Safety Features
- `abs_ebs` - ABS / EBS braking systems
- `airbags` - Airbags (in newer models)
- `fire_extinguisher` - Fire extinguisher & first aid kit
- `gps_tracking` - GPS tracking for route safety
- `emergency_exits` - Emergency exits & hammers

### Lighting & Environment
- `led_mood_lights` - LED mood / ceiling lights
- `reading_lamps` - Reading lamps for individual seats
- `air_suspension` - Air suspension for smoother ride
- `onboard_restroom` - Onboard restroom (in select long-distance coaches)
- `panoramic_windows` - Panoramic windows with wide viewing angles

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (missing or invalid parameters) |
| 401 | Unauthorized (not logged in) |
| 403 | Forbidden (no permission to perform action) |
| 404 | Not Found (resource doesn't exist) |
| 500 | Server Error |

---

## Examples

### JavaScript/Fetch API

#### Get all verified vehicles
```javascript
fetch('https://siteurl.com/wp-json/lankashuttle/v1/vehicles?status=verified&page=1&per_page=10')
  .then(response => response.json())
  .then(data => console.log(data));
```

#### Create a new vehicle (with authentication)
```javascript
const vehicleData = {
  vehicle_type: "Toyota Coaster",
  vehicle_model: "High Roof",
  year_manufacture: 2023,
  year_registration: 2023,
  license_plate: "WP-2023-AB1234",
  seating_capacity: 29,
  vehicle_features: ["high_back_seats", "full_ac", "wifi_free"]
};

fetch('https://siteurl.com/wp-json/lankashuttle/v1/vehicles', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include', // Include cookies
  body: JSON.stringify(vehicleData)
})
  .then(response => response.json())
  .then(data => console.log(data));
```

#### Update vehicle availability
```javascript
const availabilityData = {
  availability_data: [
    {
      dates: ["2024-02-10", "2024-02-11"],
      note: "Wedding event - not available"
    }
  ]
};

fetch('https://siteurl.com/wp-json/lankashuttle/v1/vehicles/123/availability', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include',
  body: JSON.stringify(availabilityData)
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### cURL

#### Get all vehicles
```bash
curl -X GET "https://siteurl.com/wp-json/lankashuttle/v1/vehicles?status=verified"
```

#### Create a vehicle
```bash
curl -X POST "https://siteurl.com/wp-json/lankashuttle/v1/vehicles" \
  -H "Content-Type: application/json" \
  -b "wordpress_cookies.txt" \
  -d '{
    "vehicle_type": "Toyota Coaster",
    "vehicle_model": "High Roof",
    "year_manufacture": 2023,
    "year_registration": 2023,
    "license_plate": "WP-2023-AB1234",
    "seating_capacity": 29
  }'
```

---
