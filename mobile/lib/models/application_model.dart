import 'internship_model.dart';

class Application {
  final int id;
  final String status; // pending, reviewed, accepted, rejected, cancelled
  final String? coverLetter;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Internship? internship;
  final Student? student;

  Application({
    required this.id,
    required this.status,
    this.coverLetter,
    required this.createdAt,
    required this.updatedAt,
    this.internship,
    this.student,
  });

  factory Application.fromJson(Map<String, dynamic> json) {
    return Application(
      id: json['id'] ?? 0,
      status: json['status'] ?? 'pending',
      coverLetter: json['cover_letter'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      updatedAt: DateTime.tryParse(json['updated_at'] ?? '') ?? DateTime.now(),
      internship: json['internship'] != null 
          ? Internship.fromJson(json['internship']) 
          : null,
      student: json['student'] != null 
          ? Student.fromJson(json['student']) 
          : null,
    );
  }

  String get statusLabel {
    switch (status) {
      case 'pending':
        return 'Menunggu';
      case 'reviewed':
        return 'Ditinjau';
      case 'accepted':
        return 'Diterima';
      case 'rejected':
        return 'Ditolak';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status;
    }
  }
}

class Student {
  final int id;
  final String name;
  final String? email;
  final String? phone;
  final Major? major;

  Student({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.major,
  });

  factory Student.fromJson(Map<String, dynamic> json) {
    return Student(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'],
      phone: json['phone'],
      major: json['major'] != null 
          ? Major.fromJson(json['major']) 
          : null,
    );
  }
}
