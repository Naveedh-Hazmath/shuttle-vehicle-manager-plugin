# Mobile App Integration Guide

**For:** Mobile App Development Team  
**API Version:** 1.0 (REST API v1)  
**Base URL:** `https://lankashuttle.lk/wp-json/lankashuttle/v1`

---

## Quick Start

### 1. Setup API Client

**JavaScript/React Native:**
```javascript
const API_BASE_URL = "https://lankashuttle.lk/wp-json/lankashuttle/v1";

class ShuttleAPI {
  constructor(baseURL = API_BASE_URL) {
    this.baseURL = baseURL;
    this.token = null;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const headers = {
      "Content-Type": "application/json",
      ...options.headers
    };

    if (this.token) {
      headers["Authorization"] = `Bearer ${this.token}`;
    }

    const response = await fetch(url, {
      ...options,
      headers
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `API Error: ${response.status}`);
    }

    return response.json();
  }

  // Public endpoints
  getVehicles(page = 1, perPage = 20, status = null) {
    const params = new URLSearchParams({
      page,
      per_page: perPage
    });
    if (status) params.append("status", status);
    return this.request(`/vehicles?${params}`);
  }

  getVehicle(id) {
    return this.request(`/vehicles/${id}`);
  }

  getAuthStatus() {
    return this.request("/auth/status");
  }

  // Authenticated endpoints
  getMyVehicles() {
    return this.request("/my-vehicles");
  }

  createVehicle(vehicleData) {
    return this.request("/vehicles", {
      method: "POST",
      body: JSON.stringify(vehicleData)
    });
  }

  updateVehicle(id, vehicleData) {
    return this.request(`/vehicles/${id}`, {
      method: "PUT",
      body: JSON.stringify(vehicleData)
    });
  }

  deleteVehicle(id) {
    return this.request(`/vehicles/${id}`, {
      method: "DELETE"
    });
  }

  updateAvailability(id, availabilityData) {
    return this.request(`/vehicles/${id}/availability`, {
      method: "POST",
      body: JSON.stringify({ availability_data: availabilityData })
    });
  }

  getUserProfile() {
    return this.request("/user/profile");
  }

  updateUserProfile(profileData) {
    return this.request("/user/profile", {
      method: "PUT",
      body: JSON.stringify(profileData)
    });
  }

  setToken(token) {
    this.token = token;
  }
}
```

**Flutter/Dart:**
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ShuttleAPI {
  static const String baseURL = 'https://lankashuttle.lk/wp-json/lankashuttle/v1';
  String? _token;

  ShuttleAPI({String? token}) : _token = token;

  Future<Map<String, dynamic>> _request(
    String endpoint, {
    String method = 'GET',
    Map<String, dynamic>? body,
  }) async {
    final url = Uri.parse('$baseURL$endpoint');
    final headers = {
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };

    http.Response response;
    
    switch (method) {
      case 'POST':
        response = await http.post(url, headers: headers, body: jsonEncode(body));
        break;
      case 'PUT':
        response = await http.put(url, headers: headers, body: jsonEncode(body));
        break;
      case 'DELETE':
        response = await http.delete(url, headers: headers);
        break;
      default:
        response = await http.get(url, headers: headers);
    }

    if (response.statusCode < 400) {
      return jsonDecode(response.body);
    } else {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'API Error');
    }
  }

  // Public endpoints
  Future<Map<String, dynamic>> getVehicles({
    int page = 1,
    int perPage = 20,
    String? status,
  }) {
    String query = '?page=$page&per_page=$perPage';
    if (status != null) query += '&status=$status';
    return _request('/vehicles$query');
  }

  Future<Map<String, dynamic>> getVehicle(int id) {
    return _request('/vehicles/$id');
  }

  // Authenticated endpoints
  Future<Map<String, dynamic>> getMyVehicles() {
    return _request('/my-vehicles');
  }

  Future<Map<String, dynamic>> createVehicle(Map<String, dynamic> data) {
    return _request('/vehicles', method: 'POST', body: data);
  }

  Future<Map<String, dynamic>> updateVehicle(int id, Map<String, dynamic> data) {
    return _request('/vehicles/$id', method: 'PUT', body: data);
  }

  Future<Map<String, dynamic>> deleteVehicle(int id) {
    return _request('/vehicles/$id', method: 'DELETE');
  }

  void setToken(String token) {
    _token = token;
  }
}
```

---

## Typical Usage Flows

### 1. Check Authentication Status

```javascript
const api = new ShuttleAPI();

// Check if user is logged in
const authStatus = await api.getAuthStatus();

if (authStatus.authenticated) {
  console.log("User is logged in:", authStatus.user);
} else {
  console.log("User is not logged in");
  // Show login screen
}
```

### 2. Browse Available Vehicles

```javascript
// Get first page of verified vehicles
const response = await api.getVehicles(1, 20, 'verified');

