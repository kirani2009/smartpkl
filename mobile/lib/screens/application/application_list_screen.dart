import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/application_provider.dart';
import '../../models/application_model.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/error_widget.dart';
import '../../widgets/empty_widget.dart';
import '../../widgets/status_badge.dart';

class ApplicationListScreen extends StatefulWidget {
  const ApplicationListScreen({super.key});
  
  @override
  State<ApplicationListScreen> createState() => _ApplicationListScreenState();
}

class _ApplicationListScreenState extends State<ApplicationListScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String? _selectedStatus;
  
  final List<Map<String, String>> _tabs = [
    {'label': 'Semua', 'value': ''},
    {'label': 'Menunggu', 'value': 'pending'},
    {'label': 'Ditinjau', 'value': 'reviewed'},
    {'label': 'Diterima', 'value': 'accepted'},
    {'label': 'Ditolak', 'value': 'rejected'},
  ];
  
  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabs.length, vsync: this);
    _tabController.addListener(_onTabChanged);
    _loadApplications();
  }
  
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
  
  void _onTabChanged() {
    if (_tabController.indexIsChanging) return;
    
    final status = _tabs[_tabController.index]['value'];
    setState(() {
      _selectedStatus = status!.isEmpty ? null : status;
    });
    _loadApplications();
  }
  
  Future<void> _loadApplications() async {
    context.read<ApplicationProvider>().fetchApplications(
          status: _selectedStatus,
        );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Lamaran Saya',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1E293B),
          ),
        ),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: const Color(0xFF6366F1),
          unselectedLabelColor: Colors.grey[600],
          indicatorColor: const Color(0xFF6366F1),
          indicatorWeight: 3,
          tabs: _tabs.map((tab) {
            return Tab(text: tab['label']);
          }).toList(),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadApplications,
        child: Consumer<ApplicationProvider>(
          builder: (context, provider, child) {
            if (provider.state == ApplicationState.loading) {
              return const LoadingWidget(message: 'Memuat lamaran...');
            }
            
            if (provider.state == ApplicationState.error) {
              return ErrorWidgetCustom(
                message: provider.error ?? 'Terjadi kesalahan',
                onRetry: _loadApplications,
              );
            }
            
            if (provider.state == ApplicationState.empty) {
              return EmptyWidget(
                title: 'Belum ada lamaran',
                message: 'Mulai cari lowongan dan lamar!',
                icon: Icons.description_outlined,
                onAction: () {
                  // Navigate to internship list
                },
                actionLabel: 'Cari Lowongan',
              );
            }
            
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: provider.applications.length,
              itemBuilder: (context, index) {
                final application = provider.applications[index];
                return _ApplicationCard(
                  application: application,
                  onCancel: () => _cancelApplication(application),
                );
              },
            );
          },
        ),
      ),
    );
  }
  
  Future<void> _cancelApplication(Application application) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Batalkan Lamaran'),
        content: const Text('Apakah Anda yakin ingin membatalkan lamaran ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Tidak'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Ya, Batalkan'),
          ),
        ],
      ),
    );
    
    if (confirmed != true) return;
    
    final provider = context.read<ApplicationProvider>();
    final success = await provider.cancelApplication(application.id);
    
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success
                ? 'Lamaran berhasil dibatalkan'
                : provider.error ?? 'Gagal membatalkan lamaran',
          ),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }
  }
}

class _ApplicationCard extends StatelessWidget {
  final Application application;
  final VoidCallback onCancel;
  
  const _ApplicationCard({
    required this.application,
    required this.onCancel,
  });
  
  StatusBadge _getStatusBadge() {
    switch (application.status) {
      case 'pending':
        return StatusBadge.pending();
      case 'reviewed':
        return StatusBadge.scheduled();
      case 'accepted':
        return StatusBadge.accepted();
      case 'rejected':
        return StatusBadge.rejected();
      case 'cancelled':
        return StatusBadge.cancelled();
      default:
        return StatusBadge(
          label: application.statusLabel,
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
                  color: const Color(0xFF6366F1).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.work,
                  color: Color(0xFF6366F1),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      application.internship?.title ?? '-',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      application.internship?.company?.name ?? '-',
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
          Row(
            children: [
              Icon(Icons.calendar_today, size: 14, color: Colors.grey[500]),
              const SizedBox(width: 6),
              Text(
                'Dilamar: ${_formatDate(application.createdAt)}',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[500],
                ),
              ),
              const Spacer(),
              if (application.status == 'pending')
                TextButton(
                  onPressed: onCancel,
                  style: TextButton.styleFrom(
                    foregroundColor: Colors.red,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                  ),
                  child: const Text(
                    'Batalkan',
                    style: TextStyle(fontSize: 13),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
  
  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }
}
