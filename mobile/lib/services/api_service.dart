import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class ApiService {
  static String? _token;
  
  // Get stored token
  static Future<String?> getToken() async {
    if (_token != null) return _token;
    
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    return _token;
  }
  
  // Save token
  static Future<void> saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }
  
  // Clear token
  static Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }
  
  // Check if logged in
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
  
  // Get headers with auth token
  static Future<Map<String, String>> _getHeaders() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }
  
  // Generic GET request
  static Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}$endpoint'),
        headers: headers,
      ).timeout(const Duration(seconds: ApiConfig.timeoutDuration));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Gagal mengambil data: ${e.toString()}');
    }
  }
  
  // Generic POST request
  static Future<Map<String, dynamic>> post(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}$endpoint'),
        headers: headers,
        body: body != null ? json.encode(body) : null,
      ).timeout(const Duration(seconds: ApiConfig.timeoutDuration));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Gagal mengirim data: ${e.toString()}');
    }
  }
  
  // Generic PUT request
  static Future<Map<String, dynamic>> put(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final headers = await _getHeaders();
      final response = await http.put(
        Uri.parse('${ApiConfig.baseUrl}$endpoint'),
        headers: headers,
        body: body != null ? json.encode(body) : null,
      ).timeout(const Duration(seconds: ApiConfig.timeoutDuration));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Gagal memperbarui data: ${e.toString()}');
    }
  }
  
  // Generic DELETE request
  static Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final headers = await _getHeaders();
      final response = await http.delete(
        Uri.parse('${ApiConfig.baseUrl}$endpoint'),
        headers: headers,
      ).timeout(const Duration(seconds: ApiConfig.timeoutDuration));
      
      return _handleResponse(response);
    } catch (e) {
      throw ApiException('Gagal menghapus data: ${e.toString()}');
    }
  }
  
  // Handle API response
  static Map<String, dynamic> _handleResponse(http.Response response) {
    final body = json.decode(response.body);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    } else if (response.statusCode == 401) {
      // Unauthorized - clear token and use backend error message
      clearToken();
      throw ApiException(body['message'] ?? 'Sesi telah berakhir. Silakan login kembali.');
    } else if (response.statusCode == 422) {
      // Validation error
      final errors = body['errors'];
      if (errors != null) {
        final messages = <String>[];
        errors.forEach((key, value) {
          if (value is List) {
            messages.addAll(value.map((e) => e.toString()));
          }
        });
        throw ApiException(messages.join('\n'));
      }
      throw ApiException(body['message'] ?? 'Validasi gagal');
    } else {
      throw ApiException(body['message'] ?? 'Terjadi kesalahan');
    }
  }
}

class ApiException implements Exception {
  final String message;
  
  ApiException(this.message);
  
  @override
  String toString() => message;
}