console.log(`Found ${response.pagination.total} vehicles`);
console.log(`Displaying page ${response.pagination.current_page} of ${response.pagination.pages}`);

// Display vehicles
response.data.forEach(vehicle => {
  console.log(`${vehicle.type} ${vehicle.model} - ${vehicle.license_plate}`);
  console.log(`Seating: ${vehicle.seating_capacity}`);
  console.log(`Status: ${vehicle.status}`);
});

// Load next page
const page2 = await api.getVehicles(2, 20, 'verified');
```

### 3. View Vehicle Details

```javascript
const vehicleId = 123;
const vehicle = await api.getVehicle(vehicleId);

console.log("Vehicle Details:");
console.log(vehicle.data.title);
console.log(`Features: ${vehicle.data.features.length}`);
console.log(`Images: ${vehicle.data.images.length}`);
console.log(`Availability: ${vehicle.data.availability_data}`);
```

### 4. Get User's Vehicles

```javascript
// Get all vehicles owned by current user
const myVehicles = await api.getMyVehicles();

console.log(`You have ${myVehicles.data.length} vehicles`);

myVehicles.data.forEach(vehicle => {
  console.log(`${vehicle.title} - Status: ${vehicle.status}`);
});
```

### 5. Create a New Vehicle

```javascript
const newVehicle = {
  vehicle_type: "Toyota Coaster",
  vehicle_model: "High Roof",
  year_manufacture: 2023,
  year_registration: 2023,
  license_plate: "WP-2023-AB1234",
  seating_capacity: 29,
  vehicle_features: [
    "high_back_seats",
    "full_ac",
    "wifi_free",
    "usb_charging"
  ],
  vehicle_images: [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg"
  ]
};

const response = await api.createVehicle(newVehicle);
console.log(`Vehicle created with ID: ${response.data.id}`);
console.log(`Status: ${response.data.status}`); // "pending" initially
```

### 6. Update Vehicle Information

```javascript
const vehicleId = 123;

const updates = {
  vehicle_model: "Super Luxury",
  seating_capacity: 35,
  vehicle_features: ["high_back_seats", "full_ac", "lcd_screens"]
};

const response = await api.updateVehicle(vehicleId, updates);
console.log(response.message); // "Vehicle updated successfully..."
```

### 7. Manage Vehicle Availability

```javascript
const vehicleId = 123;

// Mark dates as unavailable
const availability = [
  {
    dates: ["2024-02-14", "2024-02-15"],
    note: "Valentine's Day - Wedding event"
  },
  {
    dates: ["2024-02-25", "2024-02-26", "2024-02-27"],
    note: "Scheduled maintenance"
  }
];

const response = await api.updateAvailability(vehicleId, availability);
console.log(response.message);
```

### 8. Get User Profile

```javascript
// Retrieve current user's profile
const profile = await api.getUserProfile();

console.log("User Profile:");
console.log(`Name: ${profile.data.full_name}`);
console.log(`Email: ${profile.data.email}`);
console.log(`Mobile: ${profile.data.mobile_number}`);
console.log(`Status: ${profile.data.profile_status}`);
Console.log(`Profile Image: ${profile.data.profile_image}`);
```

### 9. Update User Profile

```javascript
const updates = {
  full_name: "John Smith",
  mobile_number: "+94771234567",
  whatsapp_number: "+94771234567",
  email: "john@example.com",
  address: "123 Main St, Colombo 01",
  nic_number: "123456789V"
};

const response = await api.updateUserProfile(updates);
console.log(response.message);
```

### 10. Delete Vehicle

```javascript
const vehicleId = 123;

const response = await api.deleteVehicle(vehicleId);
console.log(response.message); // "Vehicle deleted successfully"
```

---

## Image & Document Handling

### Option 1: Upload from Camera/Gallery (Base64)

```javascript
// When user selects image from camera/gallery
async function handleImageUpload(imageFile) {
  // Convert to base64
  const base64Image = await fileToBase64(imageFile);
  
  const vehicleData = {
    vehicle_type: "Toyota Coaster",
    // ... other fields ...
    vehicle_images: [base64Image]
  };
  
  await api.createVehicle(vehicleData);
}

// Helper function to convert File to Base64
function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}
```

### Option 2: Use Existing URLs

```javascript
const vehicleData = {
  vehicle_type: "Toyota Coaster",
  // ... other fields ...
  vehicle_images: [
    "https://previouslyuploaded.url/image1.jpg",
    "https://previouslyuploaded.url/image2.jpg"
  ]
};

