import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/internship_provider.dart';
import '../../models/internship_model.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/error_widget.dart';

class InternshipDetailScreen extends StatefulWidget {
  final int internshipId;
  
  const InternshipDetailScreen({
    super.key,
    required this.internshipId,
  });
  
  @override
  State<InternshipDetailScreen> createState() => _InternshipDetailScreenState();
}

class _InternshipDetailScreenState extends State<InternshipDetailScreen> {
  @override
  void initState() {
    super.initState();
    context.read<InternshipProvider>().fetchInternshipDetail(widget.internshipId);
  }
  
  Future<void> _apply() async {
    final provider = context.read<InternshipProvider>();
    
    // Show confirmation dialog
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Lamar Lowongan'),
        content: const Text('Apakah Anda yakin ingin melamar lowongan ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6366F1),
              foregroundColor: Colors.white,
            ),
            child: const Text('Ya, Lamar'),
          ),
        ],
      ),
    );
    
    if (confirmed != true) return;
    
    final success = await provider.applyInternship(widget.internshipId);
    
    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Berhasil melamar! Cek lamaran Anda.'),
            backgroundColor: Colors.green,
          ),
        );
        // Refresh detail
        provider.fetchInternshipDetail(widget.internshipId);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(provider.error ?? 'Gagal melamar'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Detail Lowongan',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1E293B),
          ),
        ),
        actions: [
          Consumer<InternshipProvider>(
            builder: (context, provider, child) {
              final internship = provider.selectedInternship;
              if (internship == null) return const SizedBox();
              
              return IconButton(
                onPressed: () {
                  provider.toggleSave(internship.id);
                },
                icon: Icon(
                  internship.isSaved == true
                      ? Icons.bookmark
                      : Icons.bookmark_border,
                  color: internship.isSaved == true
                      ? const Color(0xFF6366F1)
                      : Colors.grey,
                ),
              );
            },
          ),
        ],
      ),
      body: Consumer<InternshipProvider>(
        builder: (context, provider, child) {
          if (provider.state == InternshipState.loading) {
            return const LoadingWidget(message: 'Memuat detail...');
          }
          
          if (provider.state == InternshipState.error) {
            return ErrorWidgetCustom(
              message: provider.error ?? 'Terjadi kesalahan',
              onRetry: () {
                provider.fetchInternshipDetail(widget.internshipId);
              },
            );
          }
          
          final internship = provider.selectedInternship;
          if (internship == null) {
            return const ErrorWidgetCustom(message: 'Data tidak ditemukan');
          }
          
          return Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Header card
                      _HeaderCard(internship: internship),
                      const SizedBox(height: 16),
                      
                      // Description
                      _Section(
                        title: 'Deskripsi',
                        child: Text(
                          internship.description,
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[700],
                            height: 1.6,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      
                      // Requirements
                      if (internship.requirements != null &&
                          internship.requirements!.isNotEmpty)
                        _Section(
                          title: 'Persyaratan',
                          child: Text(
                            internship.requirements!,
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey[700],
                              height: 1.6,
                            ),
                          ),
                        ),
                      if (internship.requirements != null &&
                          internship.requirements!.isNotEmpty)
                        const SizedBox(height: 16),
                      
                      // Benefits
                      if (internship.benefits != null &&
                          internship.benefits!.isNotEmpty)
                        _Section(
                          title: 'Benefit',
                          child: Text(
                            internship.benefits!,
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey[700],
                              height: 1.6,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              
              // Apply button
              if (internship.status == 'active')
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.05),
                        blurRadius: 10,
                        offset: const Offset(0, -2),
                      ),
                    ],
                  ),
                  child: SafeArea(
                    child: SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        onPressed: _apply,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6366F1),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 0,
                        ),
                        child: const Text(
                          'Lamar Sekarang',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _HeaderCard extends StatelessWidget {
  final Internship internship;
  
  const _HeaderCard({required this.internship});
  
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
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
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: const Color(0xFF6366F1).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(
                  Icons.work,
                  color: Color(0xFF6366F1),
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      internship.title,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      internship.company?.name ?? '-',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          
          // Info chips
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              if (internship.location != null)
                _InfoItem(
                  icon: Icons.location_on_outlined,
                  text: internship.location!,
                ),
              if (internship.type != null)
                _InfoItem(
                  icon: Icons.schedule,
                  text: internship.typeLabel,
                ),
              if (internship.major?.name != null)
                _InfoItem(
                  icon: Icons.school,
                  text: internship.major!.name,
                ),
              if (internship.quota != null)
                _InfoItem(
                  icon: Icons.people_outline,
                  text: 'Kuota: ${internship.quota} orang',
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoItem extends StatelessWidget {
  final IconData icon;
  final String text;
  
  const _InfoItem({
    required this.icon,
    required this.text,
  });
  
  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: Colors.grey[500]),
        const SizedBox(width: 6),
        Text(
          text,
          style: TextStyle(
            fontSize: 13,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }
}

class _Section extends StatelessWidget {
  final String title;
  final Widget child;
  
  const _Section({
    required this.title,
    required this.child,
  });
  
  @override
  Widget build(BuildContext context) {
    return Container(
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
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}
