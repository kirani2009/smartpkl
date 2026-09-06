import 'application_model.dart';

class Interview {
  final int id;
  final DateTime scheduledAt;
  final String? location;
  final String? notes;
  final String status; // scheduled, completed, cancelled
  final Application? application;
  final DateTime createdAt;

  Interview({
    required this.id,
    required this.scheduledAt,
    this.location,
    this.notes,
    required this.status,
    this.application,
    required this.createdAt,
  });

  factory Interview.fromJson(Map<String, dynamic> json) {
    return Interview(
      id: json['id'] ?? 0,
      scheduledAt: DateTime.tryParse(json['scheduled_at'] ?? '') ?? DateTime.now(),
      location: json['location'],
      notes: json['notes'],
      status: json['status'] ?? 'scheduled',
      application: json['application'] != null 
          ? Application.fromJson(json['application']) 
          : null,
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
    );
  }

  String get statusLabel {
    switch (status) {
      case 'scheduled':
        return 'Terjadwal';
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status;
    }
  }
}
