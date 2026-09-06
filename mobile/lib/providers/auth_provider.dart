import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

enum AuthState { initial, loading, authenticated, unauthenticated, error }

class AuthProvider with ChangeNotifier {
  AuthState _state = AuthState.initial;
  User? _user;
  String? _error;
  
  AuthState get state => _state;
  User? get user => _user;
  String? get error => _error;
  bool get isAuthenticated => _state == AuthState.authenticated;
  
  // Check initial auth state
  Future<void> checkAuth() async {
    _state = AuthState.loading;
    notifyListeners();
    
    try {
      final isLoggedIn = await ApiService.isLoggedIn();
      if (isLoggedIn) {
        await _fetchUser();
      } else {
        _state = AuthState.unauthenticated;
        notifyListeners();
      }
    } catch (e) {
      _state = AuthState.unauthenticated;
      notifyListeners();
    }
  }
  
  // Login
  Future<bool> login(String email, String password) async {
    _state = AuthState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.post(
        ApiConfig.login,
        body: {
          'email': email,
          'password': password,
        },
      );
      
      final data = response['data'];
      final token = data['token'];
      final userData = data['user'];
      
      if (token != null) {
        await ApiService.saveToken(token);
        
        // Use user data from login response directly (no extra /api/me call)
        if (userData != null) {
          _setUserFromResponse(userData);
        } else {
          // Fallback: fetch user from /api/me
          await _fetchUser();
        }
        return true;
      }
      
      _error = 'Token tidak ditemukan';
      _state = AuthState.error;
      notifyListeners();
      return false;
    } on ApiException catch (e) {
      _error = e.message;
      _state = AuthState.error;
      notifyListeners();
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan. Silakan coba lagi.';
      _state = AuthState.error;
      notifyListeners();
      return false;
    }
  }
  
  // Register
  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
    String? phone,
  }) async {
    _state = AuthState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.post(
        ApiConfig.register,
        body: {
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'role': role,
          if (phone != null) 'phone': phone,
        },
      );
      
      final data = response['data'];
      final token = data['token'];
      final userData = data['user'];
      
      if (token != null) {
        await ApiService.saveToken(token);
        
        // Use user data from register response directly (no extra /api/me call)
        if (userData != null) {
          _setUserFromResponse(userData);
        } else {
          await _fetchUser();
        }
        return true;
      }
      
      _error = 'Token tidak ditemukan';
      _state = AuthState.error;
      notifyListeners();
      return false;
    } on ApiException catch (e) {
      _error = e.message;
      _state = AuthState.error;
      notifyListeners();
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan. Silakan coba lagi.';
      _state = AuthState.error;
      notifyListeners();
      return false;
    }
  }
  
  // Logout
  Future<void> logout() async {
    try {
      await ApiService.post(ApiConfig.logout);
    } catch (e) {
      // Ignore logout error
    }
    
    await ApiService.clearToken();
    _user = null;
    _state = AuthState.unauthenticated;
    notifyListeners();
  }
  
  // Fetch current user from /api/me
  Future<void> _fetchUser() async {
    try {
      final response = await ApiService.get(ApiConfig.me);
      _user = User.fromJson(response['data']);
      _state = AuthState.authenticated;
    } catch (e) {
      _state = AuthState.unauthenticated;
      _error = 'Gagal memuat profil.';
    }
    notifyListeners();
  }

  // Set user directly from login/register response (avoids extra /api/me call)
  void _setUserFromResponse(Map<String, dynamic> userData) {
    _user = User.fromJson(userData);
    _state = AuthState.authenticated;
    notifyListeners();
  }
  
  // Update user profile
  Future<bool> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await ApiService.put(ApiConfig.me, body: data);
      _user = User.fromJson(response['data']);
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return false;
    }
  }
  
  // Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
