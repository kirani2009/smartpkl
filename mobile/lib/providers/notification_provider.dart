import 'package:flutter/material.dart';
import '../models/notification_model.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

enum NotificationState { initial, loading, loaded, error, empty }

class NotificationProvider with ChangeNotifier {
  NotificationState _state = NotificationState.initial;
  List<AppNotification> _notifications = [];
  int _unreadCount = 0;
  String? _error;
  
  NotificationState get state => _state;
  List<AppNotification> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  String? get error => _error;
  
  // Fetch notifications
  Future<void> fetchNotifications({bool refresh = false}) async {
    if (refresh) {
      _notifications = [];
    }
    
    _state = NotificationState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.get(ApiConfig.notifications);
      _notifications = (response['data'] as List?)
              ?.map((json) => AppNotification.fromJson(json))
              .toList() ?? [];
      
      _state = _notifications.isEmpty 
          ? NotificationState.empty 
          : NotificationState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = NotificationState.error;
    } catch (e) {
      _error = 'Gagal memuat notifikasi';
      _state = NotificationState.error;
    }
    
    notifyListeners();
  }
  
  // Fetch unread count
  Future<void> fetchUnreadCount() async {
    try {
      final response = await ApiService.get(ApiConfig.unreadCount);
      _unreadCount = response['data']['count'] ?? 0;
      notifyListeners();
    } catch (e) {
      // Ignore error
    }
  }
  
  // Mark as read
  Future<bool> markAsRead(int notificationId) async {
    try {
      await ApiService.put(
        '${ApiConfig.notifications}/$notificationId/read',
      );
      
      // Update in list
      final index = _notifications.indexWhere((n) => n.id == notificationId);
      if (index != -1) {
        _notifications[index] = AppNotification(
          id: _notifications[index].id,
          title: _notifications[index].title,
          message: _notifications[index].message,
          type: _notifications[index].type,
          isRead: true,
          url: _notifications[index].url,
          createdAt: _notifications[index].createdAt,
        );
      }
      
      _unreadCount = (_unreadCount - 1).clamp(0, 999);
      notifyListeners();
      return true;
    } catch (e) {
      return false;
    }
  }
  
  // Mark all as read
  Future<bool> markAllAsRead() async {
    try {
      await ApiService.put('${ApiConfig.notifications}/read-all');
      
      _notifications = _notifications.map((n) => AppNotification(
        id: n.id,
        title: n.title,
        message: n.message,
        type: n.type,
        isRead: true,
        url: n.url,
        createdAt: n.createdAt,
      )).toList();
      
      _unreadCount = 0;
      notifyListeners();
      return true;
    } catch (e) {
      return false;
    }
  }
  
  // Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
