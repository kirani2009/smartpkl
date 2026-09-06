class AppNotification {
  final int id;
  final String title;
  final String message;
  final String? type; // application, interview, partnership, system
  final bool isRead;
  final String? url;
  final DateTime createdAt;

  AppNotification({
    required this.id,
    required this.title,
    required this.message,
    this.type,
    required this.isRead,
    this.url,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      type: json['type'],
      isRead: json['is_read'] ?? false,
      url: json['url'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
    );
  }

  String get typeLabel {
    switch (type) {
      case 'application':
        return 'Lamaran';
      case 'interview':
        return 'Interview';
      case 'partnership':
        return 'Partnership';
      case 'system':
        return 'Sistem';
      default:
        return type ?? 'Umum';
    }
  }
}
