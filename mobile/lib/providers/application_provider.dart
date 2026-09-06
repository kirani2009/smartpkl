import 'package:flutter/material.dart';
import '../models/application_model.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

enum ApplicationState { initial, loading, loaded, error, empty }

class ApplicationProvider with ChangeNotifier {
  ApplicationState _state = ApplicationState.initial;
  List<Application> _applications = [];
  Application? _selectedApplication;
  String? _error;
  Map<String, dynamic>? _stats;
  
  ApplicationState get state => _state;
  List<Application> get applications => _applications;
  Application? get selectedApplication => _selectedApplication;
  String? get error => _error;
  Map<String, dynamic>? get stats => _stats;
  
  // Fetch applications
  Future<void> fetchApplications({String? status}) async {
    _state = ApplicationState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final queryParams = <String, String>{};
      if (status != null && status.isNotEmpty) {
        queryParams['status'] = status;
      }
      
      final queryString = queryParams.entries
          .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
          .join('&');
      
      final endpoint = queryString.isNotEmpty
          ? '${ApiConfig.studentApplications}?$queryString'
          : ApiConfig.studentApplications;
      
      final response = await ApiService.get(endpoint);
      _applications = (response['data'] as List?)
              ?.map((json) => Application.fromJson(json))
              .toList() ?? [];
      
      _state = _applications.isEmpty 
          ? ApplicationState.empty 
          : ApplicationState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = ApplicationState.error;
    } catch (e) {
      _error = 'Gagal memuat data';
      _state = ApplicationState.error;
    }
    
    notifyListeners();
  }
  
  // Fetch application detail
  Future<void> fetchApplicationDetail(int id) async {
    _state = ApplicationState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.get(
        '${ApiConfig.studentApplications}/$id',
      );
      _selectedApplication = Application.fromJson(response['data']);
      _state = ApplicationState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = ApplicationState.error;
    } catch (e) {
      _error = 'Gagal memuat detail';
      _state = ApplicationState.error;
    }
    
    notifyListeners();
  }
  
  // Cancel application
  Future<bool> cancelApplication(int applicationId) async {
    try {
      await ApiService.delete(
        '${ApiConfig.studentApplications}/$applicationId',
      );
      
      // Update status in list
      final index = _applications.indexWhere((a) => a.id == applicationId);
      if (index != -1) {
        _applications[index] = Application(
          id: _applications[index].id,
          status: 'cancelled',
          coverLetter: _applications[index].coverLetter,
          createdAt: _applications[index].createdAt,
          updatedAt: DateTime.now(),
          internship: _applications[index].internship,
          student: _applications[index].student,
        );
      }
      
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
