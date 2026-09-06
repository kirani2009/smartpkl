import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/notification_provider.dart';
import '../../models/notification_model.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/error_widget.dart';
import '../../widgets/empty_widget.dart';
import 'package:timeago/timeago.dart' as timeago;

class NotificationListScreen extends StatefulWidget {
  const NotificationListScreen({super.key});
  
  @override
  State<NotificationListScreen> createState() => _NotificationListScreenState();
}

class _NotificationListScreenState extends State<NotificationListScreen> {
  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }
  
  Future<void> _loadNotifications() async {
    context.read<NotificationProvider>().fetchNotifications(refresh: true);
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Notifikasi',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1E293B),
          ),
        ),
        actions: [
          Consumer<NotificationProvider>(
            builder: (context, provider, child) {
              if (provider.unreadCount == 0) {
                return const SizedBox();
              }
              return TextButton(
                onPressed: () async {
                  await provider.markAllAsRead();
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Semua notifikasi ditandai sudah dibaca'),
                        backgroundColor: Colors.green,
                      ),
                    );
                  }
                },
                child: const Text('Baca Semua'),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadNotifications,
        child: Consumer<NotificationProvider>(
          builder: (context, provider, child) {
            if (provider.state == NotificationState.loading) {
              return const LoadingWidget(message: 'Memuat notifikasi...');
            }
            
            if (provider.state == NotificationState.error) {
              return ErrorWidgetCustom(
                message: provider.error ?? 'Terjadi kesalahan',
                onRetry: _loadNotifications,
              );
            }
            
            if (provider.state == NotificationState.empty) {
              return EmptyWidget(
                title: 'Tidak ada notifikasi',
                message: 'Notifikasi akan muncul di sini',
                icon: Icons.notifications_off_outlined,
              );
            }
            
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: provider.notifications.length,
              itemBuilder: (context, index) {
                final notification = provider.notifications[index];
                return _NotificationCard(
                  notification: notification,
                  onTap: () => _handleNotificationTap(notification),
                );
              },
            );
          },
        ),
      ),
    );
  }
  
  void _handleNotificationTap(AppNotification notification) async {
    final provider = context.read<NotificationProvider>();
    
    // Mark as read
    if (!notification.isRead) {
      await provider.markAsRead(notification.id);
    }
    
    // Navigate based on type
    if (mounted && notification.url != null) {
      // TODO: Implement navigation based on notification URL
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Navigasi ke: ${notification.url}'),
        ),
      );
    }
  }
}

class _NotificationCard extends StatelessWidget {
  final AppNotification notification;
  final VoidCallback onTap;
  
  const _NotificationCard({
    required this.notification,
    required this.onTap,
  });
  
  IconData _getTypeIcon() {
    switch (notification.type) {
      case 'application':
        return Icons.description_outlined;
      case 'interview':
        return Icons.calendar_today;
      case 'partnership':
        return Icons.handshake_outlined;
      case 'system':
        return Icons.info_outline;
      default:
        return Icons.notifications_outlined;
    }
  }
  
  Color _getTypeColor() {
    switch (notification.type) {
      case 'application':
        return const Color(0xFF6366F1);
      case 'interview':
        return Colors.blue;
      case 'partnership':
        return Colors.green;
      case 'system':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: notification.isRead ? Colors.white : Colors.blue[50],
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: _getTypeColor().withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                _getTypeIcon(),
                color: _getTypeColor(),
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          notification.title,
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: notification.isRead
                                ? FontWeight.w500
                                : FontWeight.w600,
                            color: const Color(0xFF1E293B),
                          ),
                        ),
                      ),
                      if (!notification.isRead)
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: Color(0xFF6366F1),
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    notification.message,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    timeago.format(
                      notification.createdAt,
                      locale: 'id',
                    ),
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey[400],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
