import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/internship_provider.dart';
import '../../models/internship_model.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/error_widget.dart';
import '../../widgets/empty_widget.dart';
import 'internship_detail_screen.dart';

class InternshipListScreen extends StatefulWidget {
  const InternshipListScreen({super.key});
  
  @override
  State<InternshipListScreen> createState() => _InternshipListScreenState();
}

class _InternshipListScreenState extends State<InternshipListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  String? _selectedType;
  
  @override
  void initState() {
    super.initState();
    _loadInternships();
    _scrollController.addListener(_onScroll);
  }
  
  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }
  
  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      context.read<InternshipProvider>().fetchInternships(
            search: _searchController.text,
          );
    }
  }
  
  Future<void> _loadInternships() async {
    context.read<InternshipProvider>().fetchInternships(refresh: true);
  }
  
  Future<void> _search() async {
    context.read<InternshipProvider>().fetchInternships(
          search: _searchController.text,
          refresh: true,
        );
  }
  
  void _showFilterSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Filter',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 24),
                  
                  // Type filter
                  const Text(
                    'Tipe',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    children: [
                      _FilterChip(
                        label: 'Semua',
                        selected: _selectedType == null,
                        onSelected: () {
                          setModalState(() {
                            _selectedType = null;
                          });
                        },
                      ),
                      _FilterChip(
                        label: 'Full Time',
                        selected: _selectedType == 'full_time',
                        onSelected: () {
                          setModalState(() {
                            _selectedType = 'full_time';
                          });
                        },
                      ),
                      _FilterChip(
                        label: 'Part Time',
                        selected: _selectedType == 'part_time',
                        onSelected: () {
                          setModalState(() {
                            _selectedType = 'part_time';
                          });
                        },
                      ),
                      _FilterChip(
                        label: 'Remote',
                        selected: _selectedType == 'remote',
                        onSelected: () {
                          setModalState(() {
                            _selectedType = 'remote';
                          });
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  
                  // Apply button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        context.read<InternshipProvider>().fetchInternships(
                              search: _searchController.text,
                              filters: {
                                if (_selectedType != null) 'type': _selectedType,
                              },
                              refresh: true,
                            );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6366F1),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text('Terapkan Filter'),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            );
          },
        );
      },
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
          'Cari Lowongan',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1E293B),
          ),
        ),
      ),
      body: Column(
        children: [
          // Search bar
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Cari lowongan...',
                      prefixIcon: const Icon(Icons.search),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear),
                              onPressed: () {
                                _searchController.clear();
                                _search();
                              },
                            )
                          : null,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: Colors.grey[300]!),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: Colors.grey[300]!),
                      ),
                      filled: true,
                      fillColor: Colors.grey[50],
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                    ),
                    onSubmitted: (_) => _search(),
                    onChanged: (value) {
                      setState(() {});
                    },
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: _showFilterSheet,
                  icon: const Icon(Icons.filter_list),
                  style: IconButton.styleFrom(
                    backgroundColor: const Color(0xFF6366F1).withOpacity(0.1),
                    foregroundColor: const Color(0xFF6366F1),
                  ),
                ),
              ],
            ),
          ),
          
          // Internship list
          Expanded(
            child: Consumer<InternshipProvider>(
              builder: (context, provider, child) {
                if (provider.state == InternshipState.loading &&
                    provider.internships.isEmpty) {
                  return const LoadingWidget(message: 'Memuat lowongan...');
                }
                
                if (provider.state == InternshipState.error &&
                    provider.internships.isEmpty) {
                  return ErrorWidgetCustom(
                    message: provider.error ?? 'Terjadi kesalahan',
                    onRetry: _loadInternships,
                  );
                }
                
                if (provider.state == InternshipState.empty) {
                  return EmptyWidget(
                    title: 'Tidak ada lowongan',
                    message: 'Coba ubah filter atau kata kunci pencarian',
                    icon: Icons.work_off_outlined,
                  );
                }
                
                return RefreshIndicator(
                  onRefresh: _loadInternships,
                  child: ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.all(16),
                    itemCount: provider.internships.length +
                        (provider.hasMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == provider.internships.length) {
                        return const Padding(
                          padding: EdgeInsets.all(16),
                          child: Center(
                            child: CircularProgressIndicator(),
                          ),
                        );
                      }
                      
                      final internship = provider.internships[index];
                      return _InternshipCard(
                        internship: internship,
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => InternshipDetailScreen(
                                internshipId: internship.id,
                              ),
                            ),
                          );
                        },
                        onSave: () {
                          provider.toggleSave(internship.id);
                        },
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _InternshipCard extends StatelessWidget {
  final Internship internship;
  final VoidCallback onTap;
  final VoidCallback onSave;
  
  const _InternshipCard({
    required this.internship,
    required this.onTap,
    required this.onSave,
  });
  
  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
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
                        internship.title,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        internship.company?.name ?? '-',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                GestureDetector(
                  onTap: onSave,
                  child: Icon(
                    internship.isSaved == true
                        ? Icons.bookmark
                        : Icons.bookmark_border,
                    color: internship.isSaved == true
                        ? const Color(0xFF6366F1)
                        : Colors.grey[400],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              internship.description,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (internship.location != null)
                  _InfoChip(
                    icon: Icons.location_on_outlined,
                    text: internship.location!,
                  ),
                if (internship.type != null)
                  _InfoChip(
                    icon: Icons.schedule,
                    text: internship.typeLabel,
                  ),
                if (internship.major?.name != null)
                  _InfoChip(
                    icon: Icons.school,
                    text: internship.major!.name,
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String text;
  
  const _InfoChip({
    required this.icon,
    required this.text,
  });
  
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: Colors.grey[600]),
          const SizedBox(width: 4),
          Text(
            text,
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onSelected;
  
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onSelected,
  });
  
  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onSelected(),
      selectedColor: const Color(0xFF6366F1),
      checkmarkColor: Colors.white,
      labelStyle: TextStyle(
        color: selected ? Colors.white : Colors.grey[700],
        fontWeight: FontWeight.w500,
      ),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
        side: BorderSide(
          color: selected ? const Color(0xFF6366F1) : Colors.grey[300]!,
        ),
      ),
    );
  }
}
