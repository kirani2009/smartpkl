import 'package:flutter/material.dart';
import '../models/internship_model.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';

enum InternshipState { initial, loading, loaded, error, empty }

class InternshipProvider with ChangeNotifier {
  InternshipState _state = InternshipState.initial;
  List<Internship> _internships = [];
  List<Internship> _savedInternships = [];
  Internship? _selectedInternship;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = true;
  
  InternshipState get state => _state;
  List<Internship> get internships => _internships;
  List<Internship> get savedInternships => _savedInternships;
  Internship? get selectedInternship => _selectedInternship;
  String? get error => _error;
  bool get hasMore => _hasMore;
  
  // Fetch internships with optional search and filters
  Future<void> fetchInternships({
    String? search,
    Map<String, dynamic>? filters,
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      _internships = [];
    }
    
    if (!_hasMore && !refresh) return;
    
    _state = InternshipState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final queryParams = <String, String>{
        'page': _currentPage.toString(),
      };
      
      if (search != null && search.isNotEmpty) {
        queryParams['search'] = search;
      }
      
      if (filters != null) {
        filters.forEach((key, value) {
          if (value != null && value.toString().isNotEmpty) {
            queryParams[key] = value.toString();
          }
        });
      }
      
      final queryString = queryParams.entries
          .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
          .join('&');
      
      final endpoint = '${ApiConfig.studentInternships}?$queryString';
      final response = await ApiService.get(endpoint);
      
      final data = response['data'];
      final List<Internship> newInternships = (data['data'] as List?)
              ?.map((json) => Internship.fromJson(json))
              .toList() ?? [];
      
      if (refresh) {
        _internships = newInternships;
      } else {
        _internships.addAll(newInternships);
      }
      
      _hasMore = data['current_page'] < data['last_page'];
      _currentPage++;
      
      _state = _internships.isEmpty 
          ? InternshipState.empty 
          : InternshipState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = InternshipState.error;
    } catch (e) {
      _error = 'Gagal memuat data';
      _state = InternshipState.error;
    }
    
    notifyListeners();
  }
  
  // Fetch internship detail
  Future<void> fetchInternshipDetail(int id) async {
    _state = InternshipState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.get(
        '${ApiConfig.studentInternships}/$id',
      );
      _selectedInternship = Internship.fromJson(response['data']);
      _state = InternshipState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = InternshipState.error;
    } catch (e) {
      _error = 'Gagal memuat detail';
      _state = InternshipState.error;
    }
    
    notifyListeners();
  }
  
  // Save/unsave internship
  Future<bool> toggleSave(int internshipId) async {
    try {
      final internship = _internships.firstWhere(
        (i) => i.id == internshipId,
        orElse: () => _selectedInternship!,
      );
      
      if (internship.isSaved == true) {
        await ApiService.delete(
          '${ApiConfig.studentInternships}/$internshipId/save',
        );
      } else {
        await ApiService.post(
          '${ApiConfig.studentInternships}/$internshipId/save',
        );
      }
      
      // Update in list
      final index = _internships.indexWhere((i) => i.id == internshipId);
      if (index != -1) {
        _internships[index] = Internship(
          id: internship.id,
          title: internship.title,
          description: internship.description,
          requirements: internship.requirements,
          benefits: internship.benefits,
          location: internship.location,
          type: internship.type,
          startDate: internship.startDate,
          endDate: internship.endDate,
          quota: internship.quota,
          appliedCount: internship.appliedCount,
          status: internship.status,
          company: internship.company,
          major: internship.major,
          createdAt: internship.createdAt,
          isSaved: !(internship.isSaved ?? false),
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
  
  // Fetch saved internships
  Future<void> fetchSavedInternships() async {
    _state = InternshipState.loading;
    _error = null;
    notifyListeners();
    
    try {
      final response = await ApiService.get(ApiConfig.studentSavedInternships);
      _savedInternships = (response['data'] as List?)
              ?.map((json) => Internship.fromJson(json))
              .toList() ?? [];
      
      _state = _savedInternships.isEmpty 
          ? InternshipState.empty 
          : InternshipState.loaded;
    } on ApiException catch (e) {
      _error = e.message;
      _state = InternshipState.error;
    } catch (e) {
      _error = 'Gagal memuat data';
      _state = InternshipState.error;
    }
    
    notifyListeners();
  }
  
  // Apply to internship
  Future<bool> applyInternship(int internshipId, {String? coverLetter}) async {
    try {
      await ApiService.post(
        '${ApiConfig.studentInternships}/$internshipId/apply',
        body: {
          if (coverLetter != null) 'cover_letter': coverLetter,
        },
      );
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
