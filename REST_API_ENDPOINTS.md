# Shuttle Vehicle Manager - REST API Quick Reference

**Base URL:** `https://lankashuttle.lk/wp-json/lankashuttle/v1`

## Public Endpoints (No Authentication Required)

| Method | Endpoint | Description | Query Params |
|--------|----------|-------------|--------------|
| GET | `/vehicles` | List all vehicles | `page`, `per_page`, `status` |
| GET | `/vehicles/{id}` | Get vehicle details | — |
| GET | `/auth/status` | Check if user is logged in | — |

## Authenticated Endpoints (Requires Login)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|----------------|
| GET | `/my-vehicles` | Get current user's vehicles | Yes (vehicle_owner) |
| POST | `/vehicles` | Create new vehicle | Yes (vehicle_owner) |
| PUT | `/vehicles/{id}` | Update vehicle | Yes (owner/admin) |
| DELETE | `/vehicles/{id}` | Delete vehicle | Yes (owner/admin) |
| POST | `/vehicles/{id}/availability` | Update vehicle availability | Yes (owner/admin) |
| GET | `/user/profile` | Get current user profile | Yes |
| PUT | `/user/profile` | Update user profile | Yes |

---

## Request/Response Examples

### 1. GET /vehicles - List Vehicles
```
Request:  GET /vehicles?page=1&per_page=20&status=verified
Response: 
{
  "success": true,
  "data": [...],
  "pagination": { "total": 150, "pages": 8, "current_page": 1, "per_page": 20 }
}
```

### 2. GET /vehicles/{id} - Get Vehicle Details
```
Request:  GET /vehicles/123
Response:
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
    "features": [array],
    "images": [array],
    "documents": {...}
  }
}
```

### 3. GET /my-vehicles - Get user's vehicles
```
Request:  GET /my-vehicles (with auth)
Response:
{
  "success": true,
  "data": [...],
  "count": 2
}
```

### 4. POST /vehicles - Create Vehicle
```
Request:  POST /vehicles
Body:     {
  "vehicle_type": "Toyota Coaster",
  "vehicle_model": "High Roof",
  "year_manufacture": 2023,
  "year_registration": 2023,
  "license_plate": "WP-2023-AB1234",
  "seating_capacity": 29,
  "vehicle_features": ["high_back_seats", "full_ac"],
  "vehicle_images": ["https://..."],
  "rc_document": "https://...",
  ...
}
Response: 
{
  "success": true,
  "message": "Vehicle created successfully and is pending verification",
  "data": { "id": 125, "status": "pending" }
}
```

### 5. PUT /vehicles/{id} - Update Vehicle
```
Request:  PUT /vehicles/123
Body:     {
  "vehicle_model": "Super Luxury",
  "seating_capacity": 35,
  ...
}
Response:
{
  "success": true,
  "message": "Vehicle updated successfully and is now pending verification",
  "data": {...}
}
```

### 6. DELETE /vehicles/{id} - Delete Vehicle
```
Request:  DELETE /vehicles/123
Response:
{
  "success": true,
  "message": "Vehicle deleted successfully"
}
```

### 7. POST /vehicles/{id}/availability - Update Availability
```
Request:  POST /vehicles/123/availability
Body:     {
  "availability_data": [
    {
      "dates": ["2024-02-10", "2024-02-11"],
      "note": "Wedding event - not available"
    }
  ]
}
Response:
{
  "success": true,
  "message": "Availability updated successfully"
}
```

### 8. GET /user/profile - Get Profile
```
Request:  GET /user/profile (with auth)
Response:
{
  "success": true,
  "data": {
    "id": 456,
    "username": "owner_user",
    "email": "owner@example.com",
    "full_name": "John Doe",
    "mobile_number": "+94771234567",
    "profile_status": "verified",
    "profile_image": "https://...",
    ...
  }
}
```

