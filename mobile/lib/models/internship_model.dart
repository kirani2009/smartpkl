class Internship {
  final int id;
  final String title;
  final String description;
  final String? requirements;
  final String? benefits;
  final String? location;
  final String? type; // full_time, part_time, remote
  final DateTime? startDate;
  final DateTime? endDate;
  final int? quota;
  final int? appliedCount;
  final String status; // active, closed, draft
  final Company? company;
  final Major? major;
  final DateTime createdAt;
  final bool? isSaved;

  Internship({
    required this.id,
    required this.title,
    required this.description,
    this.requirements,
    this.benefits,
    this.location,
    this.type,
    this.startDate,
    this.endDate,
    this.quota,
    this.appliedCount,
    required this.status,
    this.company,
    this.major,
    required this.createdAt,
    this.isSaved,
  });

  factory Internship.fromJson(Map<String, dynamic> json) {
    return Internship(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      requirements: json['requirements'],
      benefits: json['benefits'],
      location: json['location'],
      type: json['type'],
      startDate: json['start_date'] != null 
          ? DateTime.tryParse(json['start_date']) 
          : null,
      endDate: json['end_date'] != null 
          ? DateTime.tryParse(json['end_date']) 
          : null,
      quota: json['quota'],
      appliedCount: json['applied_count'],
      status: json['status'] ?? 'active',
      company: json['company'] != null 
          ? Company.fromJson(json['company']) 
          : null,
      major: json['major'] != null 
          ? Major.fromJson(json['major']) 
          : null,
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      isSaved: json['is_saved'],
    );
  }

  String get typeLabel {
    switch (type) {
      case 'full_time':
        return 'Full Time';
      case 'part_time':
        return 'Part Time';
      case 'remote':
        return 'Remote';
      default:
        return type ?? '-';
    }
  }

  String get statusLabel {
    switch (status) {
      case 'active':
        return 'Aktif';
      case 'closed':
        return 'Ditutup';
      case 'draft':
        return 'Draft';
      default:
        return status;
    }
  }
}

class Company {
  final int id;
  final String name;
  final String? logo;
  final String? industry;
  final String? address;

  Company({
    required this.id,
    required this.name,
    this.logo,
    this.industry,
    this.address,
  });

  factory Company.fromJson(Map<String, dynamic> json) {
    return Company(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      logo: json['logo'],
      industry: json['industry'],
      address: json['address'],
    );
  }
}

class Major {
  final int id;
  final String name;
  final String? school;

  Major({
    required this.id,
    required this.name,
    this.school,
  });

  factory Major.fromJson(Map<String, dynamic> json) {
    return Major(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      school: json['school'],
    );
  }
}