await api.createVehicle(vehicleData);
```

### Option 3: Documents (PDF/Images)

```javascript
const vehicleData = {
  // ... vehicle details ...
  rc_document: "https://example.com/rc.pdf", // PDF
  insurance_document: "data:application/pdf;base64,...", // Base64 PDF
  emission_document: "https://example.com/emission.jpg" // Image
};

await api.createVehicle(vehicleData);
```

---

## Error Handling

```javascript
async function safeApiCall() {
  try {
    const vehicles = await api.getVehicles();
    // Success
  } catch (error) {
    if (error.message.includes("401")) {
      // Not authenticated - show login
      console.log("Please log in");
    } else if (error.message.includes("404")) {
      // Not found
      console.log("Vehicle not found");
    } else if (error.message.includes("403")) {
      // Permission denied
      console.log("You don't have permission");
    } else {
      // Other error
      console.log("Error:", error.message);
    }
  }
}
```

---

## Pagination Example

```javascript
async function loadAllVehicles() {
  let allVehicles = [];
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = await api.getVehicles(page, 20, 'verified');
    
    allVehicles = [...allVehicles, ...response.data];
    
    // Check if there are more pages
    hasMore = page < response.pagination.pages;
    page++;
  }

  return allVehicles;
}
```

---

## Filtering & Search

### By Status
```javascript
// Only verified vehicles
const verified = await api.getVehicles(1, 20, 'verified');

// Only pending vehicles
const pending = await api.getVehicles(1, 20, 'pending');
```

### By Owner (My Vehicles)
```javascript
const myVehicles = await api.getMyVehicles();
```

---

## Feature Codes Reference

When creating/updating vehicles, use these feature codes:

```javascript
const AVAILABLE_FEATURES = {
  // Comfort
  high_back_seats: "High-back Adjustable Seats",
  adjustable_armrests: "Adjustable Armrests",
  extra_legroom: "Extra legroom / spacious interior",
  individual_seatbelts: "Individual seat belts",
  full_ac: "Full air-conditioning",
  tinted_windows: "Tinted / curtained windows",
  
  // Amenities
  coolbox: "Coolbox / refrigerator",
  overhead_racks: "Overhead luggage racks",
  boot_luggage: "Boot (Trunk) Luggage Space",
  lcd_screens: "LCD / LED TV screens",
  audio_system: "High-quality audio system",
  wifi_free: "WiFi connectivity",
  usb_charging: "USB charging ports",
  
  // Safety
  abs_ebs: "ABS / EBS braking systems",
  airbags: "Airbags",
  fire_extinguisher: "Fire extinguisher & first aid",
  gps_tracking: "GPS tracking",
  emergency_exits: "Emergency exits & hammers",
  
  // Lighting/Environment
  led_mood_lights: "LED mood lights",
  air_suspension: "Air suspension",
  onboard_restroom: "Onboard restroom",
  panoramic_windows: "Panoramic windows"
};
```

---

## Response Status Reference

**Vehicle Status Values:**
- `pending` - Awaiting admin verification
- `verified` - Approved and active

**User Profile Status Values:**
- `pending` - Profile needs admin verification
- `verified` - Profile is approved

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | User not logged in, implement login first |
| 403 Forbidden | User doesn't own the resource |
| 404 Not Found | Resource doesn't exist or ID is wrong |
| Network timeout | Increase timeout, check connection |
| CORS error (web) | Use credentials: 'include' in fetch |
| Invalid JSON | Ensure proper JSON formatting |

---

## Rate Limiting Considerations

Currently, no rate limiting is enforced. Implement the following best practices:

1. **Cache responses** when appropriate (list of features, vehicle details)
2. **Debounce requests** (e.g., search, auto-save)
3. **Batch requests** when possible
4. **Implement local storage** for offline access

---

## Security Tips

1. **Never log sensitive data** (API responses contain user emails, phones)
2. **Use HTTPS only** - All requests should go over HTTPS
3. **Validate input** - Check data before sending to API
4. **Handle errors gracefully** - Don't expose raw API errors to users
5. **Implement token refresh** - If using JWT (future enhancement)

---

## Performance Optimization

```javascript
// Cache vehicle list
let cachedVehicles = null;
let cacheTimestamp = null;
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

async function getVehiclesCached(page = 1) {
  const now = Date.now();
  
  if (cachedVehicles && (now - cacheTimestamp) < CACHE_DURATION) {
    return cachedVehicles;
  }
  
  cachedVehicles = await api.getVehicles(page);
  cacheTimestamp = now;
  return cachedVehicles;
}
```

---

## Support & Documentation

- Full API Docs: See `REST_API_DOCUMENTATION.md`
- Endpoint Reference: See `REST_API_ENDPOINTS.md`
- Implementation Details: See `IMPLEMENTATION_SUMMARY.md`

---

**API Status:** ✅ Ready for Integration  
**Last Updated:** February 19, 2026