### 9. PUT /user/profile - Update Profile
```
Request:  PUT /user/profile (with auth)
Body:     {
  "full_name": "John Doe",
  "mobile_number": "+94771234567",
  "email": "newemail@example.com",
  ...
}
Response:
{
  "success": true,
  "message": "Profile updated successfully"
}
```

### 10. GET /auth/status - Check Authentication
```
Request:  GET /auth/status
Response (Authenticated):
{
  "authenticated": true,
  "user": {
    "id": 456,
    "username": "owner_user",
    "email": "owner@example.com",
    "roles": ["vehicle_owner"]
  }
}

Response (Not Authenticated):
{
  "authenticated": false,
  "user": null
}
```

---

## Vehicle Features (Available Values)

**Comfort:**
- high_back_seats
- adjustable_armrests
- extra_legroom
- individual_seatbelts
- full_ac
- tinted_windows

**Amenities:**
- coolbox
- overhead_racks
- boot_luggage
- overhead_racks_space
- underfloor_luggage
- lcd_screens
- audio_system
- wifi_free
- microphone_pa
- usb_charging

**Safety:**
- abs_ebs
- airbags
- fire_extinguisher
- gps_tracking
- emergency_exits

**Lighting/Environment:**
- led_mood_lights
- reading_lamps
- air_suspension
- onboard_restroom
- panoramic_windows

---

## HTTP Status Codes

| Code | Message | Meaning |
|------|---------|---------|
| 200 | OK | Request successful |
| 400 | Bad Request | Invalid parameters or missing required fields |
| 401 | Unauthorized | Not logged in (for protected endpoints) |
| 403 | Forbidden | No permission to perform action |
| 404 | Not Found | Resource doesn't exist |
| 500 | Internal Server Error | Server-side error |

---

## Error Response Format

All error responses follow this format:

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 400
  }
}
```

**Common Error Codes:**
- `missing_field` - Required field is missing
- `invalid_email` - Invalid email format
- `vehicle_not_found` - Vehicle with given ID doesn't exist
- `vehicle_creation_failed` - Error creating vehicle
- `deletion_failed` - Error deleting vehicle
- `rest_not_logged_in` - User is not authenticated
- `rest_forbidden` - User doesn't have permission

---

## Implementation Notes

1. **Base URL:** All endpoints are prefixed with `/wp-json/lankashuttle/v1/`
2. **Content Type:** Use `Content-Type: application/json` for POST/PUT requests
3. **Authentication:** 
   - For web: Include WordPress session cookies automatically
   - For mobile app: Use session-based auth or implement JWT token support
4. **Images & Documents:** Can be passed as:
   - Full URLs (if already uploaded)
   - Base64-encoded strings (will be uploaded to WordPress media library)
5. **Availability Data:** Dates must be in `YYYY-MM-DD` format
6. **Status Values:** `pending` or `verified`

---

## Mobile App Integration Example (React Native/Flutter)

```javascript
// Base API Configuration
const API_BASE = "https://lankashuttle.lk/wp-json/lankashuttle/v1";

// Get all vehicles
async function getVehicles(page = 1, status = null) {
  const query = new URLSearchParams();
  query.append("page", page);
  query.append("per_page", 20);
  if (status) query.append("status", status);
  
  const response = await fetch(`${API_BASE}/vehicles?${query}`);
  return response.json();
}

// Create vehicle
async function createVehicle(vehicleData, token) {
  const response = await fetch(`${API_BASE}/vehicles`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${token}`
    },
    body: JSON.stringify(vehicleData)
  });
  return response.json();
}

// Update availability
async function updateAvailability(vehicleId, availability, token) {
  const response = await fetch(`${API_BASE}/vehicles/${vehicleId}/availability`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${token}`
    },
    body: JSON.stringify({ availability_data: availability })
  });
  return response.json();
}
```

---

For detailed documentation, see `REST_API_DOCUMENTATION.md`
