import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  final String label;
  final Color? backgroundColor;
  final Color? textColor;
  final bool small;
  
  const StatusBadge({
    super.key,
    required this.label,
    this.backgroundColor,
    this.textColor,
    this.small = false,
  });
  
  // Factory constructor for common statuses
  factory StatusBadge.pending({bool small = false}) {
    return StatusBadge(
      label: 'Menunggu',
      backgroundColor: Colors.orange[100],
      textColor: Colors.orange[800],
      small: small,
    );
  }
  
  factory StatusBadge.active({bool small = false}) {
    return StatusBadge(
      label: 'Aktif',
      backgroundColor: Colors.green[100],
      textColor: Colors.green[800],
      small: small,
    );
  }
  
  factory StatusBadge.accepted({bool small = false}) {
    return StatusBadge(
      label: 'Diterima',
      backgroundColor: Colors.green[100],
      textColor: Colors.green[800],
      small: small,
    );
  }
  
  factory StatusBadge.rejected({bool small = false}) {
    return StatusBadge(
      label: 'Ditolak',
      backgroundColor: Colors.red[100],
      textColor: Colors.red[800],
      small: small,
    );
  }
  
  factory StatusBadge.cancelled({bool small = false}) {
    return StatusBadge(
      label: 'Dibatalkan',
      backgroundColor: Colors.grey[100],
      textColor: Colors.grey[800],
      small: small,
    );
  }
  
  factory StatusBadge.scheduled({bool small = false}) {
    return StatusBadge(
      label: 'Terjadwal',
      backgroundColor: Colors.blue[100],
      textColor: Colors.blue[800],
      small: small,
    );
  }
  
  factory StatusBadge.completed({bool small = false}) {
    return StatusBadge(
      label: 'Selesai',
      backgroundColor: Colors.green[100],
      textColor: Colors.green[800],
      small: small,
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: small ? 8 : 12,
        vertical: small ? 4 : 6,
      ),
      decoration: BoxDecoration(
        color: backgroundColor ?? Colors.grey[100],
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: textColor ?? Colors.grey[800],
          fontSize: small ? 11 : 13,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}
