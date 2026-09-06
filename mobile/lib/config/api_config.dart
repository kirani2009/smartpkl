class ApiConfig {
  // Base URL - change this to your actual API URL
  // For local development, use your machine's IP address
  // For production, use your domain
  static const String baseUrl = 'http://10.0.2.2:8000/api'; // Android emulator
  // static const String baseUrl = 'http://localhost:8000/api'; // iOS simulator
  // static const String baseUrl = 'https://yourdomain.com/api'; // Production

  // Auth endpoints
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String logout = '/auth/logout';
  static const String me = '/me';

  // Student endpoints
  static const String studentProfile = '/me/student';
  static const String studentInternships = '/student/internships';
  static const String studentApplications = '/student/applications';
  static const String studentInterviews = '/student/interviews';
  static const String studentSavedInternships = '/student/saved-internships';

  // Notifications
  static const String notifications = '/notifications';
  static const String unreadCount = '/notifications/unread-count';

  // Timeout duration
  static const int timeoutDuration = 30; // seconds
}
