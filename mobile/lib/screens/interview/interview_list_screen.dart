import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/interview_provider.dart';
import '../../models/interview_model.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/error_widget.dart';
import '../../widgets/empty_widget.dart';
import '../../widgets/status_badge.dart';

class InterviewListScreen extends StatefulWidget {
  const InterviewListScreen({super.key});
  
  @override
  State<InterviewListScreen> createState() => _InterviewListScreenState();
}

class _InterviewListScreenState extends State<InterviewListScreen> {
  @override
  void initState() {
    super.initState();
    _loadInterviews();
  }
  
  Future<void> _loadInterviews() async {
    context.read<InterviewProvider>().fetchInterviews();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Interview',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1E293B),
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadInterviews,
        child: Consumer<InterviewProvider>(
          builder: (context, provider, child) {
            if (provider.state == InterviewState.loading) {
              return const LoadingWidget(message: 'Memuat interview...');
            }
            
            if (provider.state == InterviewState.error) {
              return ErrorWidgetCustom(
                message: provider.error ?? 'Terjadi kesalahan',
                onRetry: _loadInterviews,
              );
            }
            
            if (provider.state == InterviewState.empty) {
              return EmptyWidget(
                title: 'Belum ada interview',
                message: 'Anda akan mendapat notifikasi jika dijadwalkan interview',
                icon: Icons.calendar_today,
              );
            }
            
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: provider.interviews.length,
              itemBuilder: (context, index) {
                final interview = provider.interviews[index];
                return _InterviewCard(interview: interview);
              },
            );
          },
        ),
      ),
    );
  }
}

class _InterviewCard extends StatelessWidget {
  final Interview interview;
  
  const _InterviewCard({required this.interview});
  
  StatusBadge _getStatusBadge() {
    switch (interview.status) {
      case 'scheduled':
        return StatusBadge.scheduled();
      case 'completed':
        return StatusBadge.completed();
      case 'cancelled':
        return StatusBadge.cancelled();
      default:
        return StatusBadge(
          label: interview.statusLabel,
        );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.calendar_today,
                  color: Colors.blue,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      interview.application?.internship?.title ?? '-',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      interview.application?.internship?.company?.name ?? '-',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              _getStatusBadge(),
            ],
          ),
          const SizedBox(height: 16),
          
          // Interview details
          _DetailRow(
            icon: Icons.access_time,
            label: 'Waktu',
            value: _formatDateTime(interview.scheduledAt),
          ),
          if (interview.location != null && interview.location!.isNotEmpty)
            _DetailRow(
              icon: Icons.location_on_outlined,
              label: 'Lokasi',
              value: interview.location!,
            ),
          if (interview.notes != null && interview.notes!.isNotEmpty)
            _DetailRow(
              icon: Icons.notes,
              label: 'Catatan',
              value: interview.notes!,
            ),
        ],
      ),
    );
  }
  
  String _formatDateTime(DateTime dateTime) {
    final days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    final months = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    final day = days[dateTime.weekday - 1];
    final month = months[dateTime.month - 1];
    final hour = '${dateTime.hour.toString().padLeft(2, '0')}:${dateTime.minute.toString().padLeft(2, '0')}';
    
    return '$day, ${dateTime.day} $month ${dateTime.year} - $hour';
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  
  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });
  
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: Colors.grey[500]),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.grey[500],
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
