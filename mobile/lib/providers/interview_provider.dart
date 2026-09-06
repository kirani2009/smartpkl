import 'package:flutter/material.dart';
import '../models/interview_model.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

enum InterviewState { initial, loading, loaded, error, empty }

class InterviewProvider with ChangeNotifier {
  InterviewState _state = InterviewState.initial;
  List<Interview> _interviews = [];
  Interview? _selectedInterview;
  String? _error;
  
  InterviewState get state => _state;
  List<Interview> get interviews => _interviews;
  Interview? get selectedInterview => _selectedInterview;
  String? get error => _error;
  
  // Fetch interviews
  Future<void> fetchInterviews({String? status}) async {
    _state = InterviewState.loading;
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
          ? '${ApiConfig.studentInterviews}?$queryString'
          : ApiConfig.studentInterviews;
      
      final response = await ApiService.get(endpoint);
      _interviews = (response['data'] as List?)
              ?.map((json) => Interview.fromJson(json))
              .toList() ?? [];
      
      _state = _interviews.isEmpty 
          ? InterviewState.empty 
          : InterviewState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = InterviewState.error;
    } catch (e) {
      _error = 'Gagal memuat data';
      _state = InterviewState.error;
    }
    
    notifyListeners();
  }
  
  // Fetch interview detail
  Future<void> fetchInterviewDetail(int id) async {
    _state = InterviewState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.get(
        '${ApiConfig.studentInterviews}/$id',
      );
      _selectedInterview = Interview.fromJson(response['data']);
      _state = InterviewState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = InterviewState.error;
    } catch (e) {
      _error = 'Gagal memuat detail';
      _state = InterviewState.error;
    }
    
    notifyListeners();
  }
  
  // Clear error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
